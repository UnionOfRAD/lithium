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
use lithium\tests\mocks\action\MockPostsController;
use lithium\tests\mocks\action\MockRenderAltController;
use lithium\tests\mocks\action\MockControllerRequest;

class ControllerTest extends \lithium\test\Unit {

	/**
	 * Tests that controllers can be instantiated with custom request objects.
	 */
	public function testConstructionWithCustomRequest() {
		$request = new MockControllerRequest();
		$postsController = new MockPostsController(compact('request'));
		$result = get_class($postsController->request);
		$this->assertEqual($result, 'lithium\tests\mocks\action\MockControllerRequest');
	}

	/**
	 * Tests the use of `Controller::__invoke()` for dispatching requests to action methods.  Also
	 * tests that using PHP's callable syntax yields the same result as calling `__invoke()`
	 * explicitly.
	 */
	public function testMethodInvocation() {
		$postsController = new MockPostsController();
		$result = $postsController->__invoke(null, ['action' => 'index', 'args' => []]);

		$this->assertInstanceOf('lithium\action\Response', $result);
		$this->assertEqual('List of posts', $result->body());
		$this->assertEqual(['Content-Type' => 'text/plain; charset=UTF-8'], $result->headers);

		$result2 = $postsController(null, ['action' => 'index', 'args' => []]);
		$this->assertEqual($result2, $result);

		$postsController = new MockPostsController();
		$this->assertException('/Unhandled media type/', function() use ($postsController) {
			$postsController(null, ['action' => 'index', 'args' => [true]]);
		});

		$result = $postsController->access('_render');
		$this->assertEqual($result['data'], ['foo' => 'bar']);

		$postsController = new MockPostsController();
		$this->assertException('/Unhandled media type/', function() use ($postsController) {
			$postsController(null, ['action' => 'view', 'args' => ['2']]);
		});

		$result = $postsController->access('_render');
		$this->assertEqual($result['data'], ['This is a post']);
	}

	/**
	 * Tests that calls to `Controller::redirect()` correctly write redirect headers to the
	 * response object.
	 */
	public function testRedirectResponse() {
		$postsController = new MockPostsController();

		$result = $postsController(null, ['action' => 'delete']);
		$this->assertEqual($result->body(), '');

		$headers = ['Location' => '/posts', 'Content-Type' => 'text/html'];
		$this->assertEqual($result->headers, $headers);

		$postsController = new MockPostsController();
		$result = $postsController(null, ['action' => 'delete', 'args' => ['5']]);

		$this->assertEqual($result->body(), 'Deleted 5');
		$this->assertFalse($postsController->stopped);

		$postsController = new MockPostsController(['classes' => [
			'response' => 'lithium\tests\mocks\action\MockControllerResponse'
		]]);
		$this->assertFalse($postsController->stopped);

		$postsController->__invoke(null, ['action' => 'send']);
		$this->assertTrue($postsController->stopped);

		$result = $postsController->access('_render');
		$this->assertTrue($result['hasRendered']);

		$this->assertEqual($postsController->response->body(), null);
		$this->assertEqual(
			$postsController->response->headers,
			['Location' => '/posts', 'Content-Type' => 'text/html']
		);
	}

	/**
	 * Tests calling `Controller::render()` with parameters to render an alternate template from
	 * the default.
	 */
	public function testRenderWithAlternateTemplate() {
		$postsController = new MockPostsController(['classes' => [
			'media' => 'lithium\tests\mocks\action\MockMediaClass',
			'response' => 'lithium\tests\mocks\action\MockResponse'
		]]);

		$result = $postsController(null, ['action' => 'view2']);
		$this->assertEqual('view', $result->options['template']);
		$this->assertEqual('default', $result->options['layout']);

		$result = $postsController(null, ['action' => 'view3']);
		$this->assertEqual('view', $result->options['template']);
		$this->assertFalse($result->options['layout']);
	}

