<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright	 Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license	   http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command\g11n;

use lithium\core\Libraries;
use lithium\console\Request;
use lithium\console\command\g11n\Extract;
use lithium\g11n\Catalog;

class ExtractTest extends \lithium\test\Unit {

	protected $_path;

	public $command;

	public function skip() {
		$this->_path = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($this->_path), "{$this->_path} is not writable.");
	}

	public function setUp() {
		$this->command = new Extract(array(
				'request' => new Request(array('input' => fopen('php://temp', 'w+'))),
				'classes' => array('response' => 'lithium\tests\mocks\console\MockResponse')
			));
		mkdir($this->command->source = "{$this->_path}/source");
		mkdir($this->command->destination = "{$this->_path}/destination");
	}

	protected function _writeInput(array $input = array()) {
		foreach ($input as $input) {
			fwrite($this->command->request->input, $input . "\n");
		}
		rewind($this->command->request->input);
	}

	public function tearDown() {
		$this->_cleanUp();
	}

	public function testInit() {
		$command = new Extract();
		$this->assertEqual(LITHIUM_APP_PATH, $command->source);
		$this->assertEqual(Libraries::get(true, 'resources') . '/g11n', $command->destination);
	}

	public function testFailRead() {
		$this->_writeInput(array('', '', '', ''));
		$result = $this->command->run();

		$expected = 1;
		$this->assertIdentical($expected, $result);

		$expected = "Yielded no items.\n";
		$result = $this->command->response->error;
		$this->assertEqual($expected, $result);
	}

	public function testFailWrite() {
		rmdir($this->command->destination);

		$file = "{$this->_path}/source/a.html.php";
		$data = <<<EOD
<h2>Flowers</h2>
<?=\$t('Apples are green.'); ?>
EOD;
		file_put_contents($file, $data);

		$configs = Catalog::config();
		$configKey = key($configs);
		$this->_writeInput(array($configKey, 'q', '', '', '', 'y'));
		$result = $this->command->run();
		$expected = 1;
		$this->assertIdentical($expected, $result);

		$expected = "Failed to write template.\n";
		$result = $this->command->response->error;
		$this->assertEqual($expected, $result);
	}

	public function testDefaultConfiguration() {
		$file = "{$this->_path}/source/a.html.php";
		$data = <<<EOD
<h2>Flowers</h2>
<?=\$t('Apples are green.'); ?>
EOD;
		file_put_contents($file, $data);

		$configs = Catalog::config();
		$configKey1 = key($configs);
		next($configs);
		$configKey2 = key($configs);
		$this->_writeInput(array($configKey1, $configKey2, '', 'y'));
		$result = $this->command->run();
		$expected = 0;
		$this->assertIdentical($expected, $result);

		$expected = '/.*Yielded 1 item.*/';
		$result = $this->command->response->output;
		$this->assertPattern($expected, $result);

		$file = "{$this->_path}/destination/message_default.pot";
		$result = file_exists($file);
		$this->assertTrue($result);

		$result = file_get_contents($file);
		$expected = '/msgid "Apples are green\."/';
		$this->assertPattern($expected, $result);

		$expected = '#/resources/tmp/tests/source(/|\\\\)a.html.php:2#';
		$this->assertPattern($expected, $result);

		$result = $this->command->response->error;
		$this->assertFalse($result);
	}
}

?>