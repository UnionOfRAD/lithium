<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use lithium\action\Request;
use lithium\net\http\Router;
use lithium\action\Response;

class RouterTest extends \lithium\test\Unit {

	public $request = null;

	public function setUp() {
		$this->request = new Request();
	}

	public function tearDown() {
		Router::reset();
	}

	public function testBasicRouteConnection() {
		$result = Router::connect('/hello', array('controller' => 'Posts', 'action' => 'index'));
		$expected = array(
			'template' => '/hello',
			'pattern' => '@^/hello$@u',
			'params' => array('controller' => 'Posts', 'action' => 'index'),
			'match' => array('controller' => 'Posts', 'action' => 'index'),
			'meta' => array(),
			'persist' => array('controller'),
			'defaults' => array(),
			'keys' => array(),
			'subPatterns' => array(),
			'handler' => null
		);
		$this->assertEqual($expected, $result->export());

		$result = Router::connect('/{:controller}/{:action}', array('action' => 'view'));
		$this->assertInstanceOf('lithium\net\http\Route', $result);
		$expected = array(
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@u',
			'params' => array('action' => 'view'),
			'defaults' => array('action' => 'view'),
			'match' => array(),
			'meta' => array(),
			'persist' => array('controller'),
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'subPatterns' => array(),
			'handler' => null
		);
		$this->assertEqual($expected, $result->export());
	}

	/**
	 * Tests generating routes with required parameters which are not present in the URL.
	 */
	public function testConnectingWithRequiredParams() {
		$result = Router::connect('/{:controller}/{:action}', array(
			'action' => 'view', 'required' => true
		));
		$expected = array(
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@u',
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'params' => array('action' => 'view', 'required' => true),
			'defaults' => array('action' => 'view'),
			'match' => array('required' => true),
			'meta' => array(),
			'persist' => array('controller'),
			'subPatterns' => array(),
			'handler' => null
		);
		$this->assertEqual($expected, $result->export());
	}

