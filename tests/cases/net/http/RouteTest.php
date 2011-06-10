<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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
		$route = new Route(array(
			'template' => '/',
			'params' => array('controller' => 'posts', 'action' => 'archive', 'page' => 1)
		));

		$result = $route->match(array('controller' => 'posts', 'action' => 'archive', 'page' => 1));
		$this->assertEqual('/', $result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'archive', 'page' => 2));
		$this->assertFalse($result);

		$result = $route->match(array());
		$this->assertFalse($result);
	}

	/**
	 * Tests that a request for the base URL (i.e. '/') returns the proper parameters, as defined
	 * by the base route.
	 */
	public function testBaseRouteParsing() {
		$params = array('controller' => 'posts', 'action' => 'archive', 'page' => 1);
		$route = new Route(array('template' => '/', 'params' => $params));
		$request = new Request();
		$request->url = '/';

		$result = $route->parse($request);
		$this->assertEqual($params, $result->params);
		$this->assertEqual(array('controller'), $result->persist);

		$request->url = '';
		$result = $route->parse($request);
		$this->assertEqual($params, $result->params);
		$this->assertEqual(array('controller'), $result->persist);

		$request->url = '/posts';
		$this->assertFalse($route->parse($request));
	}

	/**
	 * Tests that simple routes with only a `{:controller}` parameter are properly matched, and
	 * anything including extra parameters or an action other than the default action are ignored.
	 */
	public function testSimpleRouteMatching() {
		$route = new Route(array('template' => '/{:controller}'));

		$result = $route->match(array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual('/posts', $result);

		$result = $route->match(array('controller' => 'users'));
		$this->assertEqual('/users', $result);

		$this->assertFalse($route->match(array('controller' => 'posts', 'action' => 'view')));
		$this->assertFalse($route->match(array('controller' => 'posts', 'id' => 5)));
		$this->assertFalse($route->match(array('action' => 'index')));
	}

	/**
	 * Tests that requests for base-level resource URLs (i.e. `'/posts'`) are properly parsed into
	 * the correct controller and action parameters.
	 */
	public function testSimpleRouteParsing() {
		$route = new Route(array('template' => '/{:controller}'));
		$request = new Request();
		$default = array('action' => 'index');

		$request->url = '/posts';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'posts') + $default, $result->params);

		$request->url = '/users';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'users') + $default, $result->params);

		$request->url = '/users/index';
		$this->assertFalse($route->parse($request));
	}

	public function testRouteMatchingWithOptionalParam() {
		$route = new Route(array('template' => '/{:controller}/{:action}'));

		$result = $route->match(array('controller' => 'posts'));
		$this->assertEqual('/posts', $result);

		$result = $route->match(array('controller' => 'users', 'action' => 'index'));
		$this->assertEqual('/users', $result);

		$result = $route->match(array('controller' => '1'));
		$this->assertEqual('/1', $result);

		$result = $route->match(array('controller' => '1', 'action' => 'view'));
		$this->assertEqual('/1/view', $result);

		$result = $route->match(array('controller' => 'users', 'action' => 'view'));
		$this->assertEqual('/users/view', $result);

		$result = $route->match(array('controller' => 'users', 'action' => 'view', 'id' => '5'));
		$this->assertFalse($result);

		$result = $route->match(array());
		$this->assertFalse($result);
	}

	public function testRouteParsingWithOptionalParam() {
		$route = new Route(array('template' => '/{:controller}/{:action}'));
		$request = new Request();
		$default = array('action' => 'index');

		$request->url = '/posts';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'posts') + $default, $result->params);

		$request->url = '/users';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'users') + $default, $result->params);

		$request->url = '/1';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => '1') + $default, $result->params);

		$request->url = '/users/index';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'users') + $default, $result->params);

		$request->url = '/users/view';
		$result = $route->parse($request);
		$expected = array('controller' => 'users', 'action' => 'view');
		$this->assertEqual($expected, $result->params);

		$request->url = '/users/view/5';
		$this->assertFalse($route->parse($request));

		$request->url = '/';
		$this->assertFalse($route->parse($request));
	}

	public function testRouteParsingWithOptionalParams() {
		$route = new Route(array(
			'template' => '/{:controller}/{:action}/{:id}', 'params' => array('id' => null)
		));
		$request = new Request();

		$request->url = '/posts';
		$result = $route->parse($request);
		$expected = array('controller' => 'posts', 'action' => 'index', 'id' => null);
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/index';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/index/';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/view/5';
		$result = $route->parse($request);
		$expected = array('controller' => 'posts', 'action' => 'view', 'id' => '5');
		$this->assertEqual($expected, $result->params);

		$request->url = '/';
		$this->assertFalse($route->parse($request));

		$request->url = '/posts/view/5/foo';
		$this->assertFalse($route->parse($request));
	}

	public function testRouteParsingWithOptionalParamsAndType() {
		$route = new Route(array(
			'template' => '/{:controller}/{:action}/{:id}.{:type}',
			'params' => array('id' => null)
		));
		$request = new Request();
		$default = array('controller' => 'posts');

		$request->url = '/posts/view/5.xml';
		$result = $route->parse($request);
		$expected = array('action' => 'view', 'id' => '5', 'type' => 'xml') + $default;
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts/index.xml';
		$result = $route->parse($request);
		$expected = array('action' => 'index', 'id' => '', 'type' => 'xml') + $default;
		$this->assertEqual($expected, $result->params);

		$request->url = '/posts.xml';
		$result = $route->parse($request);
		$expected = array('action' => 'index', 'id' => '', 'type' => 'xml') + $default;
		$this->assertEqual($expected, $result->params);
	}

	public function testRouteMatchingWithEmptyTrailingParams() {
		$route = new Route(array('template' => '/{:controller}/{:action}/{:args}'));

		$result = $route->match(array('controller' => 'posts'));
		$this->assertEqual('/posts', $result);

		$result = $route->match(array('controller' => 'posts', 'args' => 'foo'));
		$this->assertEqual('/posts/index/foo', $result);

		$result = $route->match(array('controller' => 'posts', 'args' => array('foo', 'bar')));
		$this->assertEqual('/posts/index/foo/bar', $result);

		$request = new Request();
		$request->url = '/posts/index/foo/bar';

		$result = $route->parse($request);
		$expected = array(
			'controller' => 'posts', 'action' => 'index', 'args' => array('foo', 'bar')
		);
		$this->assertEqual($expected, $result->params);
	}

	public function testStaticRouteMatching() {
		$route = new Route(array('template' => '/login', 'params' => array(
			'controller' => 'sessions', 'action' => 'add'
		)));
		$result = $route->match(array('controller' => 'sessions', 'action' => 'add'));
		$this->assertEqual('/login', $result);

		$result = $route->match(array());
		$this->assertFalse($result);

		$request = new Request();
		$expected = array('controller' => 'sessions', 'action' => 'add');

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
		$route = new Route(array(
			'template' => '/{:controller}',
			'pattern' => '/(?P<controller>[A-Za-z0-9_-]+)/',
			'keys' => array('controller' => 'controller'),
			'match' => array('action' => 'index'),
			'options' => array('wrap' => false, 'compile' => false)
		));

		$request = new Request();
		$request->url = '/posts';

		$result = $route->parse($request);
		$expected = array('controller' => 'posts', 'action' => 'index');
		$this->assertEqual($expected, $result->params);

		$result = $route->match(array('controller' => 'posts', 'action' => 'index'));
		$expected = '/posts';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests exporting a the details of a compiled route to an array.
	 */
	public function testRouteExporting() {
		$result = new Route(array(
			'template' => '/{:controller}/{:action}',
			'params' => array('action' => 'view')
		));
		$result = $result->export();

		$expected = array(
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@',
			'params' => array('action' => 'view'),
			'defaults' => array('action' => 'view'),
			'match' => array(),
			'meta' => array(),
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'subPatterns' => array(),
			'persist' => array('controller'),
			'handler' => null
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests creating a route with a custom pattern that accepts URLs in two formats but only
	 * generates them in one.
	 */
	public function testRoutingMultipleMatch() {
		$route = new Route(array(
			'template' => '/users/{:user}',
			'pattern' => '@^/u(?:sers)?(?:/(?P<user>[^\/]+))$@',
			'params' => array('controller' => 'users', 'action' => 'index'),
			'match' => array('controller' => 'users', 'action' => 'index'),
			'defaults' => array('controller' => 'users'),
			'keys' => array('user' => 'user'),
			'compile' => false
		));

		$result = $route->match(array('controller' => 'users', 'user' => 'alke'));
		$expected = '/users/alke';
		$this->assertEqual($expected, $result);

		$request = new Request();
		$request->url = '/users/alke';
		$expected = array('controller' => 'users', 'action' => 'index', 'user' => 'alke');

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
		$route = new Route(array('template' => '/{:controller}/{:action}/{:user:\d+}'));

		$request = new Request();
		$request->url = '/users/view/10';
		$expected = array('controller' => 'users', 'action' => 'view', 'user' => '10');

		$result = $route->parse($request);
		$this->assertEqual($expected, $result->params);

		$request->url = '/users/view/my_login';
		$result = $route->parse($request);
		$this->assertFalse($result);
	}

	/**
	 * Tests that routes with querystrings are correctly processed.
	 */
	public function testRoutesWithQueryStrings() {
		$route = new Route(array('template' => '/{:controller}/{:action}/{:args}'));

		$expected = '/posts?foo=bar';
		$result = $route->match(array('controller' => 'posts', '?' => 'foo=bar'));
		$this->assertEqual($expected, $result);

		$expected = '/posts?foo=bar&baz=dib';
		$result = $route->match(array('controller' => 'posts', '?' => 'foo=bar&baz=dib'));
		$this->assertEqual($expected, $result);

		$expected = '/posts?foo=bar';
		$result = $route->match(array('controller' => 'posts', '?' => array('foo' => 'bar')));
		$this->assertEqual($expected, $result);

		$expected = '/posts/archive?foo=bar&baz=dib';
		$result = $route->match(array('controller' => 'posts', 'action' => 'archive', '?' => array(
			'foo' => 'bar', 'baz' => 'dib'
		)));
		$this->assertEqual($expected, $result);

		$expected = '/posts/archive?foo[]=bar&foo[]=baz';
		$result = $route->match(array(
			'controller' => 'posts',
			'action' => 'archive',
			'?' => 'foo[]=bar&foo[]=baz'
		));
		$this->assertEqual($expected, $result);
	}

	public function testReversingRegexRoutes() {
		$route = new Route(array('template' => '/{:controller}/{:id:[0-7]+}'));

		$result = $route->match(array('controller' => 'posts', 'id' => '007'));
		$this->assertEqual('/posts/007', $result);

		$this->assertFalse($route->match(array('controller' => 'posts', 'id' => '009')));
	}

	/**
	 * Tests that route templates with elements containing repetition patterns are correctly parsed.
	 */
	public function testPatternsWithRepetition() {
		$route = new Route(array('template' => '/{:id:[0-9a-f]{24}}.{:type}'));
		$data = $route->export();
		$this->assertEqual('@^(?:/(?P<id>[0-9a-f]{24}))\.(?P<type>[^\/]+)$@', $data['pattern']);

		$this->assertEqual(array('id' => 'id', 'type' => 'type'), $data['keys']);
		$this->assertEqual(array('id' => '[0-9a-f]{24}'), $data['subPatterns']);
	}

	/**
	 * Tests that route handlers are able to modify route parameters.
	 */
	public function testHandlerModification() {
		$route = new Route(array(
			'template' => '/{:id:[0-9a-f]{24}}.{:type}',
			'handler' => function($request) {
				$request->params += array('lang' => $request->env('ACCEPT_LANG') ?: 'en');
				return $request;
			}
		));

		$request = new Request(array('url' => '/4bbf25bd8ead0e5180120000.json'));
		$result = $route->parse($request);
		$lang = $request->env('ACCEPT_LANG') ?: 'en';
		$this->assertEqual($lang, $result->params['lang']);
	}

	/**
	 * Tests that requests can be routed based on HTTP method verbs or HTTP headers.
	 */
	public function testHeaderAndMethodBasedRouting() {
		$parameters = array('controller' => 'users', 'action' => 'edit');

		$route = new Route(array(
			'template' => '/',
			'params' => $parameters + array('http:method' => 'POST')
		));

		$request = new Request(array('env' => array('HTTP_METHOD' => 'GET')));
		$request->url = '/';
		$this->assertFalse($route->parse($request));

		$request = new Request(array('env' => array('REQUEST_METHOD' => 'POST')));
		$request->url = '/';
		$this->assertEqual($parameters, $route->parse($request)->params);

		$route = new Route(array(
			'template' => '/{:controller}/{:id:[0-9]+}',
			'params' => $parameters + array('http:method' => array('POST', 'PUT'))
		));

		$request = new Request(array('env' => array('REQUEST_METHOD' => 'PUT')));
		$request->url = '/users/abc';
		$this->assertFalse($route->parse($request));

		$request->url = '/users/54';
		$this->assertEqual($parameters + array('id' => '54'), $route->parse($request)->params);
	}

	/**
	 * Tests that a successful match against a route with template `'/'` operating at the root of
	 * a domain never returns an empty string.
	 */
	public function testMatchingEmptyRoute() {
		$route = new Route(array(
			'template' => '/',
			'params' => array('controller' => 'users', 'action' => 'view')
		));

		$request = new Request(array('base' => '/'));
		$url = $route->match(array('controller' => 'users', 'action' => 'view'), $request);
		$this->assertEqual('/', $url);

		$request = new Request(array('base' => ''));
		$url = $route->match(array('controller' => 'users', 'action' => 'view'), $request);
		$this->assertEqual('/', $url);
	}

	/**
	 * Tests that routes with optional trailing elements have unnecessary slashes trimmed.
	 */
	public function testTrimmingEmptyPathElements() {
		$route = new Route(array(
			'template' => '/{:controller}/{:id:[0-9]+}',
			'params' => array('action' => 'index', 'id' => null)
		));

		$url = $route->match(array('controller' => 'posts', 'id' => '13'));
		$this->assertEqual("/posts/13", $url);

		$url = $route->match(array('controller' => 'posts'));
		$this->assertEqual("/posts", $url);
	}
}

?>