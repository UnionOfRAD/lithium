<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\net\http;

use lithium\action\Request;
use lithium\net\http\Route;

class RouteTest extends \lithium\test\Unit {

	/**
	 * Tests the creation of routes for the base URL (i.e. '/'), and that they are matched
	 * properly given the correct parameters.
	 */
	public function testBaseRouteMatching() {
		$route = new Route([
			'template' => '/',
			'params' => ['controller' => 'posts', 'action' => 'archive', 'page' => 1]
		]);

		$result = $route->match(['controller' => 'posts', 'action' => 'archive', 'page' => 1]);
		$this->assertEqual('/', $result);

		$result = $route->match(['controller' => 'posts', 'action' => 'archive', 'page' => 2]);
		$this->assertFalse($result);

		$result = $route->match([]);
		$this->assertFalse($result);
	}

	/**
	 * Tests that a request for the base URL (i.e. '/') returns the proper parameters, as defined
	 * by the base route.
	 */
	public function testBaseRouteParsing() {
		$params = ['controller' => 'posts', 'action' => 'archive', 'page' => 1];
		$route = new Route(['template' => '/', 'params' => $params]);
		$request = new Request();
		$request->url = '/';

		$result = $route->parse($request);
		$this->assertEqual($params, $result->params);
		$this->assertEqual(['controller'], $result->persist);

		$request->url = '';
		$result = $route->parse($request);
		$this->assertEqual($params, $result->params);
		$this->assertEqual(['controller'], $result->persist);

		$request->url = '/posts';
		$this->assertFalse($route->parse($request));
	}

	/**
	 * Tests that simple routes with only a `{:controller}` parameter are properly matched, and
	 * anything including extra parameters or an action other than the default action are ignored.
	 */
	public function testSimpleRouteMatching() {
		$route = new Route(['template' => '/{:controller}']);
		$result = $route->match(['controller' => 'posts', 'action' => 'index']);
		$this->assertEqual('/posts', $result);

		$result = $route->match(['controller' => 'users']);
		$this->assertEqual('/users', $result);

		$this->assertFalse($route->match(['controller' => 'posts', 'action' => 'view']));
		$this->assertFalse($route->match(['controller' => 'posts', 'id' => 5]));
		$this->assertFalse($route->match(['action' => 'index']));
	}

	/**
	 * Tests that requests for base-level resource URLs (i.e. `'/posts'`) are properly parsed into
	 * the correct controller and action parameters.
	 */
	public function testSimpleRouteParsing() {
		$route = new Route(['template' => '/{:controller}']);
		$request = new Request();
		$default = ['action' => 'index'];

		$request->url = '/posts';
		$result = $route->parse($request);
		$this->assertEqual(['controller' => 'posts'] + $default, $result->params);

		$request->url = '/users';
		$result = $route->parse($request);
		$this->assertEqual(['controller' => 'users'] + $default, $result->params);

		$request->url = '/users/index';
		$this->assertFalse($route->parse($request));
	}

	public function testRouteMatchingWithOptionalParam() {
		$route = new Route(['template' => '/{:controller}/{:action}']);

		$result = $route->match(['controller' => 'posts']);
		$this->assertEqual('/posts', $result);

		$result = $route->match(['controller' => 'users', 'action' => 'index']);
		$this->assertEqual('/users', $result);

		$result = $route->match(['controller' => '1']);
		$this->assertEqual('/1', $result);

		$result = $route->match(['controller' => '1', 'action' => 'view']);
		$this->assertEqual('/1/view', $result);

		$result = $route->match(['controller' => 'users', 'action' => 'view']);
		$this->assertEqual('/users/view', $result);

		$result = $route->match(['controller' => 'users', 'action' => 'view', 'id' => '5']);
		$this->assertFalse($result);

		$result = $route->match([]);
		$this->assertFalse($result);
	}

	public function testRouteParsingWithOptionalParam() {
		$route = new Route(['template' => '/{:controller}/{:action}']);
		$request = new Request();
		$default = ['action' => 'index'];

		$request->url = '/posts';
		$result = $route->parse($request);
		$this->assertEqual(['controller' => 'posts'] + $default, $result->params);

		$request->url = '/users';
		$result = $route->parse($request);
		$this->assertEqual(['controller' => 'users'] + $default, $result->params);

		$request->url = '/1';
		$result = $route->parse($request);
		$this->assertEqual(['controller' => '1'] + $default, $result->params);

		$request->url = '/users/index';
		$result = $route->parse($request);
		$this->assertEqual(['controller' => 'users'] + $default, $result->params);

		$request->url = '/users/view';
		$result = $route->parse($request);
		$expected = ['controller' => 'users', 'action' => 'view'];
		$this->assertEqual($expected, $result->params);

		$request->url = '/users/view/5';
		$this->assertFalse($route->parse($request));

		$request->url = '/';
		$this->assertFalse($route->parse($request));
	}

