<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console\command;

use lithium\tests\mocks\console\command\MockCreate;
use lithium\console\Request;
use lithium\core\Libraries;
use lithium\data\Connections;

class CreateTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = [];

	protected $_testPath = null;

	public function skip() {
		$this->_testPath = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($this->_testPath), "Path `{$this->_testPath}` is not writable.");
	}

	public function setUp() {
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = [];

		Libraries::add('create_test', ['path' => $this->_testPath . '/create_test']);
		$this->request = new Request(['input' => fopen('php://temp', 'w+')]);
		$this->request->params = ['library' => 'create_test', 'action' => null];

		Connections::add('default', [
			'type' => null,
			'adapter' => 'lithium\tests\mocks\data\model\MockDatabase'
		]);
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		$this->_cleanUp();
	}

	public function testConstruct() {
		$create = new MockCreate(['request' => $this->request]);

		$expected = 'create_test';
		$result = $create->library;
		$this->assertEqual($expected, $result);
	}

	public function testNonExistentCommand() {
		$this->request->params['args'] = ['does_not_exist', 'anywhere'];
		$create = new MockCreate(['request' => $this->request]);

		$result = $create->run('does_not_exist');
		$this->assertFalse($result);

		$expected = "does_not_exist could not be created.\n";
		$result = $create->response->error;
		$this->assertEqual($expected, $result);
	}

	public function testNamespace() {
		$create = new MockCreate(['request' => $this->request]);
		$this->request->params['command'] = 'one';

		$expected = 'create_test\\two';
		$result = $create->invokeMethod('_namespace', [
			$this->request, [
				'spaces' => ['one' => 'two']
			]
		]);
		$this->assertEqual($expected, $result);
	}

	public function testSave() {
		chdir($this->_testPath);
		$this->request->params = ['library' => 'create_test', 'template' => 'test'];
		$create = new MockCreate(['request' => $this->request]);
		$result = $create->save([
			'namespace' => 'create_test\tests\cases\models',
			'use' => 'create_test\models\Posts',
			'class' => 'PostTest',
			'methods' => "\tpublic function testCreate() {\n\n\t}\n"
		]);
		$this->assertNotEmpty($result);

		$result = $this->_testPath . '/create_test/tests/cases/models/PostTest.php';
		$this->assertFileExists($result);

		$this->_cleanUp();
	}

	public function testRunWithoutCommand() {
		$create = new MockCreate(['request' => $this->request]);

		$result = $create->run();
		$this->assertFalse($result);

		$result = $create->response->output;
		$this->assertEmpty($result);
	}

	public function testRunNotSaved() {
		$this->request->params = [
			'library' => 'not_here', 'command' => 'create', 'action' => 'model',
			'args' => ['model', 'Posts']
		];
		$create = new MockCreate(['request' => $this->request]);

		$result = $create->run('model');
		$this->assertFalse($result);

		$expected = "model could not be created.\n";
		$result = $create->response->error;
		$this->assertEqual($expected, $result);
	}

	public function testRunWithModelCommand() {
		$this->request->params = [
			'library' => 'create_test', 'command' => 'create', 'action' => 'model',
			'args' => ['Posts']
		];

		$create = new MockCreate(['request' => $this->request]);

		$create->run('model');

		$expected = 'model';
		$result = $create->request->command;
		$this->assertEqual($expected, $result);

		$result = $this->_testPath . '/create_test/models/Posts.php';
		$this->assertFileExists($result);
	}

	public function testRunWithTestModelCommand() {
		$this->request->params = [
			'library' => 'create_test', 'command' => 'create', 'action' => 'test',
			'args' => ['model', 'Posts']
		];

		$create = new MockCreate(['request' => $this->request]);

		$create->run('test');

		$expected = 'model';
		$result = $create->request->command;
		$this->assertEqual($expected, $result);

		$result = $this->_testPath . '/create_test/tests/cases/models/PostsTest.php';
		$this->assertFileExists($result);
	}

	public function testRunWithTestControllerCommand() {
		$this->request->params = [
			'library' => 'create_test', 'command' => 'create', 'action' => 'test',
			'args' => ['controller', 'Posts']
		];

		$create = new MockCreate(['request' => $this->request]);

		$create->run('test');

		$expected = 'controller';
		$result = $create->request->command;
		$this->assertEqual($expected, $result);

		$result = $this->_testPath . '/create_test/tests/cases/controllers/PostsControllerTest.php';
		$this->assertFileExists($result);
	}

	public function testRunWithTestOtherCommand() {
		$this->request->params = [
			'library' => 'create_test', 'command' => 'create', 'action' => 'test',
			'args' => ['something', 'Posts']
		];

		$create = new MockCreate(['request' => $this->request]);
		$create->run('test');

		$expected = 'something';
		$result = $create->request->command;
		$this->assertEqual($expected, $result);

		$result = $this->_testPath . '/create_test/tests/cases/something/PostsTest.php';
		$this->assertFileExists($result);
	}

	public function testRunAll() {
		$this->request->params = [
			'library' => 'create_test', 'command' => 'create', 'action' => 'Posts',
			'args' => []
		];

		$create = new MockCreate(['request' => $this->request]);
		$create->run('Posts');

		$result = $this->_testPath . '/create_test/models/Posts.php';
		$this->assertFileExists($result);

		$result = $this->_testPath . '/create_test/controllers/PostsController.php';
		$this->assertFileExists($result);

		$result = $this->_testPath . '/create_test/tests/cases/models/PostsTest.php';
		$this->assertFileExists($result);

		$result = $this->_testPath . '/create_test/tests/cases/controllers/PostsControllerTest.php';
		$this->assertFileExists($result);
	}
}

?>