	/**
	 * Tests that requests where the controller class is specified manually continue to route to
	 * the correct template path.
	 */
	public function testRenderWithNamespacedController() {
		$request = new Request();
		$request->params['controller'] = 'lithium\tests\mocks\action\MockPostsController';

		$controller = new MockPostsController(compact('request') + ['classes' => [
			'media' => 'lithium\tests\mocks\action\MockMediaClass',
			'response' => 'lithium\tests\mocks\action\MockResponse'
		]]);

		$controller->render();
		$this->assertEqual('mock_posts', $controller->response->options['controller']);
	}

	/**
	 * Verifies that data array is passed on to controller's response.
	 */
	public function testRenderWithDataArray() {
		$request = new Request();
		$request->params['controller'] = 'lithium\tests\mocks\action\MockPostsController';

		$controller = new MockPostsController(compact('request') + ['classes' => [
			'media' => 'lithium\tests\mocks\action\MockMediaClass',
			'response' => 'lithium\tests\mocks\action\MockResponse'
		]]);

		$controller->set(['set' => 'data']);
		$controller->render(['data' => ['render' => 'data']]);

		$expected = [
			'set' => 'data',
			'render' => 'data'
		];
		$this->assertEqual($expected, $controller->response->data);
	}

	/**
	 * Verifies that the Controller does not modify data when passed an array (or RecordSet)
	 * with a single element.
	 */
	public function testRenderWithDataSingleIndexedArray() {
		$request = new Request();
		$request->params['controller'] = 'lithium\tests\mocks\action\MockPostsController';

		$controller = new MockPostsController(compact('request') + ['classes' => [
			'media' => 'lithium\tests\mocks\action\MockMediaClass',
			'response' => 'lithium\tests\mocks\action\MockResponse'
		]]);

		$expected = [['id' => 1]];
		$controller->render(['data' => $expected]);

		$this->assertEqual($expected, $controller->response->data);
	}

	/**
	 * Verifies that protected methods (i.e. prefixed with '_'), and methods declared in the
	 * Controller base class cannot be accessed.
	 */
	public function testProtectedMethodAccessAttempt() {
		$postsController = new MockPostsController();
		$this->assertException('/^Attempted to invoke a private method/', function() use ($postsController) {
			$postsController->__invoke(null, ['action' => 'redirect']);
		});

		$postsController = new MockPostsController();
		$this->assertException('/^Attempted to invoke a private method/', function() use ($postsController) {
			$postsController->__invoke(null, ['action' => '_safe']);
		});
	}

	public function testResponseStatus() {
		$postsController = new MockPostsController(['classes' => [
			'response' => 'lithium\tests\mocks\action\MockControllerResponse'
		]]);
		$this->assertFalse($postsController->stopped);

		$postsController(null, ['action' => 'notFound']);

		$result = $postsController->access('_render');
		$this->assertTrue($result['hasRendered']);

		$expected = ['code' => 404, 'message' => 'Not Found'];
		$result = $postsController->response->status;
		$this->assertEqual($expected, $result);
		$result = $postsController->response->body();
		$this->assertEqual($expected, $result);
	}

	public function testResponseTypeBasedOnRequestType() {
		$request = new MockControllerRequest();
		$request->params['type'] = 'json';

		$postsController = new MockPostsController([
			'request' => $request,
			'classes' => [
				'response' => 'lithium\tests\mocks\action\MockControllerResponse'
			]
		]);
		$this->assertFalse($postsController->stopped);

		$postsController($request, ['action' => 'type']);

		$expected = [
			'type' => 'json', 'data' => ['data' => 'test'], 'auto' => true,
			'layout' => 'default', 'template' => 'type', 'hasRendered' => true, 'negotiate' => false
		];
		$result = $postsController->access('_render');
		$this->assertEqual($expected, $result);

		$result = $postsController->response->headers('Content-Type');
		$this->assertEqual('application/json; charset=UTF-8', $result);

		$result = $postsController->response->body();
		$this->assertEqual(['data' => 'test'], $result);
	}