	public function testConnectingWithDefaultParams() {
		$result = Router::connect('/{:controller}/{:action}', array('action' => 'archive'));
		$expected = array(
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\/]+))(?:/(?P<action>[^\/]+)?)?$@u',
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'params' => array('action' => 'archive'),
			'match' => array(),
			'meta' => array(),
			'persist' => array('controller'),
			'defaults' => array('action' => 'archive'),
			'subPatterns' => array(),
			'handler' => null
		);
		$this->assertEqual($expected, $result->export());
	}

	/**
	 * Tests basic options for connecting routes.
	 */
	public function testBasicRouteMatching() {
		Router::connect('/hello', array('controller' => 'Posts', 'action' => 'index'));
		$expected = array('controller' => 'Posts', 'action' => 'index');

		foreach (array('/hello/', '/hello', 'hello/', 'hello') as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result->params);
			$this->assertEqual(array('controller'), $result->persist);
		}
	}

	public function testRouteMatchingWithDefaultParameters() {
		Router::connect('/{:controller}/{:action}', array('action' => 'view'));
		$expected = array('controller' => 'Posts', 'action' => 'view');

		foreach (array('/posts/view', '/posts', 'posts', 'posts/view', 'posts/view/') as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result->params);
			$this->assertEqual(array('controller'), $result->persist);
		}
		$expected['action'] = 'index';

		foreach (array('/posts/index', 'posts/index', 'posts/index/') as $url) {
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
		Router::connect('/login', array('controller' => 'sessions', 'action' => 'create'));
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
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', array('id' => null));

		$result = Router::match(array('Posts::edit', '#' => 'foo'));
		$this->assertIdentical('/posts/edit#foo', $result);

		$result = Router::match(array('Posts::edit', 'id' => 42, '#' => 'foo'));
		$this->assertIdentical('/posts/edit/42#foo', $result);

		$result = Router::match(array('controller' => 'users', 'action' => 'view', '#' => 'blah'));
		$this->assertIdentical('/users/view#blah', $result);

		$result = Router::match(array(
			'controller' => 'users', 'action' => 'view', 'id' => 47, '#' => 'blargh'
		));
		$this->assertIdentical('/users/view/47#blargh', $result);
	}

	public function testQueryString() {
		Router::connect('/{:controller}/{:action}');
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', array('id' => null));

		$result = Router::match(array('Posts::edit', '?' => array('key' => 'value')));
		$this->assertIdentical('/posts/edit?key=value', $result);

		$result = Router::match(array(
			'Posts::edit', 'id' => 42, '?' => array('key' => 'value', 'test' => 'foo')
		));
		$this->assertIdentical('/posts/edit/42?key=value&test=foo', $result);
	}

	/**
	 * Tests that URLs specified as "Controller::action" and including additional parameters are
	 * interpreted properly.
	 */
	public function testEmbeddedStringActions() {
		Router::connect('/logout/{:id:[0-9]{5,6}}', array(
			'controller' => 'sessions', 'action' => 'destroy', 'id' => null
		));
		Router::connect('/{:controller}/{:action}');
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', array('id' => null));

		$result = Router::match("Sessions::create");
		$this->assertIdentical('/sessions/create', $result);

		$result = Router::match(array("Sessions::create"));
		$this->assertIdentical('/sessions/create', $result);

		$result = Router::match(array("Sessions::destroy", 'id' => '03815'));
		$this->assertIdentical('/logout/03815', $result);

		$result = Router::match("Posts::index");
		$this->assertIdentical('/posts', $result);

		$ex = "No parameter match found for URL ";
		$ex .= "`('controller' => 'Sessions', 'action' => 'create', 'id' => 'foo')`.";
		$this->expectException($ex);
		$result = Router::match(array("Sessions::create", 'id' => 'foo'));
	}

	/**
	 * Tests that routes can be created with shorthand strings, i.e. `'Controller::action'` and
	 * `array('Controller::action', 'id' => '...')`.
	 */
	public function testStringParameterConnect() {
		Router::connect('/posts/{:id:[0-9a-f]{24}}', 'Posts::edit');

		$result = Router::match(array(
			'controller' => 'posts', 'action' => 'edit', 'id' => '4bbf25bd8ead0e5180130000'
		));
		$expected = '/posts/4bbf25bd8ead0e5180130000';
		$this->assertIdentical($expected, $result);

		$ex = "No parameter match found for URL `(";
		$ex .= "'controller' => 'Posts', 'action' => 'view', 'id' => '4bbf25bd8ead0e5180130000')`.";
		$this->expectException($ex);
		$result = Router::match(array(
			'controller' => 'posts', 'action' => 'view', 'id' => '4bbf25bd8ead0e5180130000'
		));
		$this->assertFalse(ob_get_length());
	}

	public function testShorthandParameterMatching() {
		Router::reset();
		Router::connect('/posts/{:page:[0-9]+}', array('Posts::index', 'page' => '1'));
		Router::connect('/admin/posts/{:page:[0-9]+}', array('Posts::index', 'page' => '1', 'library' => 'admin'));

		$result = Router::match(array('controller' => 'posts', 'page' => '5'));
		$expected = '/posts/5';
		$this->assertIdentical($expected, $result);

		$result = Router::match(array('Posts::index', 'page' => '10'));
		$expected = '/posts/10';
		$this->assertIdentical($expected, $result);

		$result = Router::match(array("admin.Posts::index", 'page' => '9'));
		$expected = '/admin/posts/9';
		$this->assertEqual($expected, $result);

		$request = new Request(array('url' => '/posts/13'));
		$result = Router::process($request);
		$expected = array('controller' => 'Posts', 'action' => 'index', 'page' => '13');
		$this->assertEqual($expected, $result->params);
	}

	/**
	 * Tests that routing is fully reset when calling `Router::reset()`.
	 */
	public function testResettingRoutes() {
		Router::connect('/{:controller}', array('controller' => 'Posts'));
		$this->request->url = '/hello';

		$expected = array('controller' => 'Hello', 'action' => 'index');
		$result = Router::parse($this->request);
		$this->assertEqual($expected, $result->params);

		Router::reset();
		$this->assertNull(Router::parse($this->request));
	}

	/**
	 * Tests matching routes where the route template is a static string with no insert parameters.
	 */
	public function testRouteMatchingWithNoInserts() {
		Router::connect('/login', array('controller' => 'sessions', 'action' => 'add'));
		$result = Router::match(array('controller' => 'sessions', 'action' => 'add'));
		$this->assertIdentical('/login', $result);

		$this->expectException(
			"No parameter match found for URL `('controller' => 'Sessions', 'action' => 'index')`."
		);
		Router::match(array('controller' => 'sessions', 'action' => 'index'));
	}

	/**
	 * Test matching routes with only insert parameters and no default values.
	 */
	public function testRouteMatchingWithOnlyInserts() {
		Router::connect('/{:controller}');
		$this->assertIdentical('/posts', Router::match(array('controller' => 'posts')));

		$this->expectException(
			"No parameter match found for URL `('controller' => 'Posts', 'action' => 'view')`."
		);
		Router::match(array('controller' => 'posts', 'action' => 'view'));
	}

	/**
	 * Test matching routes with insert parameters which have default values.
	 */
	public function testRouteMatchingWithInsertsAndDefaults() {
		Router::connect('/{:controller}/{:action}', array('action' => 'archive'));
		$this->assertIdentical('/posts/index', Router::match(array('controller' => 'posts')));

		$result = Router::match(array('controller' => 'posts', 'action' => 'archive'));
		$this->assertIdentical('/posts', $result);

		Router::reset();
		Router::connect('/{:controller}/{:action}', array('controller' => 'users'));

		$result = Router::match(array('action' => 'view'));
		$this->assertIdentical('/users/view', $result);

		$result = Router::match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertIdentical('/posts/view', $result);

		$ex = "No parameter match found for URL ";
		$ex .= "`('controller' => 'Posts', 'action' => 'view', 'id' => '2')`.";
		$this->expectException($ex);
		Router::match(array('controller' => 'posts', 'action' => 'view', 'id' => '2'));
	}

	/**
	 * Tests matching routes and returning an absolute (protocol + hostname) URL.
	 */
	public function testRouteMatchAbsoluteUrl() {
		Router::connect('/login', array('controller' => 'sessions', 'action' => 'add'));
		$result = Router::match('Sessions::add', $this->request);
		$base = $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add', $this->request, array('absolute' => true));
		$base  = $this->request->env('HTTPS') ? 'https://' : 'http://';
		$base .= $this->request->env('HTTP_HOST');
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add',
			$this->request, array('host' => 'test.local', 'absolute' => true)
		);
		$base = $this->request->env('HTTPS') ? 'https://' : 'http://';
		$base .= 'test.local';
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add',
			$this->request, array('scheme' => 'https://', 'absolute' => true)
		);
		$base = 'https://' . $this->request->env('HTTP_HOST');
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);

		$result = Router::match('Sessions::add',
			$this->request, array('scheme' => 'https://', 'absolute' => true)
		);
		$base = 'https://' . $this->request->env('HTTP_HOST');
		$base .= $this->request->env('base');
		$this->assertIdentical($base . '/login', $result);
	}

	/**
	 * Tests getting routes using `Router::get()`, and checking to see if the routes returned match
	 * the routes connected.
	 */
	public function testRouteRetrieval() {
		$expected = Router::connect('/hello', array('controller' => 'posts', 'action' => 'index'));
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

		$request = new Request(array('base' => '/my/web/path'));
		$result = Router::match('/posts', $request);
		$expected = '/my/web/path/posts';
		$this->assertIdentical($expected, $result);

		$request = new Request(array('base' => '/my/web/path'));
		$result = Router::match('/some/where', $request, array('absolute' => true));
		$prefix  = $this->request->env('HTTPS') ? 'https://' : 'http://';
		$prefix .= $this->request->env('HTTP_HOST');
		$this->assertIdentical($prefix . '/my/web/path/some/where', $result);

		$result = Router::match('mailto:foo@localhost');
		$expected = 'mailto:foo@localhost';
		$this->assertIdentical($expected, $result);

		$result = Router::match('#top');
		$expected = '#top';
		$this->assertIdentical($expected, $result);
	}

	public function testJavaScriptUrlGeneration() {
		$result = Router::match('javascript:alert(1)');
		$expected = 'javascript:alert(1)';
		$this->assertEqual($expected, $result);
	}

	public function testWithWildcardString() {
		Router::connect('/add/{:args}', array('controller' => 'tests', 'action' => 'add'));

		$expected = '/add';
		$result = Router::match('/add');
		$this->assertIdentical($expected, $result);

		$expected = '/add/alke';
		$result = Router::match('/add/alke');
		$this->assertIdentical($expected, $result);
	}

	public function testWithWildcardArray() {
		Router::connect('/add/{:args}', array('controller' => 'tests', 'action' => 'add'));

		$expected = '/add';
		$result = Router::match(array('controller' => 'tests', 'action' => 'add'));
		$this->assertIdentical($expected, $result);

		$expected = '/add/alke';
		$result = Router::match(array(
			'controller' => 'tests', 'action' => 'add', 'args' => array('alke')
		));
		$this->assertIdentical($expected, $result);

		$expected = '/add/alke/php';
		$result = Router::match(array(
			'controller' => 'tests', 'action' => 'add', 'args' => array('alke', 'php')
		));
		$this->assertIdentical($expected, $result);
	}

	public function testProcess() {
		Router::connect('/add/{:args}', array('controller' => 'tests', 'action' => 'add'));
		$request = Router::process(new Request(array('url' => '/add/foo/bar')));

		$params = array('controller' => 'Tests', 'action' => 'add', 'args' => array('foo', 'bar'));
		$this->assertEqual($params, $request->params);
		$this->assertEqual(array('controller'), $request->persist);

		$request = Router::process(new Request(array('url' => '/remove/foo/bar')));
		$this->assertEmpty($request->params);
	}

	public function testActionWithProcess() {
		Router::connect('/{:controller}/{:action}/{:args}');
		$request = Router::process(new Request(array('url' => '/users/toJson')));

		$params = array('controller' => 'Users', 'action' => 'toJson', 'args' => array());
		$this->assertEqual($params, $request->params);

		$request = Router::process(new Request(array('url' => '/users/to_json')));

		$params = array('controller' => 'Users', 'action' => 'to_json', 'args' => array());
		$this->assertEqual($params, $request->params);
	}

	/**
	 * Tests that the order of the parameters is respected so it can trim
	 * the URL correctly.
	 */
	public function testParameterOrderIsRespected() {
		Router::connect('/{:locale}/{:controller}/{:action}/{:args}');
		Router::connect('/{:controller}/{:action}/{:args}');

		$request = Router::process(new Request(array('url' => 'posts')));

		$url = Router::match('Posts::index', $request);
		$this->assertIdentical($this->request->env('base') . '/posts', $url);

		$request = Router::process(new Request(array('url' => 'fr/posts')));

		$params = array('Posts::index', 'locale' => 'fr');
		$url = Router::match($params, $request);
		$this->assertIdentical($this->request->env('base') . '/fr/posts', $url);
	}

	/**
	 * Tests that a request context with persistent parameters generates URLs where those parameters
	 * are properly taken into account.
	 */
	public function testParameterPersistence() {
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', array(), array(
			'persist' => array('controller', 'id')
		));

		// URLs generated with $request will now have the 'controller' and 'id'
		// parameters copied to new URLs.
		$request = Router::process(new Request(array('url' => 'posts/view/1138')));

		$params = array('action' => 'edit');
		$url = Router::match($params, $request); // Returns: '/posts/edit/1138'
		$this->assertIdentical($this->request->env('base') . '/posts/edit/1138', $url);

		Router::connect(
			'/add/{:args}',
			array('controller' => 'tests', 'action' => 'add'),
			array('persist' => array('controller', 'action'))
		);
		$request = Router::process(new Request(array('url' => '/add/foo/bar', 'base' => '')));
		$path = Router::match(array('args' => array('baz', 'dib')), $request);
		$this->assertIdentical('/add/baz/dib', $path);
	}

	/**
	 * Tests that persistent parameters can be overridden with nulled-out values.
	 */
	public function testOverridingPersistentParameters() {
		Router::connect(
			'/admin/{:controller}/{:action}',
			array('admin' => true),
			array('persist' => array('admin', 'controller'))
		);
		Router::connect('/{:controller}/{:action}');

		$request = Router::process(new Request(array('url' => '/admin/posts/add', 'base' => '')));
		$expected = array('controller' => 'Posts', 'action' => 'add', 'admin' => true);
		$this->assertEqual($expected, $request->params);
		$this->assertEqual(array('admin', 'controller'), $request->persist);

		$url = Router::match(array('action' => 'archive'), $request);
		$this->assertIdentical('/admin/posts/archive', $url);

		$url = Router::match(array('action' => 'archive', 'admin' => null), $request);
		$this->assertIdentical('/posts/archive', $url);
	}

	/**
	 * Tests passing a closure handler to `Router::connect()` to bypass or augment default
	 * dispatching.
	 */
	public function testRouteHandler() {
		Router::connect('/login', 'Users::login');

		Router::connect('/users/login', array(), function($request) {
			return new Response(array(
				'location' => array('controller' => 'Users', 'action' => 'login')
			));
		});

		$result = Router::process(new Request(array('url' => '/users/login')));
		$this->assertInstanceOf('lithium\action\Response', $result);

		$headers = array('Location' => '/login');
		$this->assertEqual($headers, $result->headers);
	}

	/**
	 * Tests that a successful match against a route with template `'/'` operating at the root of
	 * a domain never returns an empty string.
	 */
	public function testMatchingEmptyRoute() {
		Router::connect('/', 'Users::view');

		$request = new Request(array('base' => '/'));
		$url = Router::match(array('controller' => 'users', 'action' => 'view'), $request);
		$this->assertIdentical('/', $url);

		$request = new Request(array('base' => ''));
		$url = Router::match(array('controller' => 'users', 'action' => 'view'), $request);
		$this->assertIdentical('/', $url);
	}

	/**
	 * Tests routing based on content type extensions, with HTML being the default when types are
	 * not defined.
	 */
	public function testTypeBasedRouting() {
		Router::connect('/{:controller}/{:id:[0-9]+}', array(
			'action' => 'index', 'type' => 'html', 'id' => null
		));
		Router::connect('/{:controller}/{:id:[0-9]+}.{:type}', array(
			'action' => 'index', 'id' => null
		));

		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', array(
			'type' => 'html', 'id' => null
		));
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}.{:type}', array('id' => null));

		$url = Router::match(array('controller' => 'posts', 'type' => 'html'));
		$this->assertIdentical('/posts', $url);

		$url = Router::match(array('controller' => 'posts', 'type' => 'json'));
		$this->assertIdentical('/posts.json', $url);
	}

	/**
	 * Tests that routes can be connected and correctly match based on HTTP headers or method verbs.
	 */
	public function testHttpMethodBasedRouting() {
		Router::connect('/{:controller}/{:id:[0-9]+}', array(
			'http:method' => 'GET', 'action' => 'view'
		));
		Router::connect('/{:controller}/{:id:[0-9]+}', array(
			'http:method' => 'PUT', 'action' => 'edit'
		));

		$request = new Request(array('url' => '/posts/13', 'env' => array(
			'REQUEST_METHOD' => 'GET'
		)));
		$params = Router::process($request)->params;
		$expected = array('controller' => 'Posts', 'action' => 'view', 'id' => '13');
		$this->assertEqual($expected, $params);

		$this->assertIdentical('/posts/13', Router::match($params));

		$request = new Request(array('url' => '/posts/13', 'env' => array(
			'REQUEST_METHOD' => 'PUT'
		)));
		$params = Router::process($request)->params;
		$expected = array('controller' => 'Posts', 'action' => 'edit', 'id' => '13');
		$this->assertEqual($expected, $params);

		$request = new Request(array('url' => '/posts/13', 'env' => array(
			'REQUEST_METHOD' => 'POST'
		)));
		$params = Router::process($request)->params;
		$this->assertEmpty($params);
	}

	/**
	 * Tests that the class dependency configuration can be modified.
	 */
	public function testCustomConfiguration() {
		$old = Router::config();
		$config = array('classes' => array(
			'route' => 'my\custom\Route',
			'configuration' => 'lithium\net\http\Configuration'
		), 'unicode' => true);

		Router::config($config);
		$this->assertEqual($config, Router::config());

		Router::config($old);
		$this->assertEqual($old, Router::config());
	}

	/**
	 * Tests that continuation routes properly fall through and aggregate multiple route parameters.
	 */
	public function testRouteContinuations() {
		Router::connect('/{:locale:en|de|it|jp}/{:args}', array(), array('continue' => true));
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');

		$request = new Request(array('url' => '/en/posts/view/1138'));
		$result = Router::process($request)->params;
		$expected = array (
			'controller' => 'Posts', 'action' => 'view', 'id' => '1138', 'locale' => 'en'
		);
		$this->assertEqual($expected, $result);

		$request = new Request(array('url' => '/en/foo/bar/baz'));
		$this->assertNull(Router::parse($request));

		Router::reset();
		Router::connect('/{:args}/{:locale:en|de|it|jp}', array(), array('continue' => true));
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');

		$request = new Request(array('url' => '/posts/view/1138/en'));
		$result = Router::process($request)->params;
		$this->assertEqual($expected, $result);

		Router::reset();
		Router::connect('/{:locale:en|de|it|jp}/{:args}', array(), array('continue' => true));
		Router::connect('/', 'Pages::view');

		$request = new Request(array('url' => '/en'));
		$result = Router::process($request)->params;
		$expected = array('locale' => 'en', 'controller' => 'Pages', 'action' => 'view');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that URLs are properly generated with route continuations.
	 */
	public function testReversingContinuations() {
		Router::connect('/{:locale:en|de|it|jp}/{:args}', array(), array('continue' => true));
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}');
		Router::connect('/{:controller}/{:action}/{:args}');

		$result = Router::match(array('Posts::view', 'id' => 5, 'locale' => 'de'));
		$this->assertEqual($result, '/de/posts/view/5');

		$result = Router::match(array('Posts::index', 'locale' => 'en', '?' => array('page' => 2)));
		$this->assertIdentical('/en/posts?page=2', $result);

		Router::reset();
		Router::connect('/{:locale:en|de|it|jp}/{:args}', array(), array('continue' => true));
		Router::connect('/pages/{:args}', 'Pages::view');

		$result = Router::match(array('Pages::view', 'locale' => 'en', 'args' => array('about')));
		$this->assertIdentical('/en/pages/about', $result);

		Router::reset();
		Router::connect('/admin/{:args}', array('admin' => true), array('continue' => true));
		Router::connect('/login', 'Users::login');

		$result = Router::match(array('Users::login', 'admin' => true));
		$this->assertIdentical('/admin/login', $result);
	}

	/**
	 * Tests that multiple continuation routes can be applied to the same URL.
	 */
	public function testStackedContinuationRoutes() {
		Router::connect('/admin/{:args}', array('admin' => true), array('continue' => true));
		Router::connect('/{:locale:en|de|it|jp}/{:args}', array(), array('continue' => true));
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', array('id' => null));

		$request = new Request(array('url' => '/en/foo/bar/5'));
		$expected = array('controller' => 'Foo', 'action' => 'bar', 'id' => '5', 'locale' => 'en');
		$this->assertEqual($expected, Router::process($request)->params);

		$request = new Request(array('url' => '/admin/foo/bar/5'));
		$expected = array('controller' => 'Foo', 'action' => 'bar', 'id' => '5', 'admin' => true);
		$this->assertEqual($expected, Router::process($request)->params);

		$request = new Request(array('url' => '/admin/de/foo/bar/5'));
		$expected = array(
			'controller' => 'Foo', 'action' => 'bar', 'id' => '5', 'locale' => 'de', 'admin' => true
		);
		$this->assertEqual($expected, Router::process($request)->params);

		$request = new Request(array('url' => '/en/admin/foo/bar/5'));
		$this->assertEmpty(Router::process($request)->params);

		$result = Router::match(array('Foo::bar', 'id' => 5));
		$this->assertIdentical('/foo/bar/5', $result);

		$result = Router::match(array('Foo::bar', 'id' => 5, 'admin' => true));
		$this->assertIdentical('/admin/foo/bar/5', $result);

		$result = Router::match(array('Foo::bar', 'id' => 5, 'admin' => true, 'locale' => 'jp'));
		$this->assertIdentical('/admin/jp/foo/bar/5', $result);
	}

	/**
	 * Tests that continuations can be used for route suffixes.
	 */
	public function testSuffixContinuation() {
		Router::connect("/{:args}.{:type}", array(), array('continue' => true));
		Router::connect('/{:controller}/{:id:[0-9]+}', array('action' => 'view'));

		$result = Router::match(array(
			'controller' => 'versions',
			'action' => 'view',
			'id' => 13,
			'type' => 'jsonp'
		));
		$this->assertIdentical('/versions/13.jsonp', $result);

		$result = Router::match(array(
			'controller' => 'versions',
			'action' => 'view',
			'id' => 13
		));
		$this->assertIdentical('/versions/13', $result);
	}

	/**
	 * Tests default route formatters, and setting/getting new formatters.
	 */
	public function testRouteFormatters() {
		$formatters = Router::formatters();
		$this->assertEqual(array('args', 'controller'), array_keys($formatters));

		$this->assertIdentical('foo/bar', $formatters['args'](array('foo', 'bar')));
		$this->assertIdentical('list_items', $formatters['controller']('ListItems'));

		Router::formatters(array('action' => function($value) { return strtolower($value); }));
		$formatters = Router::formatters();
		$this->assertEqual(array('action', 'args', 'controller'), array_keys($formatters));

		Router::formatters(array('action' => null));
		$formatters = Router::formatters();
		$this->assertEqual(array('args', 'controller'), array_keys($formatters));
	}

	public function testRouteModifiers() {
		$modifiers = Router::modifiers();
		$this->assertEqual(array('args', 'controller'), array_keys($modifiers));

		$this->assertEqual(array('foo', 'bar'), $modifiers['args']('foo/bar'));
		$this->assertIdentical('HelloWorld', $modifiers['controller']('hello_world'));
	}

	public function testAttachAbsolute() {
		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => 'http',
			'prefix' => 'web/tests'
		));

		$result = Router::attached('app', array(
			'subdomain' => 'admin',
			'domain' => 'myserver',
			'tld' => 'com'
		));

		$expected = array(
			'absolute' => true,
			'host' => 'admin.myserver.com',
			'scheme' => 'http',
			'base' => null,
			'prefix' => 'web/tests',
			'pattern' => '@^http://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/web/tests/@',
			'library' => 'app',
			'params' => array('subdomain', 'domain', 'tld'),
			'values' => array()
		);
		$this->assertEqual($expected, $result);
	}

	public function testAttachAbsoluteWithHttps() {
		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'prefix' => ''
		));

		$result = Router::attached('app', array(
			'scheme' => 'https',
			'subdomain' => 'admin',
			'domain' => 'myserver',
			'tld' => 'co.uk'
		));

		$expected = array(
			'absolute' => true,
			'host' => 'admin.myserver.co.uk',
			'scheme' => 'https',
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(?P<scheme>https)://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/@',
			'library' => 'app',
			'params' => array('scheme', 'subdomain', 'domain', 'tld'),
			'values' => array()
		);
		$this->assertEqual($expected, $result);
	}

	public function testCompileScopeAbsolute() {
		$result = Router::invokeMethod('_compileScope', array(array(
			'absolute' => true
		)));
		$expected = array(
			'absolute' => true,
			'host' => null,
			'scheme' => null,
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(.*?)//localhost/@',
			'params' => array()
		);
		$this->assertEqual($expected, $result);
	}

	public function testCompileScopeAbsoluteWithPrefix() {
		$result = Router::invokeMethod('_compileScope', array(array(
			'absolute' => true,
			'host' => 'www.hostname.com',
			'scheme' => 'http',
			'prefix' => 'web/tests'
		)));

		$expected = array(
			'absolute' => true,
			'host' => 'www.hostname.com',
			'scheme' => 'http',
			'base' => null,
			'prefix' => 'web/tests',
			'pattern' => '@^http://www\.hostname\.com/web/tests/@',
			'params' => array()
		);
		$this->assertEqual($expected, $result);
	}

	public function testCompileScopeAbsoluteWithVariables() {
		$result = Router::invokeMethod('_compileScope', array(array(
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => 'http',
			'prefix' => 'web/tests'
		)));

		$expected = array(
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => 'http',
			'base' => null,
			'prefix' => 'web/tests',
			'pattern' => '@^http://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/web/tests/@',
			'params' => array('subdomain', 'domain', 'tld')
		);
		$this->assertEqual($expected, $result);

		$result = Router::invokeMethod('_compileScope', array(array(
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'prefix' => ''
		)));

		$expected = array(
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(?P<scheme>https)://(?P<subdomain>[a-z]+)\\.(?P<domain>[^/]+?)\\.' .
			             '(?P<tld>[^/]+?)/@',
			'params' => array('scheme', 'subdomain', 'domain', 'tld')
		);
		$this->assertEqual($expected, $result);
	}

	public function testParseScopeWithRelativeAttachment() {
		$request = new Request(array(
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/'
		));
		Router::attach('app', array(
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAttachment() {
		$request = new Request(array(
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/'
		));
		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.atari.com',
			'scheme' => 'http'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertFalse($result);

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithRelativeAndPrefixedAttachment() {
		$request = new Request(array(
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/web'
		));
		Router::attach('app', array(
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAndPrefixedAttachment() {
		$request = new Request(array(
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/web'
		));
		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertFalse($result);

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAndPrefixAttachment() {
		$request = new Request(array(
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'url' => '/web'
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web2'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertFalse($result);
	}

	public function testParseScopeWithRelativeAndPrefixAttachmentUsingEnvRequest() {
		$request = new Request(array(
			'env' => array(
				'HTTP_HOST' => 'www.amiga.com',
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/',
				'HTTPS' => false
			)
		));

		Router::attach('app', array(
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertFalse($result);

		$request = new Request(array(
			'env' => array(
				'HTTP_HOST' => 'www.amiga.com',
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/web',
				'HTTPS' => false
			)
		));

		Router::attach('app', array(
			'absolute' => false,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);
	}

	public function testParseScopeWithAbsoluteAndPrefixAttachmentUsingEnvRequest() {
		$request = new Request(array(
			'env' => array(
				'HTTP_HOST' => 'www.amiga.com',
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/web',
				'HTTPS' => false
			)
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.atari.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertFalse($result);

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'www.amiga.com',
			'scheme' => 'http',
			'prefix' => '/web2'
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertFalse($result);
	}

	public function testParseScopeWithAbsoluteAttachmentUsingVariables() {
		$request = new Request(array(
			'env' => array(
				'HTTP_HOST' => 'www.amiga.com',
				'HTTPS' => false,
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/'
			)
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain}.atari.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		));
		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertFalse($result);

		$request = new Request(array(
			'env' => array(
				'HTTP_HOST' => 'www.amiga.com',
				'HTTPS' => false,
				'DOCUMENT_ROOT' => '/var/www',
				'PHP_SELF' => '/var/www/index.php',
				'REQUEST_URI' => '/'
			)
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		));

		$result = Router::invokeMethod('_parseScope', array('app', $request));
		$this->assertIdentical('/', $result);
	}

	public function testMatchWithRelativeAndPrefixedAttachment() {
		Router::attach('tests', array(
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix'
		));

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('/prefix/controller/action/hello', $result);
	}

	public function testMatchWithAbsoluteAndPrefixedAttachment() {
		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix'
		));

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('http://tests.mysite.com/prefix/controller/action/hello', $result);
	}

	public function testMatchWithAbsoluteAndNoSchemeAttachment() {
		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => null
		));

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('http://tests.mysite.com/controller/action/hello', $result);

		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => false
		));

		Router::scope('tests');
		$result = Router::match('/controller/action/hello');
		$this->assertIdentical('//tests.mysite.com/controller/action/hello', $result);
	}

	public function testMatchWithRelativeAndPrefixedAttachmentUsingBasedRequest() {
		$request = new Request(array('base' => '/request/base'));
		Router::attach('tests', array(
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http://',
			'prefix' => 'prefix'
		));

		Router::scope('tests');
		$result = Router::match('/controller/action/hello', $request);
		$this->assertIdentical('/request/base/prefix/controller/action/hello', $result);
	}

	public function testMatchWithAbsoluteAndPrefixedAttachmentUsingBasedRequest() {
		$request = new Request(array('base' => '/requestbase'));
		Router::attach('app', array(
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http',
			'prefix' => 'prefix'
		));

		$result = Router::match('/controller/action/hello', $request, array('scope' => 'app'));
		$expected = 'http://app.mysite.com/requestbase/prefix/controller/action/hello';
		$this->assertIdentical($expected, $result);
	}

	public function testMatchWithAbsoluteAttachmentUsingDoubleColonNotation() {
		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => 'prefix'
		));

		Router::scope('tests', function() {
			Router::connect('/user/view/{:args}', array('User::view'));
		});

		Router::scope('tests');
		$result = Router::match(array(
			'User::view', 'args' => 'bob'
		), null, array('scope' => 'tests'));
		$this->assertIdentical('http://tests.mysite.com/prefix/user/view/bob', $result);

		Router::reset();

		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'tests.mysite.com',
			'scheme' => 'http',
			'prefix' => 'prefix',
			'namespace' => 'app\controllers'
		));

		Router::scope('tests', function() {
			Router::connect('/users/view/{:args}', array('Users::view'));
		});

		Router::scope('tests');
		$result = Router::match(array(
			'Users::view', 'args' => 'bob'
		), null, array('scope' => 'tests'));
		$this->assertIdentical('http://tests.mysite.com/prefix/users/view/bob', $result);
	}

	public function testMatchWithAbsoluteAttachmentAndVariables() {
		$request = new Request(array(
			'url' => '/home/index',
			'host' => 'bob.amiga.com',
			'base' => ''
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		));

		Router::scope('app', function() {
			Router::connect('/home/index', array('Home::index'));
		});

		Router::process($request);
		$expected = 'http://bob.amiga.com/home/index';
		$result = Router::match('/home/index', $request);

		$this->assertIdentical($expected, $result);

		$expected = 'http://bob.amiga.com/home/index';
		$result = Router::match('/home/index', $request, array('scope' => 'app'));
		$this->assertIdentical($expected, $result);

		$expected = 'http://max.amiga.com/home/index';
		$result = Router::match('/home/index', $request, array(
			'scope' => array(
				'subdomain' => 'max'
			)
		));
		$this->assertIdentical($expected, $result);

		Router::scope(false);
		$result = Router::match('/home/index', $request, array(
			'scope' => array(
				'app' => array(
					'subdomain' => 'max'
				)
			)
		));
		$this->assertIdentical($expected, $result);

		$result = Router::match('/home/index', $request, array(
			'scope' => array(
				'subdomain' => 'max'
			)
		));
		$this->assertNotEqual($expected, $result);
	}

	public function testMatchWithAttachementPopulatedFromRequest() {
		$request = new Request(array(
			'host' => 'request.mysite.com',
			'scheme' => 'https',
			'base' => 'request/base'
		));
		Router::attach('app', array('absolute' => true));
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
			Router::connect('/user/view/{:args}', array('User::view'));
		});
		Router::scope('tests');

		$ex = "No parameter match found for URL `('controller' => 'User', ";
		$ex .= "'action' => 'view', 'args' => 'bob')` in `app` scope.";
		$this->expectException($ex);

		$result = Router::match(array(
			'User::view', 'args' => 'bob'
		), null, array('scope' => 'app'));
	}

	public function testMatchWithNoRouteDefined() {
		$ex = "No parameter match found for URL `('controller' => 'User', ";
		$ex .= "'action' => 'view', 'args' => 'bob')` in `app` scope.";
		$this->expectException($ex);

		$result = Router::match(array(
			'User::view', 'args' => 'bob'
		), null, array('scope' => 'app'));
	}

	public function testProcessWithAbsoluteAttachment() {
		Router::connect('/home/welcome', array('Home::app'), array('scope' => 'app'));
		Router::connect('/home/welcome', array('Home::tests'), array('scope' => 'tests'));
		Router::connect('/home/welcome', array('Home::default'));

		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'tests.mysite.com'
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'app.mysite.com'
		));

		$request = new Request(array(
			'url' => '/home/welcome',
			'host' => 'www.mysite.com'
		));
		$result = Router::process($request);
		$expected = array(
			'controller' => 'Home',
			'action' => 'default'
		);
		$this->assertEqual($expected, $result->params);

		$request->host = 'tests.mysite.com';
		$result = Router::process($request);
		$expected = array(
			'library' => 'tests',
			'controller' => 'Home',
			'action' => 'tests'
		);
		$this->assertEqual($expected, $result->params);

		$request->host = 'app.mysite.com';
		$result = Router::process($request);
		$expected = array(
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'app'
		);
		$this->assertEqual($expected, $result->params);
	}

	public function testProcessWithRelativeAndPrefixedAttachment() {
		Router::scope('app', function() {
			Router::connect('/home/welcome', array('Home::app'));
		});

		Router::scope('tests', function() {
			Router::connect('/home/welcome', array('Home::tests'));
		});

		Router::connect('/home/welcome', array('Home::default'));

		Router::attach('tests', array(
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix'
		));

		Router::attach('app', array(
			'absolute' => false,
			'host' => 'app.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix'
		));

		$request = new Request(array('url' => '/home/welcome'));
		$result = Router::process($request);
		$expected = array(
			'controller' => 'Home',
			'action' => 'default'
		);
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical(false, Router::scope());

		Router::reset();

		Router::scope('tests', function() {
			Router::connect('/home/welcome', array('Home::tests'));
		});

		Router::scope('app', function() {
			Router::connect('/home/welcome', array('Home::app'));
		});

		Router::connect('/home/welcome', array('Home::default'));

		Router::attach('tests', array(
			'absolute' => false,
			'host' => 'tests.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix1'
		));

		Router::attach('app', array(
			'absolute' => false,
			'host' => 'app.mysite.com',
			'scheme' => 'http://',
			'prefix' => '/prefix2'
		));

		$request = new Request(array('url' => '/prefix1/home/welcome'));
		$result = Router::process($request);
		$expected = array(
			'library' => 'tests',
			'controller' => 'Home',
			'action' => 'tests'
		);
		$this->assertEqual($expected, $result->params);
		$this->assertEqual('tests', Router::scope());
	}

	public function testProcessWithAbsoluteAndPrefixedAttachment() {
		Router::scope('app', function() {
			Router::connect('/home/welcome', array('Home::app'));
		});

		Router::scope('tests', function() {
			Router::connect('/home/welcome', array('Home::tests'));
		});

		Router::connect('/home/welcome', array('Home::default'));

		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix1'
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http',
			'prefix' => '/prefix2'
		));

		$request = new Request(array(
			'host' => 'app.mysite.com',
			'url' => '/home/welcome'
		));

		$result = Router::process($request);
		$expected = array(
			'controller' => 'Home',
			'action' => 'default'
		);
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical(false, Router::scope());

		$request = new Request(array(
			'host' => 'app.mysite.com',
			'url' => '/prefix2/home/welcome'
		));

		$result = Router::process($request);
		$expected = array(
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'app'
		);
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical('app', Router::scope());

		$request = new Request(array(
			'host' => 'app.mysite.com',
			'url' => '/prefix1/home/welcome'
		));

		$result = Router::process($request);
		$expected = array(
			'library' => 'tests',
			'controller' => 'Home',
			'action' => 'tests'
		);
		$this->assertEqual($expected, $result->params);
		$this->assertIdentical('tests', Router::scope());
	}

	public function testProcessWithAbsoluteHttpsAttachment() {
		Router::scope('app', function() {
			Router::connect('/home/welcome', array('Home::app'));
		});

		Router::scope('tests', function() {
			Router::connect('/home/welcome', array('Home::tests'));
		});

		Router::connect('/home/welcome', array('Home::default'));

		Router::attach('tests', array(
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http'
		));

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'http'
		));

		$request = new Request(array(
			'host' => 'app.mysite.com',
			'scheme' => 'https',
			'url' => '/home/welcome'
		));

		$result = Router::process($request);
		$expected = array(
			'controller' => 'Home',
			'action' => 'default'
		);
		$this->assertEqual($expected, $result->params);

		Router::attach('app', array(
			'absolute' => true,
			'host' => 'app.mysite.com',
			'scheme' => 'https'
		));

		$result = Router::process($request);
		$expected = array(
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'app'
		);
		$this->assertEqual($expected, $result->params);
	}

	public function testProcessWithAbsoluteAttachmentAndVariables() {
		$request = new Request(array(
			'url' => '/home/index',
			'prefix' => '',
			'env' => array(
				'HTTP_HOST' => 'bob.amiga.com',
				'HTTPS' => false
			)
		));
		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'prefix' => ''
		));

		Router::scope('app', function() {
			Router::connect('/home/index', array('Home::index'));
		});

		Router::process($request);
		$expected = array(
			'library' => 'app',
			'controller' => 'Home',
			'action' => 'index',
			'subdomain' => 'bob',
			'tld' => 'com'
		);
		$this->assertEqual($expected, $request->params);

		$result = Router::attached('app');
		$this->assertEqual($expected, $result['values']);
	}

	public function testProcessWithAbsoluteAttachmentAndLibrary() {
		$request = new Request(array(
			'url' => '/hello_world/index',
			'env' => array(
				'HTTP_HOST' => 'bob.amiga.com',
				'HTTPS' => false
			)
		));
		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain}.amiga.{:tld}',
			'scheme' => 'http',
			'library' => 'other'
		));

		Router::scope('app', function() {
			Router::connect('/hello_world/index', array('HelloWorld::index'));
			Router::connect('/{:controller}/{:action}');
		});

		Router::process($request);
		$expected = array(
			'library' => 'other',
			'controller' => 'HelloWorld',
			'action' => 'index',
			'subdomain' => 'bob',
			'tld' => 'com'
		);
		$this->assertEqual($expected, $request->params);

		$result = Router::attached('app');
		$this->assertEqual($expected, $result['values']);

		$request = new Request(array(
			'url' => '/posts/index',
			'env' => array(
				'HTTP_HOST' => 'bob.amiga.com',
				'HTTPS' => false
			)
		));

		Router::process($request);
		$expected = array(
			'library' => 'other',
			'controller' => 'Posts',
			'action' => 'index',
			'subdomain' => 'bob',
			'tld' => 'com'
		);
		$this->assertEqual($expected, $request->params);

		$result = Router::attached('app');
		$this->assertEqual($expected, $result['values']);
	}

	public function testParseWithNotValidSchemeVariable() {
		Router::attach('app', array(
			'absolute' => true,
			'host' => '{:subdomain:[a-z]+}.{:domain}.{:tld}',
			'scheme' => '{:scheme:https}',
			'prefix' => ''
		));

		$result = Router::attached('app', array(
			'scheme' => 'http',
			'subdomain' => 'admin',
			'domain' => 'myserver',
			'tld' => 'co.uk'
		));

		$expected = array(
			'absolute' => true,
			'host' => 'admin.myserver.co.uk',
			'scheme' => '{:scheme:https}',
			'base' => null,
			'prefix' => '',
			'pattern' => '@^(?P<scheme>https)://(?P<subdomain>[a-z]+)\\.' .
			             '(?P<domain>[^/]+?)\\.(?P<tld>[^/]+?)/@',
			'library' => 'app',
			'params' => array('scheme', 'subdomain', 'domain', 'tld'),
			'values' => array()
		);
		$this->assertEqual($expected, $result);
	}

	public function testRouteRetrievalWithScope() {
		Router::scope('loc1', function() use (&$expected){
			$expected = Router::connect('/hello', array(
				'controller' => 'Posts',
				'action' => 'index'
			));
		});

		$result = Router::get(0, true);
		$this->assertIdentical(null, $result);

		$result = Router::get(0, 'loc1');
		$this->assertIdentical($expected, $result);

		Router::scope('loc1', function() {
			Router::connect('/helloworld', array(
				'controller' => 'Posts',
				'action' => 'index'
			));
		});

		Router::scope('loc2', function() {
			Router::connect('/hello', array(
				'controller' => 'Posts',
				'action' => 'index'
			));
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
		Router::attach('scope1', array('prefix' => 'scope1', 'absolute' => true));
		Router::attach('scope2', array('prefix' => 'scope2', 'library' => 'app'));
		Router::attach('scope3', array('prefix' => 'scope3'));

		$expected = array(
			'scope1' => array (
				'prefix' => 'scope1',
				'absolute' => true,
				'host' => null,
				'scheme' => null,
				'base' => null,
				'pattern' => '@^(.*?)//localhost/scope1/@',
				'library' => 'scope1',
				'values' => array (),
				'params' => array ()
			),
			'scope2' => array (
				'prefix' => 'scope2',
				'library' => 'app',
				'absolute' => false,
				'host' => null,
				'scheme' => null,
				'base' => null,
				'pattern' => '@^/scope2/@',
				'values' => array (),
				'params' => array ()
			),
			'scope3' => array (
				'prefix' => 'scope3',
				'absolute' => false,
				'host' => null,
				'scheme' => null,
				'base' => null,
				'pattern' => '@^/scope3/@',
				'library' => 'scope3',
				'values' => array (),
				'params' => array ()
			)
		);
		$this->assertEqual($expected, Router::attached());
	}

	public function testScopeBase() {
		$request = new Request(array('base' => 'lithium/app'));
		Router::connect('/{:controller}/{:action}');
		$url = array('controller' => 'HelloWorld');

		$expected = '/lithium/app/hello_world';
		$this->assertEqual($expected, Router::match($url, $request));

		Router::attach(false, array('base' => 'lithium'));
		$expected = '/lithium/hello_world';
		$this->assertEqual($expected, Router::match($url, $request));

		Router::attach(false, array('base' => ''));
		$expected = '/hello_world';
		$this->assertEqual($expected, Router::match($url, $request));
	}
}

?>