<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\action;

use lithium\action\Request;
use lithium\action\Response;
use lithium\net\http\Router;
use lithium\action\Dispatcher;
use lithium\tests\mocks\action\MockDispatcher;

class DispatcherTest extends \lithium\test\Unit {

	protected $_routes = array();

	public function setUp() {
		$this->_routes = Router::get();
		Router::reset();
	}

	public function tearDown() {
		Router::reset();

		foreach ($this->_routes as $route) {
			Router::connect($route);
		}
	}

	public function testRun() {
		Router::connect('/', array('controller' => 'test', 'action' => 'test'));
		MockDispatcher::run(new Request(array('url' => '/')));

		$result = end(MockDispatcher::$dispatched);
		$expected = array('controller' => 'Test', 'action' => 'test');
		$this->assertEqual($expected, $result->params);
	}

	public function testRunWithNoRouting() {
		$this->expectException('/Could not route request/');
		MockDispatcher::run(new Request(array('url' => '/')));
	}

	public function testApplyRulesControllerCasing() {
		$params = array('controller' => 'test', 'action' => 'test');
		$expected = array('controller' => 'Test', 'action' => 'test');
		$this->assertEqual($expected, Dispatcher::applyRules($params));

		$params = array('controller' => 'Test', 'action' => 'test');
		$this->assertEqual($params, Dispatcher::applyRules($params));

		$params = array('controller' => 'test_one', 'action' => 'test');
		$expected = array('controller' => 'TestOne', 'action' => 'test');
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesWithNamespacedController() {
		$params = array('controller' => 'li3_test\\Test', 'action' => 'test');
		$expected = array('controller' => 'li3_test\\Test', 'action' => 'test');
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesDotNamespacing() {
		$params = array('controller' => 'li3_test.test', 'action' => 'test');
		$expected = array(
			'library' => 'li3_test', 'controller' => 'li3_test.Test', 'action' => 'test'
		);
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesLibraryKeyNamespacing() {
		$params = array('library' => 'li3_test', 'controller' => 'test', 'action' => 'test');
		$expected = array(
			'library' => 'li3_test', 'controller' => 'li3_test.Test', 'action' => 'test'
		);
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesNamespacingCollision() {
		$params = array('library' => 'li3_one', 'controller' => 'li3_two.test', 'action' => 'test');
		$expected = array(
			'library' => 'li3_one', 'controller' => 'li3_two.Test', 'action' => 'test'
		);
		$this->assertEqual($expected, Dispatcher::applyRules($params));

		$params = array('library' => 'li3_one', 'controller' => 'li3_two\Test', 'action' => 'test');
		$expected = array(
			'library' => 'li3_one', 'controller' => 'li3_two\Test', 'action' => 'test'
		);
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testConfigManipulation() {
		$config = MockDispatcher::config();
		$expected = array('rules' => array());
		$this->assertEqual($expected, $config);

		MockDispatcher::config(array('rules' => array(
			'admin' => array('action' => 'admin_{:action}')
		)));

		Router::connect('/', array('controller' => 'test', 'action' => 'test', 'admin' => true));
		MockDispatcher::run(new Request(array('url' => '/')));

		$result = end(MockDispatcher::$dispatched);
		$expected = array('action' => 'admin_test', 'controller' => 'Test', 'admin' => true);
		$this->assertEqual($expected, $result->params);
	}

	public function testControllerLookupFail() {
		Dispatcher::config(array('classes' => array('router' => __CLASS__)));

		$this->expectException("/Controller `SomeNonExistentController` not found/");
		Dispatcher::run(new Request(array('url' => '/')));
	}

	public function testPluginControllerLookupFail() {
		Dispatcher::config(array('classes' => array('router' => __CLASS__)));

		$this->expectException("/Controller `some_invalid_plugin.Controller` not found/");
		Dispatcher::run(new Request(array('url' => '/plugin')));
	}

	public function testCall() {
		$result = MockDispatcher::run(new Request(array('url' => '/call')));
		$this->assertEqual('Working', $result->body);
	}

	public function testAutoHandler() {
		$result = MockDispatcher::run(new Request(array('url' => '/auto')));
		$this->assertEqual(array('location: /redirect'), $result->headers());
	}

	public static function process($request) {
		if ($request->url == '/auto') {
			return new Response(array('location' => '/redirect'));
		}

		$params = array(
			'' => array('controller' => 'some_non_existent_controller', 'action' => 'index'),
			'/plugin' => array(
				'controller' => 'some_invalid_plugin.controller', 'action' => 'index'
			),
			'/call' => array('action' => 'index', 'controller' => function($request) {
				return new Response(array('body' => 'Working'));
			})
		);

		if (isset($params[$request->url])) {
			$request->params = $params[$request->url];
		}
		return $request;
	}
}

?>