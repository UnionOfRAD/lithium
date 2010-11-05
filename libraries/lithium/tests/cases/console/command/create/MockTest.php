<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command\create;

use lithium\console\command\Create;
use lithium\console\command\create\Mock;
use lithium\console\Request;
use lithium\core\Libraries;

class MockTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = array();

	protected $_testPath = null;

	public function skip() {
		$this->_testPath = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$this->skipIf(!is_writable($this->_testPath), "{$this->_testPath} is not writable.");
	}

	public function setUp() {
		$this->classes = array('response' => 'lithium\tests\mocks\console\MockResponse');
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();

		Libraries::add('create_test', array('path' => $this->_testPath . '/create_test'));
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->request->params = array('library' => 'create_test');
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		$this->_cleanUp();
	}

	public function testMockModel() {
		$this->request->params += array(
			'command' => 'create', 'action' => 'mock',
			'args' => array('model', 'Post')
		);
		$mock = new Mock(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$mock->path = $this->_testPath;
		$mock->run('mock');
		$expected = "MockPost created in create_test\\tests\\mocks\\models.\n";
		$result = $mock->response->output;
		$this->assertEqual($expected, $result);

		$expected = <<<'test'


namespace create_test\tests\mocks\models;

class MockPost extends \create_test\models\Post {


}


test;
		$replace = array("<?php", "?>");
		$result = str_replace($replace, '',
			file_get_contents($this->_testPath . '/create_test/tests/mocks/models/MockPost.php')
		);
		$this->assertEqual($expected, $result);
	}
}

?>