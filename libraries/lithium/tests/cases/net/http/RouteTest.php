<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use \lithium\net\http\Route;
use \lithium\net\http\Request;

class RouteTest extends \lithium\test\Unit {

	public function setUp() {
	}

	/**
	 * Tests the creation of routes for the base URL (i.e. '/'), and that they are matched
	 * properly given the correct parameters.
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function testBaseRouteParsing() {
		$params = array('controller' => 'posts', 'action' => 'archive', 'page' => 1);
		$route = new Route(array('template' => '/', 'params' => $params));
		$request = new Request();
		$request->url = '/';

		$result = $route->parse($request);
		$this->assertEqual($params, $result);

		$request->url = '';
		$result = $route->parse($request);
		$this->assertEqual($params, $result);

		$request->url = '/posts';
		$this->assertFalse($route->parse($request));
	}

	/**
	 * Tests that simple routes with only a `{:controller}` parameter are properly matched, and
	 * anything including extra parameters or an action other than the default action are ignored.
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function testSimpleRouteParsing() {
		$route = new Route(array('template' => '/{:controller}'));
		$request = new Request();

		$request->url = '/posts';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'posts', 'action' => 'index'), $result);

		$request->url = '/users';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'users', 'action' => 'index'), $result);

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

		$request->url = '/posts';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'posts', 'action' => 'index'), $result);

		$request->url = '/users';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'users', 'action' => 'index'), $result);

		$request->url = '/1';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => '1', 'action' => 'index'), $result);

		$request->url = '/users/index';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'users', 'action' => 'index'), $result);

		$request->url = '/users/view';
		$result = $route->parse($request);
		$this->assertEqual(array('controller' => 'users', 'action' => 'view'), $result);

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
		$this->assertEqual($expected, $result);

		$request->url = '/posts/index';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result);

		$request->url = '/posts/index/';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result);

		$request->url = '/posts/view/5';
		$result = $route->parse($request);
		$expected = array('controller' => 'posts', 'action' => 'view', 'id' => '5');
		$this->assertEqual($expected, $result);

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

		$request->url = '/posts/view/5.xml';
		$result = $route->parse($request);
		$expected = array(
			'controller' => 'posts', 'action' => 'view', 'id' => '5', 'type' => 'xml'
		);
		$this->assertEqual($expected, $result);

		$request->url = '/posts/index.xml';
		$result = $route->parse($request);
		$expected = array(
			'controller' => 'posts', 'action' => 'index', 'id' => '', 'type' => 'xml'
		);
		$this->assertEqual($expected, $result);

		$request->url = '/posts.xml';
		$result = $route->parse($request);
		$expected = array(
			'controller' => 'posts', 'action' => 'index', 'id' => '', 'type' => 'xml'
		);
		$this->assertEqual($expected, $result);
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
		$this->assertEqual($expected, $result);
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
		$this->assertEqual($expected, $result);

		$request->url = 'login';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that routes can be composed of manual regular expressions.
	 *
	 * @return void
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
		$this->assertEqual($expected, $result);

		$result = $route->match(array('controller' => 'posts', 'action' => 'index'));
		$expected = '/posts';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests exporting a the details of a compiled route to an array.
	 *
	 * @return void
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
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'subPatterns' => array()
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests creating a route with a custom pattern that accepts URLs in two formats but only
	 * generates them in one.
	 *
	 * @return void
	 */
	public function testRoutingMultipleMatch() {
		$route = new Route(array(
			'template' => '/users/{:user}',
			'pattern' => '@^/u(?:sers)?(?:/(?P<user>[^\/]+))$@',
			'params' => array('controller' => 'users', 'action' => 'index'),
			'match' => array('controller' => 'users', 'action' => 'index'),
			'defaults' => array('controller' => 'users'),
			'keys' => array('user' => 'user'),
			'options' => array('compile' => false, 'wrap' => false)
		));
		$result = $route->match(array('controller' => 'users', 'user' => 'alke'));
		$expected = '/users/alke';
		$this->assertEqual($expected, $result);

		$request = new Request();
		$request->url = '/users/alke';
		$expected = array('controller' => 'users', 'action' => 'index', 'user' => 'alke');

		$result = $route->parse($request);
		$this->assertEqual($expected, $result);

		$request->url = '/u/alke';
		$result = $route->parse($request);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests creating a route with a custom regex sub-pattern in a template
	 *
	 * @return void
	 */
	public function testCustomSubPattern() {
		$route = new Route(array('template' => '/{:controller}/{:action}/{:user:\d+}'));

		$request = new Request();
		$request->url = '/users/view/10';
		$expected = array('controller' => 'users', 'action' => 'view', 'user' => '10');

		$result = $route->parse($request);
		$this->assertEqual($expected, $result);

		$request->url = '/users/view/my_login';
		$result = $route->parse($request);
		$this->assertFalse($result);
	}
}

?>