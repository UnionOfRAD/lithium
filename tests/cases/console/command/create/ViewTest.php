<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console\command\create;

use lithium\console\command\create\View;
use lithium\console\Request;
use lithium\core\Libraries;

class ViewTest extends \lithium\test\Unit {

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

	public function testRun() {
		$this->request->params += [
			'command' => 'create', 'template' => 'test-view', 'action' => 'view',
			'args' => ['Posts', 'index.html']
		];

		$plateFolder = $this->_testPath . '/create_test/extensions/command/create/template';
		if (!is_dir($plateFolder)) {
			mkdir($plateFolder, 0755, true);
		}
		file_put_contents($plateFolder . '/test-view.txt.php', '|{:name}|{:plural}|{:singular}|');

		$view = new View([
			'request' => $this->request, 'classes' => $this->classes
		]);
		$view->run('view');
		$expected = "index.html.php created in views/posts.\n";
		$result = $view->response->output;
		$this->assertEqual($expected, $result);

		$expected = '|Posts|posts|post|';
		$result = file_get_contents($this->_testPath . '/create_test/views/posts/index.html.php');
		$this->assertEqual($expected, $result);
	}
}

?>