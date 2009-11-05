<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\action;

use \Exception;
use \lithium\action\Controller;

class TestMediaClass extends \lithium\http\Media {

	public static function render(&$response, $data = null, $options = array()) {
		$response->options = $options;
		$response->data = $data;
	}
}

class PostsController extends Controller {

	public $stopped = false;

	public function index($test = false) {
		if ($test) {
			return array('foo' => 'bar');
		}
		return 'List of posts';
	}

	public function delete($id = null) {
		if (empty($id)) {
			return $this->redirect('/posts', array('exit' => false));
		}
		return "Deleted {$id}";
	}

	public function send() {
		$this->redirect('/posts');
	}

	public function view($id = null) {
		if (!empty($id)) {
			// throw new NotFoundException();
		}
		$this->render(array('text', 'data' => 'This is a post'));
	}

	public function view2($id = null) {
		$this->render('view');
	}

	public function view3($id = null) {
		$this->render(array('layout' => false, 'template' => 'view'));
	}

	protected function _safe() {
		throw new Exception('Something wrong happened');
	}

	public function access($var) {
		return $this->{$var};
	}

	protected function _stop() {
		$this->stopped = true;
	}
}

class ControllerRequest extends \lithium\action\Request {
}

class ControllerResponse extends \lithium\action\Response {

	public $hasRendered = false;

	public function render() {
		$this->hasRendered = true;
	}
}

class ControllerTest extends \lithium\test\Unit {

	/**
	 * Tests that render settings and dynamic class dependencies can properly be injected into the
	 * controller through the constructor.
	 *
	 * @return void
	 */
	public function testConstructionWithCustomProperties() {
		$postsController = new PostsController();

		$result = $postsController->access('_render');
		$this->assertIdentical($result['layout'], 'default');

		$result = $postsController->access('_classes');
		$this->assertIdentical($result['response'], '\lithium\action\Response');

		$postsController = new PostsController(array(
			'render' => array('layout' => false),
			'classes' => array('response' => '\app\extensions\http\Response')
		));

		$result = $postsController->access('_render');
		$this->assertIdentical($result['layout'], false);

		$result = $postsController->access('_classes');
		$this->assertIdentical($result['response'], '\app\extensions\http\Response');
	}

	/**
	 * Tests that controllers can be instantiated with custom request objects.
	 *
	 * @return void
	 */
	public function testConstructionWithCustomRequest() {
		$request = new ControllerRequest();
		$postsController = new PostsController(compact('request'));
		$result = get_class($postsController->request);
		$this->assertEqual($result, 'lithium\tests\cases\action\ControllerRequest');
	}

	/**
	 * Tests the use of `Controller::__invoke()` for dispatching requests to action methods.  Also
	 * tests that using PHP's callable syntax yields the same result as calling `__invoke()`
	 * explicitly.
	 *
	 * @return void
	 */
	public function testMethodInvokation() {
		$postsController = new PostsController();
		$result = $postsController->__invoke(null, array('action' => 'index', 'args' => array()));

		$this->assertTrue(is_a($result, 'lithium\action\Response'));
		$this->assertEqual($result->body(), 'List of posts');

		$headers = array('Content-type' => 'text/plain');
		$this->assertEqual($result->headers, $headers);

		$result2 = $postsController(null, array('action' => 'index', 'args' => array()));
		$this->assertEqual($result2, $result);

		$postsController = new PostsController();
		$this->expectException('/Template not found/');
		$result = $postsController->__invoke(null, array(
			'action' => 'index', 'args' => array(true)
		));

		$this->assertTrue(is_a($result, 'lithium\action\Response'));
		$this->assertEqual($result->body, '');

		$headers = array('Content-type' => 'text/html');
		$this->assertEqual($result->headers, $headers);

		$result = $postsController->access('_render');
		$this->assertEqual($result['data'], array('foo' => 'bar'));

		$postsController = new PostsController();
		$result = $postsController(null, array('action' => 'view', 'args' => array('2')));

		$this->assertTrue(is_a($result, 'lithium\action\Response'));
		$this->assertEqual($result->body, "Array\n(\n    [0] => This is a post\n)\n");

		$headers = array('status' => 200, 'Content-type' => 'text/plain');
		$this->assertEqual($result->headers(), $headers);

		$result = $postsController->access('_render');
		$this->assertEqual($result['data'], array('This is a post'));
	}

	/**
	 * Tests that calls to `Controller::redirect()` correctly write redirect headers to the
	 * response object.
	 *
	 * @return void
	 */
	public function testRedirectResponse() {
		$postsController = new PostsController();

		$result = $postsController->__invoke(null, array('action' => 'delete'));
		$this->assertEqual($result->body(), '');

		$headers = array('Location' => '/posts');
		$this->assertEqual($result->headers, $headers);

		$postsController = new PostsController();
		$result = $postsController(null, array('action' => 'delete', 'args' => array('5')));

		$this->assertEqual($result->body(), 'Deleted 5');
		$this->assertFalse($postsController->stopped);

		$postsController = new PostsController(array('classes' => array(
			'response' => __NAMESPACE__ . '\ControllerResponse'
		)));
		$this->assertFalse($postsController->stopped);

		$postsController->__invoke(null, array('action' => 'send'));
		$this->assertTrue($postsController->stopped);

		$result = $postsController->access('_render');
		$this->assertTrue($result['hasRendered']);

		$this->assertEqual($postsController->response->body(), null);
		$this->assertEqual(
			$postsController->response->headers,
			array('Location' => '/posts')
		);
	}

	/**
	 * Tests calling `Controller::render()` with parameters to render an alternate template from
	 * the default.
	 *
	 * @return void
	 */
	public function testRenderWithAlternateTemplate() {
		$postsController = new PostsController(array('classes' => array(
			'media' => __NAMESPACE__ . '\TestMediaClass'
		)));

		$result = $postsController(null, array('action' => 'view2'));
		$this->assertEqual('view', $result->options['template']);
		$this->assertEqual('default', $result->options['layout']);

		$result = $postsController(null, array('action' => 'view3'));
		$this->assertEqual('view', $result->options['template']);
		$this->assertFalse($result->options['layout']);
	}

	/**
	 * Verifies that protected methods (i.e. prefixed with '_'), and methods declared in the
	 * Controller base class cannot be accessed.
	 *
	 * @return void
	 */
	public function testProtectedMethodAccessAttempt() {
		$postsController = new PostsController();
		$this->expectException('/^Private/');
		$result = $postsController->__invoke(null, array('action' => 'redirect'));

		$this->assertEqual($result->body, null);
		$this->assertEqual($result->headers(), array());

		$postsController = new PostsController();
		$this->expectException('/^Private/');
		$result = $postsController->invoke('_safe');

		$this->assertEqual($result->body, null);
		$this->assertEqual($result->headers(), array());
	}
}

?>