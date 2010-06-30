<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use \lithium\action\Request;
use \lithium\net\http\Route;
use \lithium\net\http\Router;
use \lithium\action\Response;

class RouterTest extends \lithium\test\Unit {

	public $request = null;

	protected $_routes = array();

	public function setUp() {
		$this->request = new Request();
		$this->_routes = Router::get();
		Router::reset();
	}

	public function tearDown() {
		Router::reset();

		foreach ($this->_routes as $route) {
			Router::connect($route);
		}
	}

	public function testBasicRouteConnection() {
		$result = Router::connect('/hello', array('controller' => 'posts', 'action' => 'index'));
		$expected = array(
			'template' => '/hello',
			'pattern' => '@^/hello$@',
			'params' => array('controller' => 'posts', 'action' => 'index'),
			'match' => array('controller' => 'posts', 'action' => 'index'),
			'defaults' => array(),
			'keys' => array(),
			'subPatterns' => array()
		);
		$this->assertEqual($expected, $result->export());

		$result = Router::connect('/{:controller}/{:action}', array('action' => 'view'));
		$this->assertTrue($result instanceof Route);
		$expected = array(
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@',
			'params' => array('action' => 'view'),
			'defaults' => array('action' => 'view'),
			'match' => array(),
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'subPatterns' => array()
		);
		$this->assertEqual($expected, $result->export());
	}

