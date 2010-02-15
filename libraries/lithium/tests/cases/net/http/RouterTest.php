<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use \lithium\net\http\Route;
use \lithium\net\http\Router;
use \lithium\action\Request;

class RouterTest extends \lithium\test\Unit {

	public $request = null;

	protected $_routes = array();

	public function setUp() {
		$this->request = new Request();
		$this->_routes = Router::get();
		Router::connect(null);
	}

	public function tearDown() {
		Router::connect(null);

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
			$this->assertEqual($expected, $result);
		}
	}

	public function testRouteMatchingWithDefaultParameters() {
		Router::connect('/{:controller}/{:action}', array('action' => 'view'));
		$expected = array('controller' => 'posts', 'action' => 'view');

		foreach (array('/posts/view', '/posts', 'posts', 'posts/view', 'posts/view/') as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result);
		}
		$expected['action'] = 'index';

		foreach (array('/posts/index', 'posts/index', 'posts/index/') as $url) {
			$this->request->url = $url;
			$result = Router::parse($this->request);
			$this->assertEqual($expected, $result);
		}

		$this->request->url = '/posts/view/1';
		$result = Router::parse($this->request);
		$this->assertNull($result);
	}

	/**
	 * Tests that routing is fully reset when `Router::connect()` is passed a null value
	 *
	 * @return void
	 */
	public function testResettingRoutes() {
		Router::connect('/{:controller}', array('controller' => 'posts'));
		$this->request->url = '/hello';

		$expected = array('controller' => 'hello', 'action' => 'index');
		$result = Router::parse($this->request);
		$this->assertEqual($expected, $result);

		Router::connect(null);
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

		Router::connect(null);
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
}

?>