	public function testRouteParsingWithOptionalParams() {
		$route = new Route([
			'template' => '/{:controller}/{:action}/{:id}', 'params' => ['id' => null]
		]);
		$request = new Request();

		$request->url = '/posts';
		$result = $route->parse($request);
		$expected = ['controller' => 'posts', 'action' => 'index', 'id' => null];
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/index';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/index/';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/view/0';
		$result = $route->parse($request);
		$expected = ['controller' => 'posts', 'action' => 'view', 'id' => '0'];
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/view/5';
		$result = $route->parse($request);
		$expected = ['controller' => 'posts', 'action' => 'view', 'id' => '5'];
		$this->assertEqual($expected, $result->params);

		$request->url = '/';
		$this->assertFalse($route->parse($request));

		$request->url = '/posts/view/5/foo';
		$this->assertFalse($route->parse($request));
	}

	public function testRouteParsingWithOptionalParamsAndType() {
		$route = new Route([
			'template' => '/{:controller}/{:action}/{:id}.{:type}',
			'params' => ['id' => null]
		]);
		$request = new Request();
		$default = ['controller' => 'posts'];

		$request->url = '/posts/view/5.xml';
		$result = $route->parse($request);
		$expected = ['action' => 'view', 'id' => '5', 'type' => 'xml'] + $default;
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/index.xml';
		$result = $route->parse($request);
		$expected = ['action' => 'index', 'id' => '', 'type' => 'xml'] + $default;
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts.xml';
		$result = $route->parse($request);
		$expected = ['action' => 'index', 'id' => '', 'type' => 'xml'] + $default;
		$this->assertEqual($expected, $result->params);
	}

	public function testRouteParsingWithParamsEqualsToZero() {
		$route = new Route([
			'template' => '/{:controller}/{:action}/{:value}/{:id}.{:type}',
			'params' => ['id' => null]
		]);
		$request = new Request();
		$default = ['controller' => 'posts'];

		$request->url = '/posts/action/0/123.json';
		$result = $route->parse($request);
		$expected = [
			'action' => 'action',
			'value' => 0,
			'id' => '123',
			'type' => 'json'
		] + $default;
		$this->assertEqual($expected, $result->params);
	}

	public function testRouteMatchingWithEmptyTrailingParams() {
		$route = new Route([
			'template' => '/{:controller}/{:action}/{:args}',
			'modifiers' => ['args' => function($value) {
				return explode('/', $value);
			}],
			'formatters' => ['args' => function($value) {
				return is_array($value) ? join('/', $value) : $value;
			}]
		]);

		$result = $route->match(['controller' => 'posts']);
		$this->assertEqual('/posts', $result);

		$result = $route->match(['controller' => 'posts', 'args' => 'foo']);
		$this->assertEqual('/posts/index/foo', $result);

		$result = $route->match(['controller' => 'posts', 'args' => ['foo', 'bar']]);
		$this->assertEqual('/posts/index/foo/bar', $result);

		$request = new Request();
		$request->url = '/posts/index/foo/bar';

		$result = $route->parse($request);
		$expected = [
			'controller' => 'posts', 'action' => 'index', 'args' => ['foo', 'bar']
		];
		$this->assertEqual($expected, $result->params);
	}

