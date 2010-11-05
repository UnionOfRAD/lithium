<?php

namespace lithium\tests\cases\console\command;

use lithium\console\command\Help;
use lithium\console\Request;
use lithium\tests\mocks\console\command\MockCommandHelp;

class HelpTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = array();

	protected $_testPath = null;

	public function setUp() {
		$this->classes = array('response' => 'lithium\tests\mocks\console\MockResponse');
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$_SERVER['argv'] = array();

		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->request->params = array('library' => 'build_test');
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
	}

	public function testRun() {
		$help = new Help(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$expected = true;
		$result = $help->run();
		$this->assertEqual($expected, $result);

		$expected = "COMMANDS\n";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);

		$expected = preg_quote($expected);
		$result = $help->response->output;
		$pattern = "/\s+test\s+Runs a given set of tests and outputs the results\./ms";
		$this->assertPattern($pattern, $result);

	}

	public function testRunWithName() {
		$help = new Help(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$expected = true;
		$result = $help->run('Test');
		$this->assertEqual($expected, $result);

		$expected = "li3 test --case=CASE --group=GROUP --filters=FILTERS [ARGS]";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);

		$expected = "OPTIONS\n    --case=CASE\n";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);

		$expected = "missing\n";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);
	}

	public function testApiClass() {
		$help = new Help(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$expected = null;
		$result = $help->api('lithium.util.Inflector');
		$this->assertEqual($expected, $result);

		$expected = "Utility for modifying format of words";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);
	}

	public function testApiMethod() {
		$help = new Help(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$expected = null;
		$result = $help->api('lithium.util.Inflector', 'method');
		$this->assertEqual($expected, $result);

		$expected = "rules [type] [config]";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);
	}

	public function testApiMethodWithName() {
		$help = new Help(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$expected = null;
		$result = $help->api('lithium.util.Inflector', 'method', 'rules');
		$this->assertEqual($expected, $result);

		$expected = "rules [type] [config]";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);
	}

	public function testApiProperty() {
		$help = new Help(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$expected = null;
		$result = $help->api('lithium.net.Message', 'property');
		$this->assertEqual($expected, $result);

		$expected = "    --host=HOST\n        The hostname for this endpoint.";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);
	}

	public function testApiPropertyWithName() {
		$help = new Help(array(
			'request' => $this->request, 'classes' => $this->classes
		));
		$expected = null;
		$result = $help->api('lithium.net.Message', 'property');
		$this->assertEqual($expected, $result);

		$expected = "    --host=HOST\n        The hostname for this endpoint.";
		$expected = preg_quote($expected);
		$result = $help->response->output;
		$this->assertPattern("/{$expected}/", $result);
	}
}

?>