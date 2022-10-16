<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\action;

use lithium\action\Request;
use lithium\action\Response;
use lithium\net\http\Router;
use lithium\action\Dispatcher;
use lithium\tests\mocks\action\MockDispatcher;
use lithium\util\Inflector;

class DispatcherTest extends \lithium\test\Unit {

	public function setUp() {
		$this->tearDown();
	}

	public function tearDown() {
		Router::reset();
		MockDispatcher::reset();
	}

	public function testRun() {
		Router::connect('/', ['controller' => 'test', 'action' => 'test']);
		MockDispatcher::run(new Request(['url' => '/']));

		$result = end(MockDispatcher::$dispatched);
		$expected = ['controller' => 'Test', 'action' => 'test'];
		$this->assertEqual($expected, $result->params);
	}

	public function testRunWithNoRouting() {
		$this->assertException('/Could not route request/', function() {
			MockDispatcher::run(new Request(['url' => '/']));
		});
	}

	/**
	 * Tests that POST requests to the / URL work as expected.
	 *
	 * This test belongs to the issue that POST requests (like submitting forms) to the /
	 * URL don't work as expected, because they immediately get redirected to the same URL but
	 * as GET requests (with no data attached to it). It veryfies that the Lithium dispatcher
	 * works as expected and returns the correct controller/action combination.
	 */
	public function testRunWithPostRoot() {
		Router::connect('/', ['controller' => 'test', 'action' => 'test']);
		$request = new Request(['url' => '/', 'env' => [
			'REQUEST_METHOD' => 'POST'
		]]);
		MockDispatcher::run($request);
		$expected = ['controller' => 'Test', 'action' => 'test'];
		$result = end(MockDispatcher::$dispatched);
		$this->assertEqual($expected, $result->params);
	}