	public function testStaticRouteMatching() {
		$route = new Route(['template' => '/login', 'params' => [
			'controller' => 'sessions', 'action' => 'add'
		]]);
		$result = $route->match(['controller' => 'sessions', 'action' => 'add']);
		$this->assertEqual('/login', $result);

		$result = $route->match([]);
		$this->assertFalse($result);

		$request = new Request();
		$expected = ['controller' => 'sessions', 'action' => 'add'];

		$request->url = '/login';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = 'login';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);
	}

	/**
	 * Tests that routes can be composed of manual regular expressions.
	 */
	public function testManualRouteDefinition() {
		$route = new Route([
			'template' => '/{:controller}',
			'pattern' => '/(?P<controller>[A-Za-z0-9_-]+)/',
			'keys' => ['controller' => 'controller'],
			'match' => ['action' => 'index'],
			'options' => ['wrap' => false, 'compile' => false]
		]);

		$request = new Request();
		$request->url = '/posts';

		$result = $route->parse($request);
		$expected = ['controller' => 'posts', 'action' => 'index'];
		$this->assertEqual($expected, $result->params);

		$result = $route->match(['controller' => 'posts', 'action' => 'index']);
		$expected = '/posts';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests exporting a the details of a compiled route to an array.
	 */
	public function testRouteExporting() {
		$result = new Route([
			'template' => '/{:controller}/{:action}',
			'params' => ['action' => 'view']
		]);
		$result = $result->export();

		$expected = [
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@u',
			'params' => ['action' => 'view'],
			'defaults' => ['action' => 'view'],
			'match' => [],
			'meta' => [],
			'keys' => ['controller' => 'controller', 'action' => 'action'],
			'subPatterns' => [],
			'persist' => ['controller'],
			'handler' => null
		];
		$this->assertEqual($expected, $result);

		$result = new Route([
			'template' => '/images/image_{:width}x{:height}.{:format}',
			'params' => ['format' => 'png']
		]);

		$ptrn = '@^/images/image_(?P<width>[^\\/]+)x(?P<height>[^\\/]+)\\.(?P<format>[^\\/]+)?$@u';
		$expected = [
			'template' => '/images/image_{:width}x{:height}.{:format}',
			'pattern' => $ptrn,
			'params' => ['format' => 'png', 'action' => 'index'],
			'match' => ['action' => 'index'],
			'meta' => [],
			'keys' => ['width' => 'width', 'height' => 'height', 'format' => 'format'],
			'defaults' => ['format' => 'png'],
			'subPatterns' => [],
			'persist' => [],
			'handler' => null
		];
		$result = $result->export();
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests creating a route with a custom pattern that accepts URLs in two formats but only
	 * generates them in one.
	 */
	public function testRoutingMultipleMatch() {
		$route = new Route([
			'template' => '/users/{:user}',
			'pattern' => '@^/u(?:sers)?(?:/(?P<user>[^\/]+))$@',
			'params' => ['controller' => 'users', 'action' => 'index'],
			'match' => ['controller' => 'users', 'action' => 'index'],
			'defaults' => ['controller' => 'users'],
			'keys' => ['user' => 'user'],
			'compile' => false
		]);

		$result = $route->match(['controller' => 'users', 'user' => 'alke']);
		$expected = '/users/alke';
		$this->assertEqual($expected, $result);

		$request = new Request();
		$request->url = '/users/alke';
		$expected = ['controller' => 'users', 'action' => 'index', 'user' => 'alke'];

		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/u/alke';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);
	}

	/**
	 * Tests creating a route with a custom regex sub-pattern in a template.
	 */
	public function testCustomSubPattern() {
		$route = new Route(['template' => '/{:controller}/{:action}/{:user:\d+}']);

		$request = new Request();
		$request->url = '/users/view/10';
		$expected = ['controller' => 'users', 'action' => 'view', 'user' => '10'];

		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/users/view/my_login';
		$result = $route->parse($request);
		$this->assertFalse($result);
	}

	/**
	 * Tests creating a route with a custom sub-pattern and trailing route
	 */
	public function testCustomSubPatternWithTrailing() {
		$route = new Route([
			'template' => '/{:controller}/{:action}/{:id:[0-9]+}/abcdefghijklm'
		]);

		$request = new Request();
		$request->url = '/users/view/10/abcdefghijklm';
		$expected = ['controller' => 'users', 'action' => 'view', 'id' => '10'];

		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/users/view/a/abcdefghijklm';
		$result = $route->parse($request);
		$this->assertFalse($result);
	}

	/**
	 * Tests that routes with querystrings are correctly processed.
	 */
	public function testRoutesWithQueryStrings() {
		$route = new Route(['template' => '/{:controller}/{:action}/{:args}']);

		$expected = '/posts?foo=bar';
		$result = $route->match(['controller' => 'posts', '?' => 'foo=bar']);
		$this->assertEqual($expected, $result);

		$expected = '/posts?foo=bar&baz=dib';
		$result = $route->match(['controller' => 'posts', '?' => 'foo=bar&baz=dib']);
		$this->assertEqual($expected, $result);

		$expected = '/posts?foo=bar';
		$result = $route->match(['controller' => 'posts', '?' => ['foo' => 'bar']]);
		$this->assertEqual($expected, $result);

		$expected = '/posts/archive?foo=bar&baz=dib';
		$result = $route->match(['controller' => 'posts', 'action' => 'archive', '?' => [
			'foo' => 'bar', 'baz' => 'dib'
		]]);
		$this->assertEqual($expected, $result);

		$expected = '/posts/archive?foo[]=bar&foo[]=baz';
		$result = $route->match([
			'controller' => 'posts',
			'action' => 'archive',
			'?' => 'foo[]=bar&foo[]=baz'
		]);
		$this->assertEqual($expected, $result);
	}

	public function testReversingRegexRoutes() {
		$route = new Route(['template' => '/{:controller}/{:id:[0-7]+}']);

		$result = $route->match(['controller' => 'posts', 'id' => '007']);
		$this->assertEqual('/posts/007', $result);

		$this->assertFalse($route->match(['controller' => 'posts', 'id' => '009']));
	}

	/**
	 * Tests that route templates with elements containing repetition patterns are correctly parsed.
	 */
	public function testPatternsWithRepetition() {
		$route = new Route(['template' => '/{:id:[0-9a-f]{24}}.{:type}']);
		$data = $route->export();
		$this->assertEqual('@^(?:/(?P<id>[0-9a-f]{24}))\.(?P<type>[^\/]+)$@u', $data['pattern']);

		$this->assertEqual(['id' => 'id', 'type' => 'type'], $data['keys']);
		$this->assertEqual(['id' => '[0-9a-f]{24}'], $data['subPatterns']);

		$route = new Route(['template' => '/{:key:[a-z]{5}[0-9]{2,3}}']);
		$data = $route->export();
		$this->assertEqual('@^(?:/(?P<key>[a-z]{5}[0-9]{2,3}))$@u', $data['pattern']);
		$this->assertEqual(['key' => '[a-z]{5}[0-9]{2,3}'], $data['subPatterns']);

		$this->assertEqual('/abcde13', $route->match(['key' => 'abcde13']));
		$this->assertFalse($route->match(['key' => 'abcdef13']));

		$route = new Route(['template' => '/{:key:z[a-z]{5}[0-9]{2,3}0}/{:val:[0-9]{2}}']);
		$data = $route->export();

		$expected = '@^(?:/(?P<key>z[a-z]{5}[0-9]{2,3}0))(?:/(?P<val>[0-9]{2}))$@u';
		$this->assertEqual($expected, $data['pattern']);

		$expected = ['key' => 'z[a-z]{5}[0-9]{2,3}0', 'val' => '[0-9]{2}'];
		$this->assertEqual($expected, $data['subPatterns']);

		$result = $route->match(['key' => 'zgheug910', 'val' => '13']);
		$this->assertEqual('/zgheug910/13', $result);
		$this->assertFalse($route->match(['key' => 'zgheu910', 'val' => '13']));

		$result = $route->match(['key' => 'zgheug9410', 'val' => '13']);
		$this->assertEqual('/zgheug9410/13', $result);
		$this->assertFalse($route->match(['key' => 'zgheug941', 'val' => '13']));
	}

	/**
	 * Tests that route handlers are able to modify route parameters.
	 */
	public function testHandlerModification() {
		$route = new Route([
			'template' => '/{:id:[0-9a-f]{24}}.{:type}',
			'handler' => function($request) {
				$request->params += ['lang' => $request->env('ACCEPT_LANG') ?: 'en'];
				return $request;
			}
		]);

		$request = new Request(['url' => '/4bbf25bd8ead0e5180120000.json']);
		$result = $route->parse($request);
		$lang = $request->env('ACCEPT_LANG') ?: 'en';
		$this->assertEqual($lang, $result->params['lang']);
	}

	/**
	 * Tests that requests can be routed based on HTTP method verbs or HTTP headers.
	 */
	public function testHeaderAndMethodBasedRouting() {
		$parameters = ['controller' => 'users', 'action' => 'edit'];

		$route = new Route([
			'template' => '/',
			'params' => $parameters + ['http:method' => 'POST']
		]);

		$request = new Request(['env' => ['HTTP_METHOD' => 'GET']]);
		$request->url = '/';
		$this->assertFalse($route->parse($request));

		$request = new Request(['env' => ['REQUEST_METHOD' => 'POST']]);
		$request->url = '/';
		$this->assertEqual($parameters, $route->parse($request)->params);

		$route = new Route([
			'template' => '/{:controller}/{:id:[0-9]+}',
			'params' => $parameters + ['http:method' => ['POST', 'PUT']]
		]);

		$request = new Request(['env' => ['REQUEST_METHOD' => 'PUT']]);
		$request->url = '/users/abc';
		$this->assertFalse($route->parse($request));

		$request->url = '/users/54';
		$this->assertEqual($parameters + ['id' => '54'], $route->parse($request)->params);
	}

	/**
	 * Tests that a successful match against a route with template `'/'` operating at the root of
	 * a domain never returns an empty string.
	 */
	public function testMatchingEmptyRoute() {
		$route = new Route([
			'template' => '/',
			'params' => ['controller' => 'users', 'action' => 'view']
		]);

		$request = new Request(['base' => '/']);
		$url = $route->match(['controller' => 'users', 'action' => 'view'], $request);
		$this->assertEqual('/', $url);

		$request = new Request(['base' => '']);
		$url = $route->match(['controller' => 'users', 'action' => 'view'], $request);
		$this->assertEqual('/', $url);
	}

	/**
	 * Test route matching for routes with specified request method (http:method)
	 */
	public function testMatchWithRequestMethod() {
		$parameters = ['controller' => 'resource', 'action' => 'create'];

		$route = new Route([
			'template' => '/resource',
			'params' => $parameters + ['http:method' => 'POST']
		]);

		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'http:method' => 'POST'
		]);
		$this->assertEqual('/resource', $result);

		$result = $route->match(['controller' => 'resource', 'action' => 'create']);
		$this->assertEqual(false, $result);
	}

	/**
	 * Test route matching for routes with specified request method (http:method) and a param
	 */
	public function testMatchWithRequestMethodWithParam() {
		$parameters = ['controller' => 'resource', 'action' => 'create'];

		$route = new Route([
			'template' => '/{:param}',
			'params' => $parameters + ['http:method' => 'POST']
		]);

		$result = $route->match([
			'controller' => 'resource',
			'action' => 'create',
			'param' => 'value',
			'http:method' => 'POST'
		]);
		$this->assertEqual('/value', $result);

		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'param' => 'value'
		]);
		$this->assertEqual(false, $result);
	}

	/**
	 * Test route matching for routes with no request method (http:method)
	 */
	public function testMatchWithNoRequestMethod() {
		$parameters = ['controller' => 'resource', 'action' => 'create'];

		$route = new Route([
			'template' => '/resource',
			'params' => $parameters
		]);

		$result = $route->match(['controller' => 'resource', 'action' => 'create']);
		$this->assertEqual('/resource', $result);
		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'http:method' => 'GET'
		]);
		$this->assertEqual('/resource', $result);
		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'http:method' => 'POST'
		]);
		$this->assertEqual('/resource', $result);
		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'http:method' => 'PUT'
		]);
		$this->assertEqual('/resource', $result);
	}

	/**
	 * Test route matching for routes with request method (http:method) GET
	 */
	public function testMatchWithRequestMethodGet() {
		$parameters = ['controller' => 'resource', 'action' => 'create'];

		$route = new Route([
			'template' => '/resource',
			'params' => $parameters + ['http:method' => 'GET']
		]);

		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'http:method' => 'GET']
		);
		$this->assertEqual('/resource', $result);

		$result = $route->match(['controller' => 'resource', 'action' => 'create']);
		$this->assertEqual('/resource', $result);

		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'http:method' => 'POST']
		);
		$this->assertEqual(false, $result);
		$result = $route->match([
			'controller' => 'resource', 'action' => 'create', 'http:method' => 'PUT']
		);
		$this->assertEqual(false, $result);
	}

	/**
	 * Tests that routes with optional trailing elements have unnecessary slashes trimmed.
	 */
	public function testTrimmingEmptyPathElements() {
		$route = new Route([
			'template' => '/{:controller}/{:id:[0-9]+}',
			'params' => ['action' => 'index', 'id' => null]
		]);

		$url = $route->match(['controller' => 'posts', 'id' => '13']);
		$this->assertEqual("/posts/13", $url);

		$url = $route->match(['controller' => 'posts']);
		$this->assertEqual("/posts", $url);
	}

	public function testRouteMatchParseSymmetryWithoutTrimming() {
		$route = new Route([
			'template' => '/{:language:(de|en)}/{:country:(DE|NL)}/home',
			'params' => ['controller' => 'pages', 'action' => 'home'],
			'defaults' => [
				'language' => 'de',
				'country' => 'DE'
			]
		]);

		$params = [
			'controller' => 'pages', 'action' => 'home',
			'language' => 'en', 'country' => 'DE'
		];
		$url = $route->match($params);

		$this->assertEqual('/en/DE/home', $url);

		$request = new Request();
		$request->url = $url;

		$result = $route->parse($request);
		$this->assertEqual($params, $result->params);
	}

	public function testRouteMatchParseSymmetryWithRootTrimming() {
		$route = new Route([
			'template' => '/{:language:(de|en)}/{:country:(DE|NL)}',
			'params' => ['controller' => 'pages', 'action' => 'home', 'country' => true],
			'defaults' => [
				'language' => 'de',
				'country' => 'DE'
			]
		]);

		$params = [
			'controller' => 'pages', 'action' => 'home',
			'language' => 'en', 'country' => 'DE'
		];
		$url = $route->match($params);

		$this->assertEqual('/en', $url);

		$request = new Request();
		$request->url = $url;

		$result = $route->parse($request);
		$this->assertEqual($params, $result->params);
	}

	public function testUrlEncodedArgs() {
		$route = new Route([
			'template' => '/{:controller}/{:action}/{:args}',
			'modifiers' => ['args' => function($value) {
				return explode('/', $value);
			}]
		]);
		$request = new Request();
		$request->url = '/posts/index/Food%20%26%20Dining';
		$result = $route->parse($request);
		$expected = [
			'controller' => 'posts', 'action' => 'index', 'args' => ['Food%20%26%20Dining']
		];
		$this->assertEqual($expected, $result->params);
	}

	public function testContinuationRoute() {
		$route = new Route();
		$this->assertFalse($route->canContinue());

		$route = new Route(['continue' => true]);
		$this->assertTrue($route->canContinue());

		$route = new Route([
			'template' => '/admin/{:args}',
			'continue' => true,
			'params' => ['admin' => true]
		]);

		$result = $route->match(['admin' => true, 'args' => '']);
		$this->assertEqual('/admin/{:args}', $result);
	}

	public function testContinuationRouteWithParameters() {
		$route = new Route([
			'template' => '/admin/{:args}',
			'continue' => true,
			'params' => ['admin' => true]
		]);

		$result = $route->match([
			'admin' => true, 'controller' => 'users', 'action' => 'login'
		]);
		$this->assertEqual('/admin/{:args}', $result);

		$result = $route->match(['controller' => 'users', 'action' => 'login']);
		$this->assertFalse($result);
	}

	/**
	 * Tests that continuation routes don't append query strings.
	 */
	public function testContinuationRouteWithQueryString() {
		$route = new Route([
			'template' => '/admin/{:args}',
			'continue' => true,
			'params' => ['admin' => true]
		]);

		$result = $route->match(['Posts::index', 'admin' => true, '?' => ['page' => 2]]);
		$this->assertEqual('/admin/{:args}', $result);
	}

	/**
	 * Tests correct regex backtracking.
	 */
	public function testValidPatternGeneration() {
		$route = new Route([
			'template' => '/posts/list/{:foobar:[0-9a-f]{5}}/todday/fooo',
			'params' => ['controller' => 'posts', 'action' => 'archive']
		]);

		$expected = '@^/posts/list(?:/(?P<foobar>[0-9a-f]{5}))/todday/fooo$@u';
		$result = $route->export();
		$this->assertEqual($expected, $result['pattern']);
	}

	/**
	 * Tests that routes with Unicode characters are correctly parsed.
	 */
	public function testUnicodeParameters() {
		$route = new Route([
			'template' => '/{:slug:[\pL\pN\-\%]+}',
			'params' => ['controller' => 'users', 'action' => 'view']
		]);

		$unicode = 'clément';
		$slug = rawurlencode($unicode);
		$params = ['controller' => 'users', 'action' => 'view'] + compact('slug');

		$result = $route->match($params);
		$this->assertEqual("/{$slug}", $result);

		$request = new Request(['url' => "/{$slug}"]);
		$result = $route->parse($request, ['url' => $request->url]);

		$expected = ['controller' => 'users', 'action' => 'view'] + compact('slug');
		$this->assertEqual($expected, $result->params);

		$request = new Request(['url' => "/{$slug}"]);
		$result = $route->parse($request, ['url' => $request->url]);
		$expected = ['controller' => 'users', 'action' => 'view'] + compact('slug');
		$this->assertEqual($expected, $result->params);
	}

	/**
	 * Tests fix for route parameter matching.
	 */
	public function testTwoParameterRoutes() {
		$route = new Route([
			'template' => '/personnel/{:personnel_id}/position/{:position_id}/actions/create',
			'params' => ['controller' => 'actions', 'action' => 'create']
		]);

		$route->compile();
		$data = $route->export(); $actual = $data['pattern'];
		$expected = '@^/personnel(?:/(?P<personnel_id>[^\\/]+))/position(?:/';
		$expected .= '(?P<position_id>[^\\/]+))/actions/create$@u';

		$this->assertEqual($expected, $actual);
	}

	/**
	 * Tests that a single route with default values matches its default parameters, as well as
	 * non-default parameters.
	 */
	public function testSingleRouteWithDefaultValues() {
		$defaults = ['controller' => 'Admin', 'action' => 'index'];

		$route = new Route(compact('defaults') + [
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P[^\\/]+)?)?(?:/(?P[^\\/]+)?)?$@u',
			'params' => ['controller' => 'Admin', 'action' => 'index'],
			'keys' => ['controller' => 'controller', 'action' => 'action'],
			'match' => []
		]);
		$this->assertIdentical('/', $route->match($defaults));

		$nonDefault = ['controller' => 'Admin', 'action' => 'view'];
		$this->assertIdentical('/Admin/view', $route->match($nonDefault));
	}

	/**
	 * Tests that routes with defaults keep their defaults, even when there
	 * are keys in the route template.
	 */
	public function testDefaultsAreKept() {
		$request = new Request();
		$request->url = '/shop/pay/123';

		$route = new Route([
			'template' => '/shop/pay/{:uuid}',
			'params' => ['controller' => 'orders', 'action' => 'pay'],
			'defaults' => ['language' => 'de']
		]);
		$result = $route->parse($request);
		$this->assertArrayHasKey('language', $result->params);

		$request = new Request();
		$request->url = '/shop/pay/123';

		$route = new Route([
			'template' => '/shop/pay/{:uuid:[0-9]+}',
			'params' => ['controller' => 'orders', 'action' => 'pay'],
			'defaults' => ['language' => 'de']
		]);
		$result = $route->parse($request);
		$this->assertArrayHasKey('language', $result->params);

		$request = new Request();
		$request->url = '/shop/pay';

		$route = new Route([
			'template' => '/shop/pay',
			'params' => ['controller' => 'orders', 'action' => 'pay'],
			'defaults' => ['language' => 'de']
		]);
		$result = $route->parse($request);
		$this->assertArrayHasKey('language', $result->params);
	}

	public function testRouteParsingWithRegexAction() {
		$route = new Route([
			'template' => '/products/{:action:add|edit|remove}/{:category}',
			'params' => ['controller' => 'Products']
		]);
		$request = new Request();
		$request->url = '/products/add/computer';
		$result = $route->parse($request);
		$expected = [
			'controller' => 'Products',
			'action' => 'add',
			'category' => 'computer'
		];
		$this->assertEqual($expected, $result->params);

		$request = new Request();
		$request->url = '/products/index/computer';
		$result = $route->parse($request);
		$this->assertEqual(false, $result);
	}

	public function testRouteParsingWithRegexActionAndParamWithAction() {
		$route = new Route([
			'template' => '/products/{:action:add|edit|remove}/{:category}',
			'params' => ['controller' => 'Products', 'action' => 'index']
		]);
		$request = new Request();
		$request->url = '/products/hello';
		$result = $route->parse($request);
		$expected = [
			'controller' => 'Products',
			'action' => 'index',
			'category' => 'hello'
		];
		$this->assertEqual($expected, $result->params);
	}

	public function testRouteParsingWithRegexActionAndParamWithoutAction() {
		$route = new Route([
			'template' => '/products/{:action:add|edit|remove}/{:category}',
			'params' => ['controller' => 'Products']
		]);
		$request = new Request();
		$request->url = '/products/hello';
		$result = $route->parse($request);
		$this->assertEqual(false, $result);

		$request = new Request();
		$request->url = '/products';
		$result = $route->parse($request);
		$this->assertEqual(false, $result);
	}
}

?>