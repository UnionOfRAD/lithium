<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command;

use \lithium\tests\mocks\console\command\MockBuild;
use \lithium\console\Request;

class BuildTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = array();

	protected $_paths = array();


	public function setUp() {
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();
		$this->_paths['tests'] = LITHIUM_APP_PATH . '/resources/tmp/tests';
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		$this->_cleanup();
	}

	protected function _cleanup() {
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

	public function testSave() {
		chdir($this->_paths['tests']);
		$build = new MockBuild(array('request' => $this->request));
		$build->path = $this->_paths['tests'];
		$result = $build->save('test', array(
			'namespace' => 'app\tests\cases\models',
			'use' => 'app\models\Post',
			'class' => 'PostTest',
			'methods' => "\tpublic function testCreate() {\n\n\t}\n",
		));
		$this->assertTrue($result);

		$result = $this->_paths['tests'] . '/app/tests/cases/models/PostTest.php';
		$this->assertTrue(file_exists($result));

		$this->_cleanup();
	}

	public function testRunWithoutCommand() {
		$build = new MockBuild(array('request' => $this->request));

		$expected = null;
		$result = $build->run();
		$this->assertEqual($expected, $result);
	}

	public function testRunWithModelCommand() {
		$build = new MockBuild(array('request' => $this->request));

		$this->request->params = array(
			'command' => 'build', 'action' => 'run', 'args' => array('model')
		);
		$build->run('model');

		$expected = 'model';
		$result = $build->request->params['command'];
		$this->assertEqual($expected, $result);
	}

	public function testRunWithTestModelCommand() {
		$this->request->params = array(
			'command' => 'build', 'action' => 'run',
			'args' => array('test', 'model', 'Post'),
			'path' => $this->_paths['tests']
		);
		$build = new MockBuild(array('request' => $this->request));

		$expected = $this->_paths['tests'];
		$result = $build->path;
		$this->assertEqual($expected, $result);

		$build->run('test', 'model');

		$expected = 'test';
		$result = $build->request->params['command'];
		$this->assertEqual($expected, $result);

		$result = $this->_paths['tests'] . '/app/tests/cases/models/PostTest.php';
		$this->assertTrue(file_exists($result));
	}

	public function testRunWithTestOtherCommand() {
		$build = new MockBuild(array('request' => $this->request));
		$this->request->params = array(
			'command' => 'build', 'action' => 'run',
			'args' => array('test', 'something', 'Post'),
			'path' => $this->_paths['tests']
		);
		$build->run('test', 'something');

		$expected = 'test';
		$result = $build->request->params['command'];
		$this->assertEqual($expected, $result);

		$result = $this->_paths['tests'] . '/app/tests/cases/something/PostTest.php';
		$this->assertTrue(file_exists($result));
	}
}

?>