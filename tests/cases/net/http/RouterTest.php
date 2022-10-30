<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net\http;

use lithium\action\Request;
use lithium\net\http\Router;
use lithium\action\Response;
use lithium\tests\mocks\action\MockDispatcher;
use lithium\tests\mocks\net\http\MockRouter;

class RouterTest extends \lithium\test\Unit {

	public $request = null;

	public function setUp() {
		$this->request = new Request();
	}

	public function tearDown() {
		Router::reset();
	}

	public function testBasicRouteConnection() {
		$result = Router::connect('/hello', ['controller' => 'Posts', 'action' => 'index']);
		$expected = [
			'template' => '/hello',
			'pattern' => '@^/hello$@u',
			'params' => ['controller' => 'Posts', 'action' => 'index'],
			'match' => ['controller' => 'Posts', 'action' => 'index'],
			'meta' => [],
			'persist' => ['controller'],
			'defaults' => [],
			'keys' => [],
			'subPatterns' => [],
			'handler' => null
		];
		$this->assertEqual($expected, $result->export());

		$result = Router::connect('/{:controller}/{:action}', ['action' => 'view']);
		$this->assertInstanceOf('lithium\net\http\Route', $result);
		$expected = [
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@u',
			'params' => ['action' => 'view'],
			'defaults' => ['action' => 'view'],
			'match' => [],
			'meta' => [],
			'persist' => ['controller'],
			'keys' => ['controller' => 'controller', 'action' => 'action'],
			'subPatterns' => [],
			'handler' => null
		];
		$this->assertEqual($expected, $result->export());
	}

	/**
	 * Tests generating routes with required parameters which are not present in the URL.
	 */
	public function testConnectingWithRequiredParams() {
		$result = Router::connect('/{:controller}/{:action}', [
			'action' => 'view', 'required' => true
		]);
		$expected = [
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@u',
			'keys' => ['controller' => 'controller', 'action' => 'action'],
			'params' => ['action' => 'view', 'required' => true],
			'defaults' => ['action' => 'view'],
			'match' => ['required' => true],
			'meta' => [],
			'persist' => ['controller'],
			'subPatterns' => [],
			'handler' => null
		];
		$this->assertEqual($expected, $result->export());
	}

