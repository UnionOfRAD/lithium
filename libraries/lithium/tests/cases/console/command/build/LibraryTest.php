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
		Libraries::add('build_test', array('path' => $this->_testPath .'/build_test'));
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
	}

	public function testArchive() {
		$this->skipIf(
			ini_get('phar.readonly') == '1',
			'Skipped test {:class}::{:function}() - INI setting phar.readonly = On'
		);

		$this->request->params['library'] = 'app';
		$app = new Library(array('request' => $this->request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->archive($this->_testPath . '/app', 'app');
		$this->assertEqual($expected, $result);

		$expected = "app.phar.gz created in " . LITHIUM_APP_PATH . "/resources/tmp/tests\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/app.phar');
	}

	public function testRun() {
		$this->request->params['library'] = 'app';
		$app = new Library(array('request' => $this->request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->run($this->_testPath . '/new', $this->_testPath . '/app.phar.gz');
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists($this->_testPath . '/new'));

		$expected = "new created in " . LITHIUM_APP_PATH . "/resources/tmp/tests\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		Phar::unlinkArchive($this->_testPath . '/app.phar.gz');
	}

	public function testArchiveNoLibrary() {
		$this->skipIf(
			ini_get('phar.readonly') == '1',
			'Skipped test {:class}::{:function}() - INI setting phar.readonly = On'
		);

		chdir('new');
		$request = new Request(array('input' => fopen('php://temp', 'w+')));
		$request->params['library'] = 'does_not_exist';
		$app = new Library(array('request' => $request, 'classes' => $this->classes));

		$expected = true;
		$result = $app->archive();
		$this->assertEqual($expected, $result);

		$expected = "new.phar.gz created in {$this->_testPath}\n";
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

		$expected = "new created in {$this->_testPath}\n";
		$result = $app->response->output;
		$this->assertEqual($expected, $result);

		$this->_cleanUp();
	}
}

?>