	public function testResponseTypeBasedOnRequestParamsType() {
		$request = new MockControllerRequest();
		$request->params['type'] = 'json';

		$postsController = new MockPostsController([
			'request' => $request,
			'classes' => [
				'response' => 'lithium\tests\mocks\action\MockControllerResponse'
			]
		]);
		$this->assertFalse($postsController->stopped);

		$postsController->__invoke($request, ['action' => 'type']);

		$expected = [
			'type' => 'json', 'data' => ['data' => 'test'], 'auto' => true,
			'layout' => 'default', 'template' => 'type', 'hasRendered' => true, 'negotiate' => false
		];
		$result = $postsController->access('_render');
		$this->assertEqual($expected, $result);

		$result = $postsController->response->headers('Content-Type');
		$this->assertEqual('application/json; charset=UTF-8', $result);

		$expected = ['data' => 'test'];
		$result = $postsController->response->body();
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that `$_render['template']` can be manually set in a controller action and will not be
	 * overwritten.
	 */
	public function testManuallySettingTemplate() {
		$postsController = new MockPostsController(['classes' => [
			'media' => 'lithium\tests\mocks\action\MockMediaClass',
			'response' => 'lithium\tests\mocks\action\MockResponse'
		]]);
		$postsController(new Request(), ['action' => 'changeTemplate']);
		$result = $postsController->access('_render');
		$this->assertEqual('foo', $result['template']);
	}

	public function testRenderPropertyInheritance() {
		$controller = new MockRenderAltController();

		$expected = [
			'data' => ['foo' => 'bar'], 'layout' => 'alternate', 'type' => null,
			'auto' => true, 'template' => null, 'hasRendered' => false, 'negotiate' => false
		];
		$result = $controller->access('_render');
		$this->assertEqual($expected, $result);
	}

	public function testSetData() {
		$postController = new MockPostsController();

		$setData = ['foo' => 'bar'];
		$postController->set($setData);
		$_render = $postController->access('_render');
		$data = $_render['data'];
		$expected = $setData;
		$this->assertEqual($expected, $data);

		$setData = ['foo' => 'baz'];
		$postController->set($setData);
		$_render = $postController->access('_render');
		$data = $_render['data'];
		$expected = $setData;
		$this->assertEqual($expected, $data);
	}

	public function testResponseTypeBasedOnRequestHeaderType() {
		$request = new MockControllerRequest([
			'env' => ['HTTP_ACCEPT' => 'application/json,*/*']
		]);

		$postsController = new MockPostsController([
			'request' => $request,
			'classes' => ['response' => 'lithium\tests\mocks\action\MockControllerResponse'],
			'render' => ['negotiate' => true]
		]);
		$this->assertFalse($postsController->stopped);

		$postsController($request, ['action' => 'type']);

		$expected = [
			'type' => 'json', 'data' => ['data' => 'test'], 'auto' => true,
			'layout' => 'default', 'template' => 'type', 'hasRendered' => true, 'negotiate' => true
		];
		$result = $postsController->access('_render');
		$this->assertEqual($expected, $result);

		$result = $postsController->response->headers('Content-Type');
		$this->assertEqual('application/json; charset=UTF-8', $result);

		$result = $postsController->response->body();
		$this->assertEqual(['data' => 'test'], $result);
	}

	/**
	 * Tests that requests which are dispotched with the controller route parameter specified as
	 * a fully-qualified class name are able to locate their templates correctly.
	 */
	public function testDispatchingWithExplicitControllerName() {
		$request = new Request(['url' => '/']);
		$request->params = [
			'controller' => 'lithium\tests\mocks\action\MockPostsController',
			'action' => 'index'
		];

		$postsController = new MockPostsController(compact('request'));
		$postsController->__invoke($request, $request->params);
	}

	public function testNonExistentFunction() {
		$postsController = new MockPostsController();

		$this->assertException("Action `foo` not found.", function() use ($postsController) {
			$postsController(new Request(), ['action' => 'foo']);
		});
	}

	/**
	 * Tests that the library of the controller is automatically added to the default rendering
	 * options.
	 */
	public function testLibraryScoping() {
		$request = new Request();
		$request->params['controller'] = 'lithium\tests\mocks\action\MockPostsController';

		$controller = new MockPostsController(compact('request') + ['classes' => [
			'media' => 'lithium\tests\mocks\action\MockMediaClass',
			'response' => 'lithium\tests\mocks\action\MockResponse'
		]]);

		$controller->render();
		$this->assertEqual('lithium', $controller->response->options['library']);
	}
}

?>