<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command\build;

use \lithium\console\command\Build;
use \lithium\console\command\build\Test;
use \lithium\console\Request;

class TestTest extends \lithium\test\Unit {

	public function setUp() {
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->classes = array('response' => '\lithium\tests\mocks\console\MockResponse');
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();
		$this->_paths['tests'] = LITHIUM_APP_PATH . '/resources/tmp/tests';
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);

		$rmdir = function($value) use( &$rmdir) {
			$result = is_file($value) ? unlink($value) : null;
			if ($result == null && is_dir($value)) {
				$result = array_filter(glob($value . '/*'), $rmdir);
				rmdir($value);
			}
			return false;
		};
		$rmdir($this->_paths['tests'] . '/app');
	}

	public function testModel() {
		$test = new Test(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$test->path = $this->_paths['tests'];
		$test->model('Post');
		$expected = "PostTest created for model Post.\n";
		$result = $test->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<'test'


namespace app\tests\cases\models;

use \app\models\Post;

class PostTest extends \lithium\test\Unit {

	public function setUp() {}

	public function tearDown() {}


}


test;
		$replace = array("<?php", "?>");
		$result = str_replace($replace, '',
			file_get_contents($this->_paths['tests'] . '/app/tests/cases/models/PostTest.php')
		);
		$this->assertEqual($expected, $result);
	}

	public function testMockModel() {
		$test = new Test(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$test->path = $this->_paths['tests'];
		$test->mock('model', 'Post');
		$expected = "MockPost created for model Post.\n";
		$result = $test->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<'test'


namespace app\tests\mocks\models;

class MockPost extends \app\models\Post {


}


test;
		$replace = array("<?php", "?>");
		$result = str_replace($replace, '',
			file_get_contents($this->_paths['tests'] . '/app/tests/mocks/models/MockPost.php')
		);
		$this->assertEqual($expected, $result);
	}
}

?>