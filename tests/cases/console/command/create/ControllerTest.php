<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console\command\create;

use ReflectionMethod;
use lithium\console\Request;
use lithium\console\command\create\Controller;
use lithium\core\Libraries;

class ControllerTest extends \lithium\test\Unit {

	public $request;

	public $classes = [];

	protected $_backup = [];

	protected $_testPath = null;

	public function skip() {
		$this->_testPath = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($this->_testPath), "Path `{$this->_testPath}` is not writable.");
	}

	public function setUp() {
		$this->classes = ['response' => 'lithium\tests\mocks\console\MockResponse'];
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = [];

		Libraries::add('create_test', ['path' => $this->_testPath . '/create_test']);
		$this->request = new Request(['input' => fopen('php://temp', 'w+')]);
		$this->request->params = ['library' => 'create_test'];
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		$this->_cleanUp();
	}

	public function testClass() {
		$this->request->params += [
			'command' => 'controller', 'action' => 'Posts'
		];
		$model = new Controller([
			'request' => $this->request, 'classes' => $this->classes
		]);
		$method = new ReflectionMethod($model, '_class');
		$method->setAccessible(true);

		$expected = 'PostsController';
		$result = $method->invokeArgs($model, [$this->request]);
		$this->assertEqual($expected, $result);
	}

	public function testUse() {
		$this->request->params += [
			'command' => 'controller', 'action' => 'Posts'
		];
		$model = new Controller([
			'request' => $this->request, 'classes' => $this->classes
		]);

		$method = new ReflectionMethod($model, '_use');
		$method->setAccessible(true);

		$expected = 'create_test\\models\\Posts';
		$result = $method->invokeArgs($model, [$this->request]);
		$this->assertEqual($expected, $result);
	}

	public function testRun() {
		$this->request->params += [
			'command' => 'create', 'action' => 'controller',
			'args' => ['Posts']
		];
		$controller = new Controller([
			'request' => $this->request, 'classes' => $this->classes
		]);
		$controller->run('controller');
		$expected = "PostsController created in controllers/PostsController.php.\n";
		$result = $controller->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<'test'


namespace create_test\controllers;

use create_test\models\Posts;
use lithium\action\DispatchException;

class PostsController extends \lithium\action\Controller {

	public function index() {
		$posts = Posts::all();
		return compact('posts');
	}

	public function view() {
		$post = Posts::first($this->request->id);
		return compact('post');
	}

	public function add() {
		$post = Posts::create();

		if (($this->request->data) && $post->save($this->request->data)) {
			return $this->redirect(['Posts::view', 'args' => [$post->id]]);
		}
		return compact('post');
	}

	public function edit() {
		$post = Posts::find($this->request->id);

		if (!$post) {
			return $this->redirect('Posts::index');
		}
		if (($this->request->data) && $post->save($this->request->data)) {
			return $this->redirect(['Posts::view', 'args' => [$post->id]]);
		}
		return compact('post');
	}

	public function delete() {
		if (!$this->request->is('post') && !$this->request->is('delete')) {
			$msg = "Posts::delete can only be called with http:post or http:delete.";
			throw new DispatchException($msg);
		}
		Posts::find($this->request->id)->delete();
		return $this->redirect('Posts::index');
	}
}


test;
		$replace = ["<?php", "?>"];
		$result = str_replace($replace, '',
			file_get_contents($this->_testPath . '/create_test/controllers/PostsController.php')
		);
		$this->assertEqual($expected, $result);
	}
}

?>