	/**
	 * Tests generating routes with required parameters which are not present in the URL.
	 *
	 * @return void
	 */
	public function testConnectingWithRequiredParams() {
		$result = Router::connect('/{:controller}/{:action}', array(
			'action' => 'view', 'required' => true
		));
		$expected = array(
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\\/]+))(?:/(?P<action>[^\\/]+)?)?$@',
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'params' => array('action' => 'view', 'required' => true),
			'defaults' => array('action' => 'view'),
			'match' => array('required' => true),
			'subPatterns' => array()
		);
		$this->assertEqual($expected, $result->export());
	}

	public function testConnectingWithDefaultParams() {
		$result = Router::connect('/{:controller}/{:action}', array('action' => 'archive'));
		$expected = array(
			'template' => '/{:controller}/{:action}',
			'pattern' => '@^(?:/(?P<controller>[^\/]+))(?:/(?P<action>[^\/]+)?)?$@',
			'keys' => array('controller' => 'controller', 'action' => 'action'),
			'params' => array('action' => 'archive'),
			'match' => array(),
			'defaults' => array('action' => 'archive'),
			'subPatterns' => array()
		);
		$this->assertEqual($expected, $result->export());
	}

	/**
	 * Tests basic options for connecting routes.
	 *
	 * @return void
	 */
	public function testBasicRouteMatching() {
		Router::connect('/hello', array('controller' => 'posts', 'action' => 'index'));
		$expected = array('controller' => 'posts', 'action' => 'index');

		foreach (array('/hello/', '/hello', 'hello/', 'hello') as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result->params);
			$this->assertEqual(array('controller'), $result->persist);
		}
	}

	public function testRouteMatchingWithDefaultParameters() {
		Router::connect('/{:controller}/{:action}', array('action' => 'view'));
		$expected = array('controller' => 'posts', 'action' => 'view');

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
		$result = Router::parse($this->request);
		$this->assertNull($result);
	}

	/**
	 * Tests that URLs specified as "Controller::action" are interpreted properly.
	 *
	 * @return void
	 */
	public function testStringActions() {
		Router::connect('/login', array('controller' => 'sessions', 'action' => 'create'));
		Router::connect('/{:controller}/{:action}');

		$result = Router::match("Sessions::create");
		$this->assertEqual('/login', $result);

		$result = Router::match("Posts::index");
		$this->assertEqual('/posts', $result);

		$result = Router::match("ListItems::archive");
		$this->assertEqual('/list_items/archive', $result);
	}

	/**
	 * Tests that URLs specified as "Controller::action" and including additional parameters are
	 * interpreted properly.
	 *
	 * @return void
	 */
	public function testEmbeddedStringActions() {
		Router::connect('/logout/{:id:[0-9]{5,6}}', array(
			'controller' => 'sessions', 'action' => 'destroy', 'id' => null
		));
		Router::connect('/{:controller}/{:action}');
		Router::connect('/{:controller}/{:action}/{:id:[0-9]+}', array('id' => null));

		$result = Router::match("Sessions::create");
		$this->assertEqual('/sessions/create', $result);

		$result = Router::match(array("Sessions::create"));
		$this->assertEqual('/sessions/create', $result);

		$result = Router::match(array("Sessions::destroy", 'id' => '03815'));
		$this->assertEqual('/logout/03815', $result);

		$result = Router::match(array("Sessions::create", 'id' => 'foo'));
		$this->assertNull($result);

		$result = Router::match("Posts::index");
		$this->assertEqual('/posts', $result);
	}

	/**
	 * Tests that routes can be created with shorthand strings, i.e. `'Controller::action'` and
	 * `array('Controller::action', 'id' => '...')`.
	 *
	 * @return void
	 */
	public function testStringParameterConnect() {
		Router::connect('/posts/{:id:[0-9a-f]{24}}', 'Posts::edit');

		$result = Router::match(array(
			'controller' => 'posts', 'action' => 'edit', 'id' => '4bbf25bd8ead0e5180130000'
		));
		$expected = '/posts/4bbf25bd8ead0e5180130000';
		$this->assertEqual($expected, $result);

		$result = Router::match(array(
			'controller' => 'posts', 'action' => 'view', 'id' => '4bbf25bd8ead0e5180130000'
		));
		$this->assertNull($result);

		Router::reset();
		Router::connect('/posts/{:page:[0-9]+}', array('Posts::index', 'page' => '1'));

		$result = Router::match(array('controller' => 'posts', 'page' => '5'));
		$expected = '/posts/5';
		$this->assertEqual($expected, $result);

		$result = Router::match(array('Posts::index', 'page' => '10'));
		$expected = '/posts/10';
		$this->assertEqual($expected, $result);

		$request = new Request(array('url' => '/posts/13'));
		$result = Router::process($request);
		$expected = array('controller' => 'posts', 'action' => 'index', 'page' => '13');
		$this->assertEqual($expected, $result->params);
	}

	/**
	 * Tests that routing is fully reset when calling `Router::reset()`.
	 *
	 * @return void
	 */
	public function testResettingRoutes() {
		Router::connect('/{:controller}', array('controller' => 'posts'));
		$this->request->url = '/hello';

		$expected = array('controller' => 'hello', 'action' => 'index');
		$result = Router::parse($this->request);
		$this->assertEqual($expected, $result->params);

		Router::reset();
		$this->assertNull(Router::parse($this->request));
	}

	/**
	 * Tests matching routes where the route template is a static string with no insert parameters.
	 *
	 * @return void
	 */
	public function testRouteMatchingWithNoInserts() {
		Router::connect('/login', array('controller' => 'sessions', 'action' => 'add'));
		$result = Router::match(array('controller' => 'sessions', 'action' => 'add'));
		$this->assertEqual('/login', $result);
		$this->assertFalse(Router::match(array('controller' => 'sessions', 'action' => 'index')));
	}

	/**
	 * Test matching routes with only insert parameters and no default values.
	 *
	 * @return void
	 */
	public function testRouteMatchingWithOnlyInserts() {
		Router::connect('/{:controller}');
		$this->assertEqual('/posts', Router::match(array('controller' => 'posts')));

		$result = Router::match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertFalse($result);
	}

	/**
	 * Test matching routes with insert parameters which have default values.
	 *
	 * @return void
	 */
	public function testRouteMatchingWithInsertsAndDefaults() {
		Router::connect('/{:controller}/{:action}', array('action' => 'archive'));
		$this->assertEqual('/posts', Router::match(array('controller' => 'posts')));

		$result = Router::match(array('controller' => 'posts', 'action' => 'archive'));
		$this->assertEqual('/posts/archive', $result);

		Router::reset();
		Router::connect('/{:controller}/{:action}', array('controller' => 'users'));

		$result = Router::match(array('action' => 'view'));
		$this->assertEqual('/users/view', $result);

		$result = Router::match(array('controller' => 'posts', 'action' => 'view'));
		$this->assertEqual('/posts/view', $result);

		$result = Router::match(array('controller' => 'posts', 'action' => 'view', 'id' => '2'));
		$this->assertFalse($result);
	}

	/**
	 * Tests getting routes using `Router::get()`, and checking to see if the routes returned match
	 * the routes connected.
	 *
	 * @return void
	 */
	public function testRouteRetrieval() {
		$expected = Router::connect('/hello', array('controller' => 'posts', 'action' => 'index'));
		$result = Router::get(0);
		$this->assertIdentical($expected, $result);

		list($result) = Router::get();
		$this->assertIdentical($expected, $result);
	}

	public function testStringUrlGeneration() {
		$result = Router::match('/posts');
		$expected = '/posts';
		$this->assertEqual($expected, $result);

		$result = Router::match('/posts');
		$this->assertEqual($expected, $result);

		$result = Router::match('/posts/view/5');
		$expected = '/posts/view/5';
		$this->assertEqual($expected, $result);

		$request = new Request(array('base' => '/my/web/path'));
		$result = Router::match('/posts', $request);
		$expected = '/my/web/path/posts';
		$this->assertEqual($expected, $result);

		$result = Router::match('mailto:foo@localhost');
		$expected = 'mailto:foo@localhost';
		$this->assertEqual($expected, $result);

		$result = Router::match('#top');
		$expected = '#top';
		$this->assertEqual($expected, $result);
	}

	public function testWithWildcardString() {
		Router::connect('/add/{:args}', array('controller' => 'tests', 'action' => 'add'));

		$expected = '/add';
		$result = Router::match('/add');
		$this->assertEqual($expected, $result);

		$expected = '/add/alke';
		$result = Router::match('/add/alke');
		$this->assertEqual($expected, $result);
	}

	public function testWithWildcardArray() {
		Router::connect('/add/{:args}', array('controller' => 'tests', 'action' => 'add'));

		$expected = '/add';
		$result = Router::match(array('controller' => 'tests', 'action' => 'add'));
		$this->assertEqual($expected, $result);

		$expected = '/add/alke';
		$result = Router::match(array(
			'controller' => 'tests', 'action' => 'add', 'args' => array('alke')
		));
		$this->assertEqual($expected, $result);

		$expected = '/add/alke/php';
		$result = Router::match(array(
			'controller' => 'tests', 'action' => 'add', 'args' => array('alke', 'php')
		));
		$this->assertEqual($expected, $result);
	}

	public function testProcess() {
		Router::connect('/add/{:args}', array('controller' => 'tests', 'action' => 'add'));
		$request = Router::process(new Request(array('url' => '/add/foo/bar')));

		$params = array('controller' => 'tests', 'action' => 'add', 'args' => array('foo', 'bar'));
		$this->assertEqual($params, $request->params);
		$this->assertEqual(array('controller'), $request->persist);

		$request = Router::process(new Request(array('url' => '/remove/foo/bar')));
		$this->assertFalse($request->params);
	}

	/**
	 * Tests that a request context with persistent parameters generates URLs where those parameters
	 * are properly taken into account.
	 *
	 * @return void
	 */
	public function testParameterPersistence() {
		Router::connect('/add/{:args}', array('controller' => 'tests', 'action' => 'add'), array(
			'persist' => array('controller', 'action')
		));
		$request = Router::process(new Request(array('url' => '/add/foo/bar', 'base' => '')));
		$path = Router::match(array('args' => array('baz', 'dib')), $request);
		$this->assertEqual('/add/baz/dib', $path);
	}

	/**
	 * Tests that persistent parameters can be overridden with nulled-out values.
	 *
	 * @return void
	 */
	public function testOverridingPersistentParameters() {
		Router::connect(
			'/admin/{:controller}/{:action}',
			array('admin' => true),
			array('persist' => array('admin', 'controller'))
		);
		Router::connect('/{:controller}/{:action}');

		$request = Router::process(new Request(array('url' => '/admin/posts/add', 'base' => '')));
		$expected = array('controller' => 'posts', 'action' => 'add', 'admin' => true);
		$this->assertEqual($expected, $request->params);
		$this->assertEqual(array('admin', 'controller'), $request->persist);

		$url = Router::match(array('action' => 'archive'), $request);
		$this->assertEqual('/admin/posts/archive', $url);

		$url = Router::match(array('action' => 'archive', 'admin' => null), $request);
		$this->assertEqual('/posts/archive', $url);
	}

	/**
	 * Tests passing a closure handler to `Router::connect()` to bypass or augment default
	 * dispatching.
	 *
	 * @return void
	 */
	public function testRouteHandler() {
		Router::connect('/login', 'Users::login');

		Router::connect('/users/login', array(), function($request) {
			return new Response(array(
				'location' => array('controller' => 'users', 'action' => 'login')
			));
		});

		$result = Router::process(new Request(array('url' => '/users/login')));
		$this->assertTrue($result instanceof Response);

		$headers = array('location' => '/login');
		$this->assertEqual($headers, $result->headers);
	}

	/**
	 * Tests that a successful match against a route with template `'/'` operating at the root of
	 * a domain never returns an empty string.
	 *
	 * @return void
	 */
	public function testMatchingEmptyRoute() {
		Router::connect('/', 'Users::view');

		$request = new Request(array('base' => '/'));
		$url = Router::match(array('controller' => 'users', 'action' => 'view'), $request);
		$this->assertEqual('/', $url);

		$request = new Request(array('base' => ''));
		$url = Router::match(array('controller' => 'users', 'action' => 'view'), $request);
		$this->assertEqual('/', $url);
	}
}

?>