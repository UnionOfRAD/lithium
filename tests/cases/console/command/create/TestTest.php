<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\console\command\create;

use lithium\console\command\create\Test;
use lithium\console\Request;
use lithium\core\Libraries;

class TestTest extends \lithium\test\Unit {

	public $request;

	public $classes = [];

	protected $_backup = [];

	protected $_testPath = null;

	public function skip() {
		$this->_testPath = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($this->_testPath), "Path `{$this->_testPath}` is not writable.");
	}

	public function setUp() {
		Libraries::cache(false);
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

	public function testTestModel() {
		$this->request->params += [
			'command' => 'create', 'action' => 'test',
			'args' => ['model', 'Posts']
		];
		$test = new Test([
			'request' => $this->request, 'classes' => $this->classes
		]);
		$test->run('test');
		$expected = "PostsTest created in tests/cases/models/PostsTest.php.\n";
		$result = $test->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<EOD


namespace create_test\\tests\\cases\\models;

use create_test\\models\\Posts;

class PostsTest extends \\lithium\\test\\Unit {

	public function setUp() {}

	public function tearDown() {}


}


EOD;
		$replace = ["<?php", "?>"];
		$result = str_replace($replace, '',
			file_get_contents($this->_testPath . '/create_test/tests/cases/models/PostsTest.php')
		);
		$this->assertEqual($expected, $result);
	}

	public function testTestModelWithMethods() {
		$this->_cleanUp();
		mkdir($this->_testPath . '/create_test/models/', 0755, true);
		$id = rand();
		$path = "create_test/models/Post{$id}s.php";

		$body = <<<EOD
<?php
namespace create_test\models;

class Post{$id}s {
	public function someMethod() {}
}
EOD;
		file_put_contents("{$this->_testPath}/{$path}", $body);

		$this->request->params += ['command' => 'create', 'action' => 'test', 'args' => [
			'model', "Post{$id}s"
		]];
		$test = new Test(['request' => $this->request, 'classes' => $this->classes]);
		$test->run('test');
		$expected = "Post{$id}sTest created in tests/cases/models/Post{$id}sTest.php.\n";
		$result = $test->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<EOD


namespace create_test\\tests\\cases\\models;

use create_test\\models\\Post{$id}s;

class Post{$id}sTest extends \\lithium\\test\\Unit {

	public function setUp() {}

	public function tearDown() {}

	public function testSomeMethod() {}
}


EOD;
		$replace = ["<?php", "?>"];
		$path = "create_test/tests/cases/models/Post{$id}sTest.php";
		$result = str_replace($replace, '', file_get_contents("{$this->_testPath}/{$path}"));
		$this->assertEqual($expected, $result);
	}
}

?>