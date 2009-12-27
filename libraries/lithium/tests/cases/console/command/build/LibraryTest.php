<?php

namespace lithium\tests\cases\console\command\build;

use \Phar;
use \lithium\console\command\build\Library;
use \lithium\core\Libraries;
use \lithium\console\Request;

class LibraryTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = array();

	protected $_testPath = null;

	public function setUp() {
		$this->classes = array('response' => '\lithium\tests\mocks\console\MockResponse');
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();
		$this->_testPath = LITHIUM_APP_PATH . '/resources/tmp/tests';

		Libraries::add('build_test', array('path' => $this->_testPath .'/build_test'));
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);

		$rmdir = function($value) use( &$rmdir) {
			if(is_dir($value) && $dir = @opendir($value)) {
				while (($path = readdir($dir)) !== false) {
					if ($path === '.' || $path === '..') continue;
					$result = is_dir($value . '/' . $path) ? $rmdir($value . '/' . $path) : null;
					$result = is_file($value . '/' . $path) ? unlink($value . '/' . $path) : null;
				}
				closedir($dir);
				rmdir($value);
			}
		};

		$rmdir($this->_testPath . '/build_test');
		$rmdir($this->_testPath . '/new');
	}

	public function testArchive() {
		$this->request->params['library'] = 'app';
		$app = new Library(array('request' => $this->request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->archive($this->_testPath . '/app', 'app');
		$this->assertEqual($expected, $result);

		$expected = "app created in " . LITHIUM_APP_PATH . "/resources/tmp/tests\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/app.phar');
	}

	public function testRun() {
		$this->request->params['library'] = 'app';
		$app = new Library(array('request' => $this->request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->run($this->_testPath . '/new', $this->_testPath . '/app');
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists($this->_testPath . '/new'));

		$expected = "new created in " . LITHIUM_APP_PATH . "/resources/tmp/tests\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/app.phar.gz');
	}
}

?>