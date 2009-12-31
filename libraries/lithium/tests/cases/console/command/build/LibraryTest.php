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
		chdir($this->_testPath);
		Libraries::add('build_test', array('path' => $this->_testPath . '/build_test'));
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		Libraries::remove('build_test');
		unset($this->request);
	}

	public function testRun() {
		$this->request->params['library'] = 'build_test';
		$app = new Library(array('request' => $this->request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->run($this->_testPath . '/build_test');
		$this->assertEqual($expected, $result);

		$expected = "build_test created in {$this->_testPath} from ";
		$expected .= LITHIUM_LIBRARY_PATH . "/lithium/console/command/build/template/app.phar.gz\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);
	}

	public function testArchive() {
		$this->request->params['library'] = 'build_test';
		$app = new Library(array('request' => $this->request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->archive($this->_testPath . '/build_test', $this->_testPath . '/build_test');
		$this->assertEqual($expected, $result);

		$expected = "build_test.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/build_test\n";

		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/build_test.phar');
	}

	public function testRunWithFullPaths() {
		$this->request->params['library'] = 'build_test';
		$app = new Library(array('request' => $this->request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->run($this->_testPath . '/new', $this->_testPath . '/build_test.phar.gz');
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists($this->_testPath . '/new'));

		$expected = "new created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/build_test.phar.gz\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/build_test.phar.gz');
	}

	public function testArchiveNoLibrary() {
		chdir('new');
		$request = new Request(array('input' => fopen('php://temp', 'w+')));
		$request->params['library'] = 'does_not_exist';
		$app = new Library(array('request' => $request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->archive();
		$this->assertEqual($expected, $result);

		$expected = "new.phar.gz created in {$this->_testPath} from ";
		$expected .= "{$this->_testPath}/new\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/new.phar');
		Phar::unlinkArchive($this->_testPath . '/new.phar.gz');
		$this->_cleanUp('tests/new');
		rmdir($this->_testPath . '/new');
	}

	public function testRunWhenLibraryDoesNotExist() {
		chdir($this->_testPath);
		$request = new Request(array('input' => fopen('php://temp', 'w+')));
		$request->params['library'] = 'does_not_exist';
		$app = new Library(array('request' => $request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->run();
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists($this->_testPath . '/new'));

		$expected = "new created in {$this->_testPath} from ";
		$expected .= LITHIUM_LIBRARY_PATH . "/lithium/console/command/build/template/app.phar.gz\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		$this->_cleanUp();
	}
}

?>