	public function testApplyRulesControllerCasing() {
		$params = ['controller' => 'test', 'action' => 'test'];
		$expected = ['controller' => 'Test', 'action' => 'test'];
		$this->assertEqual($expected, Dispatcher::applyRules($params));

		$params = ['controller' => 'Test', 'action' => 'test'];
		$this->assertEqual($params, Dispatcher::applyRules($params));

		$params = ['controller' => 'test_one', 'action' => 'test'];
		$expected = ['controller' => 'TestOne', 'action' => 'test'];
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesWithNamespacedController() {
		$params = ['controller' => 'li3_test\\Test', 'action' => 'test'];
		$expected = ['controller' => 'li3_test\\Test', 'action' => 'test'];
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesDotNamespacing() {
		$params = ['controller' => 'li3_test.test', 'action' => 'test'];
		$expected = [
			'library' => 'li3_test', 'controller' => 'li3_test.Test', 'action' => 'test'
		];
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesLibraryKeyNamespacing() {
		$params = ['library' => 'li3_test', 'controller' => 'test', 'action' => 'test'];
		$expected = [
			'library' => 'li3_test', 'controller' => 'li3_test.Test', 'action' => 'test'
		];
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testApplyRulesNamespacingCollision() {
		$params = ['library' => 'li3_one', 'controller' => 'li3_two.test', 'action' => 'test'];
		$expected = [
			'library' => 'li3_one', 'controller' => 'li3_two.Test', 'action' => 'test'
		];
		$this->assertEqual($expected, Dispatcher::applyRules($params));

		$params = ['library' => 'li3_one', 'controller' => 'li3_two\Test', 'action' => 'test'];
		$expected = [
			'library' => 'li3_one', 'controller' => 'li3_two\Test', 'action' => 'test'
		];
		$this->assertEqual($expected, Dispatcher::applyRules($params));
	}

	public function testRunWithoutRules() {
		$config = MockDispatcher::config();
		$expected = ['rules' => []];
		$this->assertEqual($expected, $config);
	}

	public function testRunWithAdminActionRule() {
		MockDispatcher::config(['rules' => [
			'admin' => ['action' => 'admin_{:action}']
		]]);

		Router::connect('/', ['controller' => 'test', 'action' => 'test', 'admin' => true]);
		MockDispatcher::run(new Request(['url' => '/']));

		$result = end(MockDispatcher::$dispatched);
		$expected = ['action' => 'admin_test', 'controller' => 'Test', 'admin' => true];
		$this->assertEqual($expected, $result->params);
	}

	public function testRunWithGenericActionRule() {
		MockDispatcher::config(['rules' => [
			'action' => ['action' => function($params) {
				return Inflector::camelize(strtolower($params['action']), false);
			}]
		]]);

		Router::connect('/', ['controller' => 'test', 'action' => 'TeST-camelize']);
		MockDispatcher::run(new Request(['url' => '/']));

		$result = end(MockDispatcher::$dispatched);
		$expected = ['action' => 'testCamelize', 'controller' => 'Test'];
		$this->assertEqual($expected, $result->params);
	}

	public function testRunWithSpecialRuleAsCallable() {
		MockDispatcher::config(['rules' => function($params) {
			if (isset($params['admin'])) {
				return ['special' => ['action' => 'special_{:action}']];
			}
			return [];
		}]);

		Router::connect('/', ['controller' => 'test', 'action' => 'test', 'admin' => true]);
		Router::connect('/special', [
			'controller' => 'test', 'action' => 'test',
			'admin' => true, 'special' => true
		]);
		MockDispatcher::run(new Request(['url' => '/']));

		$result = end(MockDispatcher::$dispatched);
		$expected = ['action' => 'test', 'controller' => 'Test', 'admin' => true];
		$this->assertEqual($expected, $result->params);

		MockDispatcher::run(new Request(['url' => '/special']));

		$result = end(MockDispatcher::$dispatched);
		$expected = [
			'action' => 'special_test', 'controller' => 'Test',
			'admin' => true, 'special' => true
		];
		$this->assertEqual($expected, $result->params);
	}

	public function testRunWithContinuingRules() {
		MockDispatcher::config(['rules' => [
			'api' => ['action' => 'api_{:action}'],
			'admin' => ['action' => 'admin_{:action}']
		]]);

		Router::connect('/', [
			'controller' => 'test', 'action' => 'test', 'admin' => true, 'api' => true
		]);
		MockDispatcher::run(new Request(['url' => '/']));

		$result = end(MockDispatcher::$dispatched);
		$expected = [
			'action' => 'admin_api_test', 'controller' => 'Test', 'admin' => true, 'api' => true
		];
		$this->assertEqual($expected, $result->params);
	}

	public function testControllerLookupFail() {
		Dispatcher::config(['classes' => ['router' => __CLASS__]]);

		$this->assertException("/Controller `SomeNonExistentController` not found/", function() {
			Dispatcher::run(new Request(['url' => '/']));
		});
	}

	public function testPluginControllerLookupFail() {
		Dispatcher::config(['classes' => ['router' => __CLASS__]]);

		$this->assertException("/Controller `some_invalid_plugin.Controller` not found/", function() {
			Dispatcher::run(new Request(['url' => '/plugin']));
		});
	}

	public function testCall() {
		$result = MockDispatcher::run(new Request(['url' => '/call']));
		$this->assertEqual('Working', $result->body);
	}

	public function testAutoHandler() {
		$result = MockDispatcher::run(new Request(['url' => '/auto']));
		$this->assertEqual(['Location: /redirect'], $result->headers());
	}

	public static function process($request) {
		if ($request->url === '/auto') {
			return new Response(['location' => '/redirect']);
		}

		$params = [
			'/' => ['controller' => 'some_non_existent_controller', 'action' => 'index'],
			'/plugin' => [
				'controller' => 'some_invalid_plugin.controller', 'action' => 'index'
			],
			'/call' => ['action' => 'index', 'controller' => function($request) {
				return new Response(['body' => 'Working']);
			}]
		];

		if (isset($params[$request->url])) {
			$request->params = $params[$request->url];
		}
		return $request;
	}
}

?>