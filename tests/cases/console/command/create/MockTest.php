<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console\command\create;

use lithium\console\command\create\Mock;
use lithium\console\Request;
use lithium\core\Libraries;

class MockTest extends \lithium\test\Unit {

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

	public function testMockModel() {
		$this->request->params += [
			'command' => 'create', 'action' => 'mock',
			'args' => ['model', 'Posts']
		];
		$mock = new Mock([
			'request' => $this->request, 'classes' => $this->classes
		]);
		$mock->run('mock');
		$expected = "MockPosts created in tests/mocks/models/MockPosts.php.\n";
		$result = $mock->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<'test'


namespace create_test\tests\mocks\models;

class MockPosts extends \create_test\models\Posts {


}


test;
		$replace = ["<?php", "?>"];
		$result = str_replace($replace, '',
			file_get_contents($this->_testPath . '/create_test/tests/mocks/models/MockPosts.php')
		);
		$this->assertEqual($expected, $result);
	}
}

?>