	public function testConnectingWithDefaultParams() {
		$result = Router::connect('/{:controller}/{:action}', ['action' => 'archive']);
		$expected = [
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\/]+))(?:/(?P<action>[^\/]+)?)?$@u',
			'keys' => ['controller' => 'controller', 'action' => 'action'],
			'params' => ['action' => 'archive'],
			'match' => [],
			'meta' => [],
			'persist' => ['controller'],
			'defaults' => ['action' => 'archive'],
			'subPatterns' => [],
			'handler' => null
		];
		$this->assertEqual($expected, $result->export());
	}

	/**
	 * Tests basic options for connecting routes.
	 */
	public function testBasicRouteMatching() {
		Router::connect('/hello', ['controller' => 'Posts', 'action' => 'index']);
		$expected = ['controller' => 'Posts', 'action' => 'index'];

		foreach (['/hello/', '/hello', 'hello/', 'hello'] as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result->params);
			$this->assertEqual(['controller'], $result->persist);
		}
	}

	public function testRouteMatchingWithDefaultParameters() {
		Router::connect('/{:controller}/{:action}', ['action' => 'view']);
		$expected = ['controller' => 'Posts', 'action' => 'view'];

		foreach (['/posts/view', '/posts', 'posts', 'posts/view', 'posts/view/'] as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result->params);
			$this->assertEqual(['controller'], $result->persist);
		}
		$expected['action'] = 'index';

		foreach (['/posts/index', 'posts/index', 'posts/index/'] as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result->params);
		}

		$this->request->url = '/posts/view/1';
		$this->assertNull(Router::parse($this->request));
	}

	/**
	 * Tests that URLs specified as "Controller::action" are interpreted properly.
	 */
	public function testStringActions() {
		Router::connect('/login', ['controller' => 'sessions', 'action' => 'create']);
		Router::connect('/{:controller}/{:action}');

		$result = Router::match("Sessions::create");
		$this->assertIdentical('/login', $result);

		$result = Router::match("Posts::index");
		$this->assertIdentical('/posts', $result);

		$result = Router::match("ListItems::archive");
		$this->assertIdentical('/list_items/archive', $result);
	}

	public function testNamedAnchor() {
		Router::connect('/{:controller}/{:action}');
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', ['id' => null]);

		$result = Router::match(['Posts::edit', '#' => 'foo']);
		$this->assertIdentical('/posts/edit#foo', $result);

		$result = Router::match(['Posts::edit', 'id' => 42, '#' => 'foo']);
		$this->assertIdentical('/posts/edit/42#foo', $result);

		$result = Router::match(['controller' => 'users', 'action' => 'view', '#' => 'blah']);
		$this->assertIdentical('/users/view#blah', $result);

		$result = Router::match([
			'controller' => 'users', 'action' => 'view', 'id' => 47, '#' => 'blargh'
		]);
		$this->assertIdentical('/users/view/47#blargh', $result);
	}

	public function testQueryString() {
		Router::connect('/{:controller}/{:action}');
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', ['id' => null]);

		$result = Router::match(['Posts::edit', '?' => ['key' => 'value']]);
		$this->assertIdentical('/posts/edit?key=value', $result);

		$result = Router::match([
			'Posts::edit', 'id' => 42, '?' => ['key' => 'value', 'test' => 'foo']
		]);
		$this->assertIdentical('/posts/edit/42?key=value&test=foo', $result);
	}

	/**
	 * Tests that URLs specified as "Controller::action" and including additional parameters are
	 * interpreted properly.
	 */
	public function testEmbeddedStringActions() {
		Router::connect('/logout/{:id:[0-9]{5,6}}', [
			'controller' => 'sessions', 'action' => 'destroy', 'id' => null
		]);
		Router::connect('/{:controller}/{:action}');
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', ['id' => null]);

		$result = Router::match("Sessions::create");
		$this->assertIdentical('/sessions/create', $result);

		$result = Router::match(["Sessions::create"]);
		$this->assertIdentical('/sessions/create', $result);

		$result = Router::match(["Sessions::destroy", 'id' => '03815']);
		$this->assertIdentical('/logout/03815', $result);

		$result = Router::match("Posts::index");
		$this->assertIdentical('/posts', $result);

		$ex = "No parameter match found for URL ";
		$ex .= "`('controller' => 'Sessions', 'action' => 'create', 'id' => 'foo')`.";
		$this->assertException($ex, function() {
			Router::match(["Sessions::create", 'id' => 'foo']);
		});
	}

	/**
	 * Tests that routes can be created with shorthand strings, i.e. `'Controller::action'` and
	 * `['Controller::action', 'id' => '...']`.
	 */
	public function testStringParameterConnect() {
		Router::connect('/posts/{:id:[0-9a-f]{24}}', 'Posts::edit');

		$result = Router::match([
			'controller' => 'posts', 'action' => 'edit', 'id' => '4bbf25bd8ead0e5180130000'
		]);
		$expected = '/posts/4bbf25bd8ead0e5180130000';
		$this->assertIdentical($expected, $result);

		$ex = "No parameter match found for URL `(";
		$ex .= "'controller' => 'Posts', 'action' => 'view', 'id' => '4bbf25bd8ead0e5180130000')`.";
		$this->assertException($ex, function() {
			Router::match([
				'controller' => 'posts', 'action' => 'view', 'id' => '4bbf25bd8ead0e5180130000'
			]);
		});
	}

	public function testShorthandParameterMatching() {
		Router::reset();
		Router::connect('/posts/{:page:[0-9]+}', ['Posts::index', 'page' => '1']);
		Router::connect('/admin/posts/{:page:[0-9]+}', ['Posts::index', 'page' => '1', 'library' => 'admin']);

		$result = Router::match(['controller' => 'posts', 'page' => '5']);
		$expected = '/posts/5';
		$this->assertIdentical($expected, $result);

		$result = Router::match(['Posts::index', 'page' => '10']);
		$expected = '/posts/10';
		$this->assertIdentical($expected, $result);

		$result = Router::match(["admin.Posts::index", 'page' => '9']);
		$expected = '/admin/posts/9';
		$this->assertEqual($expected, $result);

		$request = new Request(['url' => '/posts/13']);
		$result = Router::process($request);
		$expected = ['controller' => 'Posts', 'action' => 'index', 'page' => '13'];
		$this->assertEqual($expected, $result->params);
	}

	/**
	 * Tests that routing is fully reset when calling `Router::reset()`.
	 */
	public function testResettingRoutes() {
		Router::connect('/{:controller}', ['controller' => 'Posts']);
		$this->request->url = '/hello';

		$expected = ['controller' => 'Hello', 'action' => 'index'];
		$result = Router::parse($this->request);
		$this->assertEqual($expected, $result->params);

		Router::reset();
		$this->assertNull(Router::parse($this->request));
	}

	/**
	 * Tests matching routes where the route template is a static string with no insert parameters.
	 */
	public function testRouteMatchingWithNoInserts() {
		Router::connect('/login', ['controller' => 'sessions', 'action' => 'add']);
		$result = Router::match(['controller' => 'sessions', 'action' => 'add']);
		$this->assertIdentical('/login', $result);

		$expected  = "No parameter match found for URL `('controller' => 'Sessions', ";
		$expected .= "'action' => 'index')`.";
		$this->assertException($expected, function() {
			Router::match(['controller' => 'sessions', 'action' => 'index']);
		});
	}

	/**
	 * Test matching routes with only insert parameters and no default values.
	 */
	public function testRouteMatchingWithOnlyInserts() {
		Router::connect('/{:controller}');
		$this->assertIdentical('/posts', Router::match(['controller' => 'posts']));

		$expected  = "No parameter match found for URL `('controller' => 'Posts', ";
		$expected .= "'action' => 'view')`.";
		$this->assertException($expected, function() {
			Router::match(['controller' => 'posts', 'action' => 'view']);
		});
	}

	/**
	 * Test matching routes with insert parameters which have default values.
	 */
	public function testRouteMatchingWithInsertsAndDefaults() {
		Router::connect('/{:controller}/{:action}', ['action' => 'archive']);
		$this->assertIdentical('/posts/index', Router::match(['controller' => 'posts']));

		$result = Router::match(['controller' => 'posts', 'action' => 'archive']);
		$this->assertIdentical('/posts', $result);

		Router::reset();
		Router::connect('/{:controller}/{:action}', ['controller' => 'users']);

		$result = Router::match(['action' => 'view']);
		$this->assertIdentical('/users/view', $result);

		$result = Router::match(['controller' => 'posts', 'action' => 'view']);
		$this->assertIdentical('/posts/view', $result);

		$ex = "No parameter match found for URL ";
		$ex .= "`('controller' => 'Posts', 'action' => 'view', 'id' => '2')`.";
		$this->assertException($ex, function() {
			Router::match(['controller' => 'posts', 'action' => 'view', 'id' => '2']);
		});
	}

	/**
	 * Tests matching routes and returning an absolute (protocol + hostname) URL.
	 */
	public function testRouteMatchAbsoluteUrl() {
		Router::connect('/login', ['controller' => 'sessions', 'action' => 'add']);
		$result = Router::match('Sessions::add', $this->request);
		$base = $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add', $this->request, ['absolute' => true]);
		$base  = $this->request->env('HTTPS') ? 'https://' : 'http://';
		$base .= $this->request->env('HTTP_HOST');
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add',
			$this->request, ['host' => 'test.local', 'absolute' => true]
		);
		$base = $this->request->env('HTTPS') ? 'https://' : 'http://';
		$base .= 'test.local';
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add',
			$this->request, ['scheme' => 'https://', 'absolute' => true]
		);
		$base = 'https://' . $this->request->env('HTTP_HOST');
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add',
			$this->request, ['scheme' => 'https://', 'absolute' => true]
		);
		$base = 'https://' . $this->request->env('HTTP_HOST');
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);
	}

	public function testEmptyUrlMatching() {
		$result = Router::match('');
		$expected = '/';
		$this->assertIdentical($expected, $result);

		$this->assertException('/No parameter match found for URL/', function() {
			Router::match([]);
		});
	}

	/**
	 * Tests getting routes using `Router::get()`, and checking to see if the routes returned match
	 * the routes connected.
	 */
	public function testRouteRetrieval() {
		$expected = Router::connect('/hello', ['controller' => 'posts', 'action' => 'index']);
		$result = Router::get(0, true);
		$this->assertIdentical($expected, $result);

		list($result) = Router::get(null, true);
		$this->assertIdentical($expected, $result);
	}

	public function testStringUrlGeneration() {
		$result = Router::match('/posts');
		$expected = '/posts';
		$this->assertIdentical($expected, $result);

		$result = Router::match('/posts');
		$this->assertIdentical($expected, $result);

		$result = Router::match('/posts/view/5');
		$expected = '/posts/view/5';
		$this->assertIdentical($expected, $result);

		$request = new Request(['base' => '/my/web/path']);
		$result = Router::match('/posts', $request);
		$expected = '/my/web/path/posts';
		$this->assertIdentical($expected, $result);

		$request = new Request(['base' => '/my/web/path']);
		$result = Router::match('/some/where', $request, ['absolute' => true]);
		$prefix  = $this->request->env('HTTPS') ? 'https://' : 'http://';
		$prefix .= $this->request->env('HTTP_HOST');
		$this->assertIdentical($prefix . '/my/web/path/some/where', $result);
	}

	public function testStringUrlGenerationPassedThrough() {
		$result = Router::match('mailto:foo@localhost');
		$expected = 'mailto:foo@localhost';
		$this->assertIdentical($expected, $result);

		$result = Router::match('tel:+49401234567');
		$expected = 'tel:+49401234567';
		$this->assertIdentical($expected, $result);

		$result = Router::match('sms:+49401234567');
		$expected = 'sms:+49401234567';
		$this->assertIdentical($expected, $result);

		$result = Router::match('#top');
		$expected = '#top';
		$this->assertIdentical($expected, $result);

		$result = Router::match('javascript:alert(1)');
		$expected = 'javascript:alert(1)';
		$this->assertEqual($expected, $result);
	}

	public function testWithWildcardString() {
		Router::connect('/add/{:args}', ['controller' => 'tests', 'action' => 'add']);

		$expected = '/add';
		$result = Router::match('/add');
		$this->assertIdentical($expected, $result);

		$expected = '/add/alke';
		$result = Router::match('/add/alke');
		$this->assertIdentical($expected, $result);
	}

	public function testWithWildcardArray() {
		Router::connect('/add/{:args}', ['controller' => 'tests', 'action' => 'add']);

		$expected = '/add';
		$result = Router::match(['controller' => 'tests', 'action' => 'add']);
		$this->assertIdentical($expected, $result);

		$expected = '/add/alke';
		$result = Router::match([
			'controller' => 'tests', 'action' => 'add', 'args' => ['alke']
		]);
		$this->assertIdentical($expected, $result);

		$expected = '/add/alke/php';
		$result = Router::match([
			'controller' => 'tests', 'action' => 'add', 'args' => ['alke', 'php']
		]);
		$this->assertIdentical($expected, $result);
	}

	public function testProcess() {
		Router::connect('/add/{:args}', ['controller' => 'tests', 'action' => 'add']);
		$request = Router::process(new Request(['url' => '/add/foo/bar']));

		$params = ['controller' => 'Tests', 'action' => 'add', 'args' => ['foo', 'bar']];
		$this->assertEqual($params, $request->params);
		$this->assertEqual(['controller'], $request->persist);

		$request = Router::process(new Request(['url' => '/remove/foo/bar']));
		$this->assertEmpty($request->params);
	}

	public function testActionWithProcess() {
		Router::connect('/{:controller}/{:action}/{:args}');
		$request = Router::process(new Request(['url' => '/users/toJson']));

		$params = ['controller' => 'Users', 'action' => 'toJson', 'args' => []];
		$this->assertEqual($params, $request->params);

		$request = Router::process(new Request(['url' => '/users/to_json']));

		$params = ['controller' => 'Users', 'action' => 'to_json', 'args' => []];
		$this->assertEqual($params, $request->params);
	}

	/**
	 * Tests that the order of the parameters is respected so it can trim
	 * the URL correctly.
	 */
	public function testParameterOrderIsRespected() {
		Router::connect('/{:locale}/{:controller}/{:action}/{:args}');
		Router::connect('/{:controller}/{:action}/{:args}');

		$request = Router::process(new Request(['url' => 'posts']));

		$url = Router::match('Posts::index', $request);
		$this->assertIdentical($this->request->env('base') . '/posts', $url);

		$request = Router::process(new Request(['url' => 'fr/posts']));

		$params = ['Posts::index', 'locale' => 'fr'];
		$url = Router::match($params, $request);
		$this->assertIdentical($this->request->env('base') . '/fr/posts', $url);
	}

	/**
	 * Tests that a request context with persistent parameters generates URLs where those parameters
	 * are properly taken into account.
	 */
	public function testParameterPersistence() {
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', [], [
			'persist' => ['controller', 'id']
		]);

		// URLs generated with $request will now have the 'controller' and 'id'
		// parameters copied to new URLs.
		$request = Router::process(new Request(['url' => 'posts/view/1138']));

		$params = ['action' => 'edit'];
		$url = Router::match($params, $request); // Returns: '/posts/edit/1138'
		$this->assertIdentical($this->request->env('base') . '/posts/edit/1138', $url);

		Router::connect(
			'/add/{:args}',
			['controller' => 'tests', 'action' => 'add'],
			['persist' => ['controller', 'action']]
		);
		$request = Router::process(new Request(['url' => '/add/foo/bar', 'base' => '']));
		$path = Router::match(['args' => ['baz', 'dib']], $request);
		$this->assertIdentical('/add/baz/dib', $path);
	}

	/**
	 * Tests that persistent parameters can be overridden with nulled-out values.
	 */
	public function testOverridingPersistentParameters() {
		Router::connect(
			'/admin/{:controller}/{:action}',
			['admin' => true],
			['persist' => ['admin', 'controller']]
		);
		Router::connect('/{:controller}/{:action}');

		$request = Router::process(new Request(['url' => '/admin/posts/add', 'base' => '']));
		$expected = ['controller' => 'Posts', 'action' => 'add', 'admin' => true];
		$this->assertEqual($expected, $request->params);
		$this->assertEqual(['admin', 'controller'], $request->persist);

		$url = Router::match(['action' => 'archive'], $request);
		$this->assertIdentical('/admin/posts/archive', $url);

		$url = Router::match(['action' => 'archive', 'admin' => null], $request);
		$this->assertIdentical('/posts/archive', $url);
	}

	/**
	 * Tests passing a closure handler to `Router::connect()` to bypass or augment default
	 * dispatching.
	 */
	public function testRouteHandler() {
		Router::connect('/login', 'Users::login');

		Router::connect('/users/login', [], function($request) {
			return new Response([
				'location' => ['controller' => 'Users', 'action' => 'login']
			]);
		});

		$result = Router::process(new Request(['url' => '/users/login']));
		$this->assertInstanceOf('lithium\action\Response', $result);

		$headers = ['Location' => '/login'];
		$this->assertEqual($headers, $result->headers);
	}

	/**
	 * Tests that a successful match against a route with template `'/'` operating at the root of
	 * a domain never returns an empty string.
	 */
	public function testMatchingEmptyRoute() {
		Router::connect('/', 'Users::view');

		$request = new Request(['base' => '/']);
		$url = Router::match(['controller' => 'users', 'action' => 'view'], $request);
		$this->assertIdentical('/', $url);

		$request = new Request(['base' => '']);
		$url = Router::match(['controller' => 'users', 'action' => 'view'], $request);
		$this->assertIdentical('/', $url);
	}

	/**
	 * Tests routing based on content type extensions, with HTML being the default when types are
	 * not defined.
	 */
	public function testTypeBasedRouting() {
		Router::connect('/{:controller}/{:id:[0-9]+}', [
			'action' => 'index', 'type' => 'html', 'id' => null
		]);
		Router::connect('/{:controller}/{:id:[0-9]+}.{:type}', [
			'action' => 'index', 'id' => null
		]);

		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', [
			'type' => 'html', 'id' => null
		]);
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}.{:type}', ['id' => null]);

		$url = Router::match(['controller' => 'posts', 'type' => 'html']);
		$this->assertIdentical('/posts', $url);

		$url = Router::match(['controller' => 'posts', 'type' => 'json']);
		$this->assertIdentical('/posts.json', $url);
	}

	/**
	 * Tests that routes can be connected and correctly match based on HTTP headers or method verbs.
	 */
	public function testHttpMethodBasedRouting() {
		Router::connect('/{:controller}/{:id:[0-9]+}', [
			'http:method' => 'GET', 'action' => 'view'
		]);
		Router::connect('/{:controller}/{:id:[0-9]+}', [
			'http:method' => 'PUT', 'action' => 'edit'
		]);

		$request = new Request(['url' => '/posts/13', 'env' => [
			'REQUEST_METHOD' => 'GET'
		]]);
		$params = Router::process($request)->params;
		$expected = ['controller' => 'Posts', 'action' => 'view', 'id' => '13'];
		$this->assertEqual($expected, $params);

		$this->assertIdentical('/posts/13', Router::match($params));

		$request = new Request(['url' => '/posts/13', 'env' => [
			'REQUEST_METHOD' => 'PUT'
		]]);
		$params = Router::process($request)->params;
		$expected = ['controller' => 'Posts', 'action' => 'edit', 'id' => '13'];
		$this->assertEqual($expected, $params);

		$request = new Request(['url' => '/posts/13', 'env' => [
			'REQUEST_METHOD' => 'POST'
		]]);
		$params = Router::process($request)->params;
		$this->assertEmpty($params);
	}

	/**
	 * Tests that the class dependency configuration can be modified.
	 */
	public function testCustomConfiguration() {
		$old = Router::config();
		$config = ['classes' => [
			'route' => 'my\custom\Route',
			'configuration' => 'lithium\net\http\Configuration'
		], 'unicode' => true];

		Router::config($config);
		$this->assertEqual($config, Router::config());

		Router::config($old);
		$this->assertEqual($old, Router::config());
	}

	/**
	 * Tests that continuation routes properly fall through and aggregate multiple route parameters.
	 */
	public function testRouteContinuations() {
		Router::connect('/{:locale:en|de|it|jp}/{:args}', [], ['continue' => true]);
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');

		$request = new Request(['url' => '/en/posts/view/1138']);
		$result = Router::process($request)->params;
		$expected = [
			'controller' => 'Posts', 'action' => 'view', 'id' => '1138', 'locale' => 'en'
		];
		$this->assertEqual($expected, $result);

		$request = new Request(['url' => '/en/foo/bar/baz']);
		$this->assertNull(Router::parse($request));

		Router::reset();
		Router::connect('/{:args}/{:locale:en|de|it|jp}', [], ['continue' => true]);
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');

		$request = new Request(['url' => '/posts/view/1138/en']);
		$result = Router::process($request)->params;
		$this->assertEqual($expected, $result);

		Router::reset();
		Router::connect('/{:locale:en|de|it|jp}/{:args}', [], ['continue' => true]);
		Router::connect('/', 'Pages::view');

		$request = new Request(['url' => '/en']);
		$result = Router::process($request)->params;
		$expected = ['locale' => 'en', 'controller' => 'Pages', 'action' => 'view'];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that URLs are properly generated with route continuations.
	 */
	public function testReversingContinuations() {
		Router::connect('/{:locale:en|de|it|jp}/{:args}', [], ['continue' => true]);
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');
		Router::connect('/{:controller}/{:action}/{:args}');

		$result = Router::match(['Posts::view', 'id' => 5, 'locale' => 'de']);
		$this->assertEqual($result, '/de/posts/view/5');

		$result = Router::match(['Posts::index', 'locale' => 'en', '?' => ['page' => 2]]);
		$this->assertIdentical('/en/posts?page=2', $result);

		Router::reset();
		Router::connect('/{:locale:en|de|it|jp}/{:args}', [], ['continue' => true]);
		Router::connect('/pages/{:args}', 'Pages::view');

		$result = Router::match(['Pages::view', 'locale' => 'en', 'args' => ['about']]);
		$this->assertIdentical('/en/pages/about', $result);

		Router::reset();
		Router::connect('/admin/{:args}', ['admin' => true], ['continue' => true]);
		Router::connect('/login', 'Users::login');

		$result = Router::match(['Users::login', 'admin' => true]);
		$this->assertIdentical('/admin/login', $result);
	}

	/**
	 * Tests that multiple continuation routes can be applied to the same URL.
	 */
	public function testStackedContinuationRoutes() {
		Router::connect('/admin/{:args}', ['admin' => true], ['continue' => true]);
		Router::connect('/{:locale:en|de|it|jp}/{:args}', [], ['continue' => true]);
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', ['id' => null]);

		$request = new Request(['url' => '/en/foo/bar/5']);
		$expected = ['controller' => 'Foo', 'action' => 'bar', 'id' => '5', 'locale' => 'en'];
		$this->assertEqual($expected, Router::process($request)->params);

		$request = new Request(['url' => '/admin/foo/bar/5']);
		$expected = ['controller' => 'Foo', 'action' => 'bar', 'id' => '5', 'admin' => true];
		$this->assertEqual($expected, Router::process($request)->params);

		$request = new Request(['url' => '/admin/de/foo/bar/5']);
		$expected = [
			'controller' => 'Foo', 'action' => 'bar', 'id' => '5', 'locale' => 'de', 'admin' => true
		];
		$this->assertEqual($expected, Router::process($request)->params);

		$request = new Request(['url' => '/en/admin/foo/bar/5']);
		$this->assertEmpty(Router::process($request)->params);

		$result = Router::match(['Foo::bar', 'id' => 5]);
		$this->assertIdentical('/foo/bar/5', $result);

		$result = Router::match(['Foo::bar', 'id' => 5, 'admin' => true]);
		$this->assertIdentical('/admin/foo/bar/5', $result);

		$result = Router::match(['Foo::bar', 'id' => 5, 'admin' => true, 'locale' => 'jp']);
		$this->assertIdentical('/admin/jp/foo/bar/5', $result);
	}

	/**
	 * Tests that continuations can be used for route suffixes.
	 */
	public function testSuffixContinuation() {
		Router::connect("/{:args}.{:type}", [], ['continue' => true]);
		Router::connect('/{:controller}/{:id:[0-9]+}', ['action' => 'view']);

		$result = Router::match([
			'controller' => 'versions',
			'action' => 'view',
			'id' => 13,
			'type' => 'jsonp'
		]);
		$this->assertIdentical('/versions/13.jsonp', $result);

		$result = Router::match([
			'controller' => 'versions',
			'action' => 'view',
			'id' => 13
		]);
		$this->assertIdentical('/versions/13', $result);
	}

	public function testRouteContinuationsWithQueryString() {
		Router::connect('/{:args}/page-{:page:[\d]+}', [], ['continue' => true]);
		Router::connect('/hello/world', 'Hello::world');

		$expected = '/hello/world/page-2?foo=bar';

		$result = Router::match(['Hello::world', 'page' => 2, '?' => ['foo' => 'bar']]);
		$this->assertEqual($expected, $result);
	}

	public function testRouteContinuationsOnRootUrl() {
		Router::connect('/{:args}/page-{:page:[\d]+}', [], ['continue' => true]);
		Router::connect('/', 'Home::index');

		$expected = '/page-1';

		$result = Router::match(['Home::index', 'page' => 1]);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests default route formatters, and setting/getting new formatters.
	 */
	public function testRouteFormatters() {
		$formatters = Router::formatters();
		$this->assertEqual(['args', 'controller'], array_keys($formatters));

		$this->assertIdentical('foo/bar', $formatters['args'](['foo', 'bar']));
		$this->assertIdentical('list_items', $formatters['controller']('ListItems'));

		Router::formatters(['action' => function($value) { return strtolower($value); }]);
		$formatters = Router::formatters();
		$this->assertEqual(['action', 'args', 'controller'], array_keys($formatters));

		Router::formatters(['action' => null]);
		$formatters = Router::formatters();
		$this->assertEqual(['args', 'controller'], array_keys($formatters));
	}

	public function testRouteFormattersAppliedOnMatch() {
		Router::reset();
		Router::connect('/{:controller:lists}/{:action:add}');
		$this->assertIdentical(
			'/lists/add',
			Router::match(['controller' => 'lists', 'action' => 'add'])
		);

		Router::connect('/lists/{:action:add}', ['controller' => 'lists']);
		$this->assertIdentical(
			'/lists/add',
			Router::match(['controller' => 'lists', 'action' => 'add'])
		);
	}

	public function testRouteModifiers() {
		$modifiers = Router::modifiers();
		$this->assertEqual(['args', 'controller'], array_keys($modifiers));

		$this->assertEqual(['foo', 'bar'], $modifiers['args']('foo/bar'));
		$this->assertIdentical('HelloWorld', $modifiers['controller']('hello_world'));
	}

	public function testAttachAbsolute() {
		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => 'http',
			'prefix' => 'web/tests'
		]);

		$result = Router::attached('app', [
			'subdomain' => 'admin',
			'domain' => 'myserver',
			'tld' => 'com'
		]);

		$expected = [
			'absolute' => true,
			'host' => 'admin.myserver.com',
			'scheme' => 'http',
			'base' => null,
			'prefix' => 'web/tests',
			'pattern' => '@^http://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/web/tests/@',
			'library' => 'app',
			'params' => ['subdomain', 'domain', 'tld'],
			'values' => []
		];
		$this->assertEqual($expected, $result);
	}

	public function testAttachAbsoluteWithHttps() {
		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'prefix' => ''
		]);

		$result = Router::attached('app', [
			'scheme' => 'https',
			'subdomain' => 'admin',
			'domain' => 'myserver',
			'tld' => 'co.uk'
		]);

		$expected = [
			'absolute' => true,
			'host' => 'admin.myserver.co.uk',
			'scheme' => 'https',
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(?P<scheme>https)://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/@',
			'library' => 'app',
			'params' => ['scheme', 'subdomain', 'domain', 'tld'],
			'values' => []
		];
		$this->assertEqual($expected, $result);
	}

	public function testCompileScopeAbsolute() {
		$result = MockRouter::compileScope([
			'absolute' => true
		]);
		$expected = [
			'absolute' => true,
			'host' => null,
			'scheme' => null,
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(.*?)//localhost/@',
			'params' => []
		];
		$this->assertEqual($expected, $result);
	}

	public function testCompileScopeAbsoluteWithPrefix() {
		$result = MockRouter::compileScope([
			'absolute' => true,
			'host' => 'www.hostname.com',
			'scheme' => 'http',
			'prefix' => 'web/tests'
		]);

		$expected = [
			'absolute' => true,
			'host' => 'www.hostname.com',
			'scheme' => 'http',
			'base' => null,
			'prefix' => 'web/tests',
			'pattern' => '@^http://www\.hostname\.com/web/tests/@',
			'params' => []
		];
		$this->assertEqual($expected, $result);
	}

	public function testCompileScopeAbsoluteWithVariables() {
		$result = MockRouter::compileScope([
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => 'http',
			'prefix' => 'web/tests'
		]);

		$expected = [
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => 'http',
			'base' => null,
			'prefix' => 'web/tests',
			'pattern' => '@^http://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/web/tests/@',
			'params' => ['subdomain', 'domain', 'tld']
		];
		$this->assertEqual($expected, $result);

		$result = MockRouter::compileScope([
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'prefix' => ''
		]);

		$expected = [
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(?P<scheme>https)://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/@',
			'params' => ['scheme', 'subdomain', 'domain', 'tld']
		];
		$this->assertEqual($expected, $result);
	}

	public function testParseScopeWithRelativeAttachment() {
		$request = new Request([
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/'
		]);
		Router::attach('app', [
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAttachment() {
		$request = new Request([
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/'
		]);
		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.atari.com',
			'scheme' => 'http'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertFalse($result);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithRelativeAndPrefixedAttachment() {
		$request = new Request([
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/web'
		]);
		Router::attach('app', [
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAndPrefixedAttachment() {
		$request = new Request([
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/web'
		]);
		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertFalse($result);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAndPrefixAttachment() {
		$request = new Request([
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/web'
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web2'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertFalse($result);
	}

	public function testParseScopeWithRelativeAndPrefixAttachmentUsingEnvRequest() {
		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'www.amiga.com',
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/',
				'HTTPS' => false
			]
		]);

		Router::attach('app', [
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertFalse($result);

		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'www.amiga.com',
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/web',
				'HTTPS' => false
			]
		]);

		Router::attach('app', [
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAndPrefixAttachmentUsingEnvRequest() {
		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'www.amiga.com',
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/web',
				'HTTPS' => false
			]
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertFalse($result);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web2'
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertFalse($result);
	}

	public function testParseScopeWithAbsoluteAttachmentUsingVariables() {
		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'www.amiga.com',
				'HTTPS' => false,
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/'
			]
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain}.atari.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		]);
		$result = MockRouter::parseScope('app', $request);
		$this->assertFalse($result);

		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'www.amiga.com',
				'HTTPS' => false,
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/'
			]
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		]);

		$result = MockRouter::parseScope('app', $request);
		$this->assertIdentical('/', $result);
	}

	public function testMatchWithRelativeAndPrefixedAttachment() {
		Router::attach('tests', [
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix'
		]);

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('/prefix/controller/action/hello', $result);
	}

	public function testMatchWithAbsoluteAndPrefixedAttachment() {
		Router::attach('tests', [
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix'
		]);

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('http://tests.mysite.com/prefix/controller/action/hello', $result);
	}

	public function testMatchWithAbsoluteAndNoSchemeAttachment() {
		Router::attach('tests', [
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => null
		]);

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('http://tests.mysite.com/controller/action/hello', $result);

		Router::attach('tests', [
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => false
		]);

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('//tests.mysite.com/controller/action/hello', $result);
	}

	public function testMatchWithRelativeAndPrefixedAttachmentUsingBasedRequest() {
		$request = new Request(['base' => '/request/base']);
		Router::attach('tests', [
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http://',
			'prefix' => 'prefix'
		]);

		Router::scope('tests');
		$result = Router::match('/controller/action/hello', $request);
		$this->assertIdentical('/request/base/prefix/controller/action/hello', $result);
	}

	public function testMatchWithAbsoluteAndPrefixedAttachmentUsingBasedRequest() {
		$request = new Request(['base' => '/requestbase']);
		Router::attach('app', [
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http',
			'prefix' => 'prefix'
		]);

		$result = Router::match('/controller/action/hello', $request, ['scope' => 'app']);
		$expected = 'http://app.mysite.com/requestbase/prefix/controller/action/hello';
		$this->assertIdentical($expected, $result);
	}

	public function testMatchWithAbsoluteAttachmentUsingDoubleColonNotation() {
		Router::attach('tests', [
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => 'prefix'
		]);

		Router::scope('tests', function() {
			Router::connect('/user/view/{:args}', ['User::view']);
		});

		Router::scope('tests');
		$result = Router::match([
			'User::view', 'args' => 'bob'
		], null, ['scope' => 'tests']);
		$this->assertIdentical('http://tests.mysite.com/prefix/user/view/bob', $result);

		Router::reset();

		Router::attach('tests', [
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => 'prefix',
			'namespace' => 'app\controllers'
		]);

		Router::scope('tests', function() {
			Router::connect('/users/view/{:args}', ['Users::view']);
		});

		Router::scope('tests');
		$result = Router::match([
			'Users::view', 'args' => 'bob'
		], null, ['scope' => 'tests']);
		$this->assertIdentical('http://tests.mysite.com/prefix/users/view/bob', $result);
	}

	public function testMatchWithAbsoluteAttachmentAndVariables() {
		$request = new Request([
			'url' => '/home/index',
			'host' => 'bob.amiga.com',
			'base' => ''
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		]);

		Router::scope('app', function() {
			Router::connect('/home/index', ['Home::index']);
		});

		Router::process($request);
		$expected = 'http://bob.amiga.com/home/index';
		$result = Router::match('/home/index', $request);

		$this->assertIdentical($expected, $result);

		$expected = 'http://bob.amiga.com/home/index';
		$result = Router::match('/home/index', $request, ['scope' => 'app']);
		$this->assertIdentical($expected, $result);

		$expected = 'http://max.amiga.com/home/index';
		$result = Router::match('/home/index', $request, [
			'scope' => [
				'subdomain' => 'max'
			]
		]);
		$this->assertIdentical($expected, $result);

		Router::scope(false);
		$result = Router::match('/home/index', $request, [
			'scope' => [
				'app' => [
					'subdomain' => 'max'
				]
			]
		]);
		$this->assertIdentical($expected, $result);

		$result = Router::match('/home/index', $request, [
			'scope' => [
				'subdomain' => 'max'
			]
		]);
		$this->assertNotEqual($expected, $result);
	}

	public function testMatchWithAttachementPopulatedFromRequest() {
		$request = new Request([
			'host' => 'request.mysite.com',
			'scheme' => 'https',
			'base' => 'request/base'
		]);
		Router::attach('app', ['absolute' => true]);
		Router::scope('app');
		$result = Router::match('/controller/action/hello', $request);
		$expected = 'https://request.mysite.com/request/base/controller/action/hello';
		$this->assertIdentical($expected, $result);

		Router::scope(false);
		$result = Router::match('/controller/action/hello', $request);
		$this->assertIdentical('/request/base/controller/action/hello', $result);
	}

	public function testUnexistingScopedRoute() {
		Router::scope('tests', function() {
			Router::connect('/user/view/{:args}', ['User::view']);
		});
		Router::scope('tests');

		$ex = "No parameter match found for URL `('controller' => 'User', ";
		$ex .= "'action' => 'view', 'args' => 'bob')` in `app` scope.";
		$this->assertException($ex, function() {
			Router::match([
				'User::view', 'args' => 'bob'
			], null, ['scope' => 'app']);
		});
	}

	public function testMatchWithNoRouteDefined() {
		$ex = "No parameter match found for URL `('controller' => 'User', ";
		$ex .= "'action' => 'view', 'args' => 'bob')` in `app` scope.";
		$this->assertException($ex, function() {
			Router::match([
				'User::view', 'args' => 'bob'
			], null, ['scope' => 'app']);
		});
	}

	public function testProcessWithAbsoluteAttachment() {
		Router::connect('/home/welcome', ['Home::app'], ['scope' => 'app']);
		Router::connect('/home/welcome', ['Home::tests'], ['scope' => 'tests']);
		Router::connect('/home/welcome', ['Home::default']);

		Router::attach('tests', [
			'absolute' => true,
			'host' => 'tests.mysite.com'
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'app.mysite.com'
		]);

		$request = new Request([
			'url' => '/home/welcome',
			'host' => 'www.mysite.com'
		]);
		$result = Router::process($request);
		$expected = [
			'controller' => 'Home',
			'action' => 'default'
		];
		$this->assertEqual($expected, $result->params);

		$request->host = 'tests.mysite.com';
		$result = Router::process($request);
		$expected = [
			'library' => 'tests',
			'controller' => 'Home',
			'action' => 'tests'
		];
		$this->assertEqual($expected, $result->params);

		$request->host = 'app.mysite.com';
		$result = Router::process($request);
		$expected = [
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'app'
		];
		$this->assertEqual($expected, $result->params);
	}

	public function testProcessWithRelativeAndPrefixedAttachment() {
		Router::scope('app', function() {
			Router::connect('/home/welcome', ['Home::app']);
		});

		Router::scope('tests', function() {
			Router::connect('/home/welcome', ['Home::tests']);
		});

		Router::connect('/home/welcome', ['Home::default']);

		Router::attach('tests', [
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix'
		]);

		Router::attach('app', [
			'absolute' => false,
			'host' => 'app.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix'
		]);

		$request = new Request(['url' => '/home/welcome']);
		$result = Router::process($request);
		$expected = [
			'controller' => 'Home',
			'action' => 'default'
		];
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical(false, Router::scope());

		Router::reset();

		Router::scope('tests', function() {
			Router::connect('/home/welcome', ['Home::tests']);
		});

		Router::scope('app', function() {
			Router::connect('/home/welcome', ['Home::app']);
		});

		Router::connect('/home/welcome', ['Home::default']);

		Router::attach('tests', [
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix1'
		]);

		Router::attach('app', [
			'absolute' => false,
			'host' => 'app.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix2'
		]);

		$request = new Request(['url' => '/prefix1/home/welcome']);
		$result = Router::process($request);
		$expected = [
			'library' => 'tests',
			'controller' => 'Home',
			'action' => 'tests'
		];
		$this->assertEqual($expected, $result->params);
		$this->assertEqual('tests', Router::scope());
	}

	public function testProcessWithAbsoluteAndPrefixedAttachment() {
		Router::scope('app', function() {
			Router::connect('/home/welcome', ['Home::app']);
		});

		Router::scope('tests', function() {
			Router::connect('/home/welcome', ['Home::tests']);
		});

		Router::connect('/home/welcome', ['Home::default']);

		Router::attach('tests', [
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix1'
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix2'
		]);

		$request = new Request([
			'host' => 'app.mysite.com',
			'url' => '/home/welcome'
		]);

		$result = Router::process($request);
		$expected = [
			'controller' => 'Home',
			'action' => 'default'
		];
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical(false, Router::scope());

		$request = new Request([
			'host' => 'app.mysite.com',
			'url' => '/prefix2/home/welcome'
		]);

		$result = Router::process($request);
		$expected = [
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'app'
		];
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical('app', Router::scope());

		$request = new Request([
			'host' => 'app.mysite.com',
			'url' => '/prefix1/home/welcome'
		]);

		$result = Router::process($request);
		$expected = [
			'library' => 'tests',
			'controller' => 'Home',
			'action' => 'tests'
		];
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical('tests', Router::scope());
	}

	public function testProcessWithAbsoluteHttpsAttachment() {
		Router::scope('app', function() {
			Router::connect('/home/welcome', ['Home::app']);
		});

		Router::scope('tests', function() {
			Router::connect('/home/welcome', ['Home::tests']);
		});

		Router::connect('/home/welcome', ['Home::default']);

		Router::attach('tests', [
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http'
		]);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http'
		]);

		$request = new Request([
			'host' => 'app.mysite.com',
			'scheme' => 'https',
			'url' => '/home/welcome'
		]);

		$result = Router::process($request);
		$expected = [
			'controller' => 'Home',
			'action' => 'default'
		];
		$this->assertEqual($expected, $result->params);

		Router::attach('app', [
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'https'
		]);

		$result = Router::process($request);
		$expected = [
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'app'
		];
		$this->assertEqual($expected, $result->params);
	}

	public function testProcessWithAbsoluteAttachmentAndVariables() {
		$request = new Request([
			'url' => '/home/index',
			'prefix' => '',
			'env' => [
				'HTTP_HOST' => 'bob.amiga.com',
				'HTTPS' => false
			]
		]);
		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		]);

		Router::scope('app', function() {
			Router::connect('/home/index', ['Home::index']);
		});

		Router::process($request);
		$expected = [
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'index',
			'subdomain' => 'bob',
			'tld' => 'com'
		];
		$this->assertEqual($expected, $request->params);

		$result = Router::attached('app');
		$this->assertEqual($expected, $result['values']);
	}

	public function testProcessWithAbsoluteAttachmentAndLibrary() {
		$request = new Request([
			'url' => '/hello_world/index',
			'env' => [
				'HTTP_HOST' => 'bob.amiga.com',
				'HTTPS' => false
			]
		]);
		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'library' => 'other'
		]);

		Router::scope('app', function() {
			Router::connect('/hello_world/index', ['HelloWorld::index']);
			Router::connect('/{:controller}/{:action}');
		});

		Router::process($request);
		$expected = [
			'library' => 'other',
			'controller' => 'HelloWorld',
			'action' => 'index',
			'subdomain' => 'bob',
			'tld' => 'com'
		];
		$this->assertEqual($expected, $request->params);

		$result = Router::attached('app');
		$this->assertEqual($expected, $result['values']);

		$request = new Request([
			'url' => '/posts/index',
			'env' => [
				'HTTP_HOST' => 'bob.amiga.com',
				'HTTPS' => false
			]
		]);

		Router::process($request);
		$expected = [
			'library' => 'other',
			'controller' => 'Posts',
			'action' => 'index',
			'subdomain' => 'bob',
			'tld' => 'com'
		];
		$this->assertEqual($expected, $request->params);

		$result = Router::attached('app');
		$this->assertEqual($expected, $result['values']);
	}

	public function testParseWithNotValidSchemeVariable() {
		Router::attach('app', [
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'prefix' => ''
		]);

		$result = Router::attached('app', [
			'scheme' => 'http',
			'subdomain' => 'admin',
			'domain' => 'myserver',
			'tld' => 'co.uk'
		]);

		$expected = [
			'absolute' => true,
			'host' => 'admin.myserver.co.uk',
			'scheme' => '{:scheme:https}',
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(?P<scheme>https)://(?P<subdomain>[a-z]+)\\.' .
			             '(?P<domain>[^/]+?)\\.(?P<tld>[^/]+?)/@',
			'library' => 'app',
			'params' => ['scheme', 'subdomain', 'domain', 'tld'],
			'values' => []
		];
		$this->assertEqual($expected, $result);
	}

	public function testRouteRetrievalWithScope() {
		Router::scope('loc1', function() use (&$expected){
			$expected = Router::connect('/hello', [
				'controller' => 'Posts',
				'action' => 'index'
			]);
		});

		$result = Router::get(0, true);
		$this->assertIdentical(null, $result);

		$result = Router::get(0, 'loc1');
		$this->assertIdentical($expected, $result);

		Router::scope('loc1', function() {
			Router::connect('/helloworld', [
				'controller' => 'Posts',
				'action' => 'index'
			]);
		});

		Router::scope('loc2', function() {
			Router::connect('/hello', [
				'controller' => 'Posts',
				'action' => 'index'
			]);
		});

		$this->assertCount(0, Router::get(null, true));

		$result = count(Router::get(null, 'loc1'));
		$this->assertCount(2, Router::get(null, 'loc1'));

		$scopes = Router::get();
		$result = 0;
		foreach ($scopes as $routes) {
			$result += count($routes);
		}
		$this->assertIdentical(3, $result);
	}

	public function testListAttached() {
		Router::attach('scope1', ['prefix' => 'scope1', 'absolute' => true]);
		Router::attach('scope2', ['prefix' => 'scope2', 'library' => 'app']);
		Router::attach('scope3', ['prefix' => 'scope3']);

		$expected = [
			'scope1' => [
				'prefix' => 'scope1',
				'absolute' => true,
				'host' => null,
				'scheme' => null,
				'base' => null,
				'pattern' => '@^(.*?)//localhost/scope1/@',
				'library' => 'scope1',
				'values' => [],
				'params' => []
			],
			'scope2' => [
				'prefix' => 'scope2',
				'library' => 'app',
				'absolute' => false,
				'host' => null,
				'scheme' => null,
				'base' => null,
				'pattern' => '@^/scope2/@',
				'values' => [],
				'params' => []
			],
			'scope3' => [
				'prefix' => 'scope3',
				'absolute' => false,
				'host' => null,
				'scheme' => null,
				'base' => null,
				'pattern' => '@^/scope3/@',
				'library' => 'scope3',
				'values' => [],
				'params' => []
			]
		];
		$this->assertEqual($expected, Router::attached());
	}

	public function testScopeBase() {
		$request = new Request(['base' => 'lithium/app']);
		$url = ['controller' => 'HelloWorld'];

		Router::scope('app', function(){
			Router::connect('/{:controller}/{:action}');
		});
		Router::scope('app');

		$expected = '/lithium/app/hello_world';
		$this->assertEqual($expected, Router::match($url, $request));

		Router::attach('app', ['base' => 'lithium']);
		$expected = '/lithium/hello_world';
		$this->assertEqual($expected, Router::match($url, $request));

		Router::attach('app', ['base' => '']);
		$expected = '/hello_world';
		$this->assertEqual($expected, Router::match($url, $request));
	}

	public function testMatchOverRequestParamsWithScope() {
		Router::scope('app1', function(){
			Router::connect('/hello/world1', 'HelloApp1::index');
		});
		Router::scope('app2', function(){
			Router::connect('/hello/world2', 'HelloApp2::index');
		});

		$request = new Request(['url' => '/hello/world1']);
		$result = Router::process($request);

		$expected = ['controller' => 'HelloApp1', 'action' => 'index', 'library' => 'app1'];
		$this->assertEqual($expected, $result->params);

		$result = Router::match($result->params);
		$this->assertEqual('/hello/world1', $result);

		$request = new Request(['url' => '/hello/world2']);
		$result = Router::process($request);

		$expected = ['controller' => 'HelloApp2', 'action' => 'index', 'library' => 'app2'];
		$this->assertEqual($expected, $result->params);

		$result = Router::match($result->params);
		$this->assertEqual('/hello/world2', $result);
	}

	public function testLibraryBasedRoute() {
		$route = Router::connect('/{:library}/{:controller}/{:action}',
			['library' => 'app'],
			['persist' => ['library']]
		);

		$expected = '/app/hello/world';
		$result = Router::match(['controller' => 'hello', 'action' => 'world']);
		$this->assertEqual($expected, $result);

		$expected = '/myapp/hello/world';
		$result = Router::match([
			'library' => 'myapp', 'controller' => 'hello', 'action' => 'world'
		]);
		$this->assertEqual($expected, $result);
	}

	public function testMatchWithAbsoluteScope() {
		Router::attach('app', [
			'absolute' => true,
			'host' => '{:domain}',
		]);

		Router::scope('app', function(){
			Router::connect('/hello', 'Posts::index');
		});

		$request = new Request(['url' => '/hello', 'base' => '']);
		$result = Router::process($request);

		$expected = 'http://' . $result->params['domain'] . '/hello';
		$result = Router::match($result->params, $request);

		$this->assertEqual($expected, $result);
	}

	public function testMatchWithScopeAndWithoutController() {
		Router::scope('app', function() {
			Router::connect('/{:id}', 'Posts::index');
		});

		$request = new Request(['url' => '/1', 'base' => '']);
		MockDispatcher::run($request);

		$result = Router::match([
			'id' => 2
		], $request);

		$this->assertEqual('/2', $result);
	}

	public function testDisableScopeAutoLibraryFeature() {
		$request = new Request(['url' => '/', 'base' => '']);

		Router::attach('admin', [
			'prefix' => 'admin',
			'absolute' => false,
			'library' => false
		]);
		Router::scope('admin', function() {
			Router::connect('/foo/posts/{:id}', [
				'controller' => 'Posts', 'action' => 'index',
				'library' => 'admin_foo'
			]);
		});
		$result = Router::match([
			'controller' => 'Posts', 'action' => 'index',
			'id' => 23,
			'library' => 'admin_foo'
		], $request, ['scope' => 'admin']);

		$this->assertEqual('/admin/foo/posts/23', $result);
	}
}

?>