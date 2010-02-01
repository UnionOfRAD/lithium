<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\action;

use \lithium\net\http\Router;
use \lithium\net\http\Request;
use \lithium\action\Dispatcher;
use \lithium\tests\mocks\action\MockDispatcher;

class DispatcherTest extends \lithium\test\Unit {

	protected $_routes = array();

	public function setUp() {
		$this->_routes = Router::get();
		Router::connect(null);
	}

	public function tearDown() {
		Router::connect(null);

		foreach ($this->_routes as $route) {
			Router::connect($route);
		}
	}

	public function testRun() {
		Router::connect('/', array('controller' => 'test', 'action' => 'test'));
		$request = new Request();
		$request->url = '/';
		MockDispatcher::run($request);

		$result = end(MockDispatcher::$dispatched);
		$expected = array('controller' => 'test', 'action' => 'test');
		$this->assertEqual($expected, $result->params);
	}
}

?>