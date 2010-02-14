<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command\create;

use \lithium\console\command\create\Controller;
use \lithium\console\Request;
use \lithium\core\Libraries;

class ControllerTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = array();

	protected $_testPath = null;

	public function skip() {
		$this->_testPath = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$this->skipIf(!is_writable($this->_testPath), "{$this->_testPath} is not writable.");
	}

	public function setUp() {
		$this->classes = array('response' => '\lithium\tests\mocks\console\MockResponse');
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();

		Libraries::add('create_test', array('path' => $this->_testPath . '/create_test'));
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->request->params = array('library' => 'create_test');
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		$this->_cleanUp();
	}

	public function testRun() {
		$controller = new Controller(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$controller->path = $this->_testPath;
		$controller->run('Posts');
		$expected = "PostsController created in create_test\\controllers.\n";
		$result = $controller->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<'test'


namespace create_test\controllers;

use \create_test\models\Post;

class PostsController extends \lithium\action\Controller {

	public function index() {
		$posts = Post::all();
		return compact('posts');
	}

	public function view($id = null) {
		$post = Post::find($id);
		return compact('post');
	}

	public function add() {
		if (!empty($this->request->data)) {
			$post = Post::create($this->request->data);
			if ($post->save()) {
				$this->redirect(array(
					'controller' => 'posts', 'action' => 'view',
					'args' => array($post->id)
				));
			}
		}
		if (empty($post)) {
			$post = Post::create();
		}
		return compact('post');
	}

	public function edit($id = null) {
		$post = Post::find($id);
		if (empty($post)) {
			$this->redirect(array('controller' => 'posts', 'action' => 'index'));
		}
		if (!empty($this->request->data)) {
			if ($post->save($this->request->data)) {
				$this->redirect(array(
					'controller' => 'posts', 'action' => 'view',
					'args' => array($post->id)
				));
			}
		}
		return compact('post');
	}
}


test;
		$replace = array("<?php", "?>");
		$result = str_replace($replace, '',
			file_get_contents($this->_testPath . '/create_test/controllers/PostsController.php')
		);
		$this->assertEqual($expected, $result);
	}
}

?>