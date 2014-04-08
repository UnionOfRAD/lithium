<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright	 Copyright 2014, Union of RAD (http://union-of-rad.org)
 * @license	   http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command\g11n;

use lithium\core\Libraries;
use lithium\console\Request;
use lithium\console\command\g11n\Extract;
use lithium\g11n\Catalog;

class ExtractTest extends \lithium\test\Unit {

	protected $_backup = array();

	protected $_path;

	public $command;

	public function skip() {
		$this->_path = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($this->_path), "Path `{$this->_path}` is not writable.");
	}

	public function setUp() {
		$this->_backup['catalogConfig'] = Catalog::config();
		Catalog::reset();

		$this->command = new Extract(array(
			'request' => new Request(array('input' => fopen('php://temp', 'w+'))),
			'classes' => array('response' => 'lithium\tests\mocks\console\MockResponse')
		));
		mkdir($this->command->source = "{$this->_path}/source");
		mkdir($this->command->destination = "{$this->_path}/destination");
	}

	public function tearDown() {
		Catalog::config($this->_backup['catalogConfig']);
		$this->_cleanUp();
	}

	protected function _writeInput(array $input = array()) {
		foreach ($input as $input) {
			fwrite($this->command->request->input, $input . "\n");
		}
		rewind($this->command->request->input);
	}

	/**
	 * Added realpath() to fix issues when lithium is linked in to the app's libraries directory.
	 */
	public function testInit() {
		$command = new Extract();
		$this->assertEqual(realpath(Libraries::get(true, 'path')), realpath($command->source));
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
		$this->_writeInput(array($configKey, '', '', '', '', 'y'));
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
		$this->assertFileExists($file);

		$result = file_get_contents($file);
		$expected = '/msgid "Apples are green\."/';
		$this->assertPattern($expected, $result);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:2#';
		$this->assertPattern($expected, $result);

		$result = $this->command->response->error;
		$this->assertEmpty($result);
	}

	public function testContextsMultiple() {
		$file = "{$this->_path}/source/a.html.php";
		$data = <<<EOD
<h2>Balls</h2>
<?=\$t('Ball', array('context' => 'Spherical object')); ?>
<?=\$t('Ball', array('context' => 'Social gathering')); ?>
<?=\$t('Ball'); ?>
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

		$expected = '/.*Yielded 3 item.*/';
		$result = $this->command->response->output;
		$this->assertPattern($expected, $result);

		$file = "{$this->_path}/destination/message_default.pot";
		$this->assertFileExists($file);

		$result = file_get_contents($file);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:2';
		$expected .= "\n";
		$expected .= 'msgctxt "Spherical object"';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:3';
		$expected .= "\n";
		$expected .= 'msgctxt "Social gathering"';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:4';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$result = $this->command->response->error;
		$this->assertEmpty($result);
	}

	public function testContextsWithMultipleOccurences() {
		$file = "{$this->_path}/source/a.html.php";
		$data = <<<EOD
<h2>Balls</h2>
<?=\$t('Ball', array('context' => 'Spherical object')); ?>
<?=\$t('Ball', array('context' => 'Social gathering')); ?>
<?=\$t('Ball'); ?>
<?=\$t('Ball', array('context' => 'Social gathering')); ?>
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

		$expected = '/.*Yielded 3 item.*/';
		$result = $this->command->response->output;
		$this->assertPattern($expected, $result);

		$file = "{$this->_path}/destination/message_default.pot";
		$this->assertFileExists($file);

		$result = file_get_contents($file);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:3';
		$expected .= "\n";
		$expected .= '.*/tmp/tests/source(/|\\\\)a.html.php:5';
		$expected .= "\n";
		$expected .= 'msgctxt "Social gathering"';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$result = $this->command->response->error;
		$this->assertEmpty($result);
	}

	public function testContextsWithOtherParams() {
		$file = "{$this->_path}/source/a.html.php";
		$data = <<<EOD
<?=\$t('Ball', array('context' => 'Social gathering', 'foo' => 'bar')); ?>
<?=\$t('Ball', array('foo' => 123, 'bar' => baz(), 'context' => 'Spherical object')); ?>
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

		$expected = '/.*Yielded 2 item.*/';
		$result = $this->command->response->output;
		$this->assertPattern($expected, $result);

		$file = "{$this->_path}/destination/message_default.pot";
		$this->assertFileExists($file);

		$result = file_get_contents($file);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:1';
		$expected .= "\n";
		$expected .= 'msgctxt "Social gathering"';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:2';
		$expected .= "\n";
		$expected .= 'msgctxt "Spherical object"';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$result = $this->command->response->error;
		$this->assertEmpty($result);
	}

	public function testContextsWithAmbiguousContextTokens() {
		$file = "{$this->_path}/source/a.html.php";
		$data = <<<EOD
<?=\$t('Ball', array('context', 'foo' => 'context', 'context' => 'Spherical object')); ?>
<?=\$t('Ball', array('foo' => \$t('context'), 'context' => 'Social gathering')); ?>
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

		$file = "{$this->_path}/destination/message_default.pot";
		$this->assertFileExists($file);

		$result = file_get_contents($file);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:1';
		$expected .= "\n";
		$expected .= 'msgctxt "Spherical object"';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:2';
		$expected .= "\n";
		$expected .= 'msgctxt "Social gathering"';
		$expected .= "\n";
		$expected .= 'msgid "Ball"#';
		$this->assertPattern($expected, $result);

		$result = $this->command->response->error;
		$this->assertEmpty($result);
	}

	public function testContextsNested() {
		$file = "{$this->_path}/source/a.html.php";
		$data = <<<EOD
<?=\$t('Robin, {:a}', array('a' => \$t('Michael, {:b}', array('b' => \$t('Bruce', array('context' => 'Lee')), 'context' => 'Jackson')), 'context' => 'Hood')); ?>
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

		$expected = '/.*Yielded 3 item.*/';
		$result = $this->command->response->output;
		$this->assertPattern($expected, $result);

		$file = "{$this->_path}/destination/message_default.pot";
		$this->assertFileExists($file);

		$result = file_get_contents($file);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:1';
		$expected .= "\n";
		$expected .= 'msgctxt "Hood"';
		$expected .= "\n";
		$expected .= 'msgid "Robin, {:a}"#';
		$this->assertPattern($expected, $result);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:1';
		$expected .= "\n";
		$expected .= 'msgctxt "Jackson"';
		$expected .= "\n";
		$expected .= 'msgid "Michael, {:b}"#';
		$this->assertPattern($expected, $result);

		$expected = '#/tmp/tests/source(/|\\\\)a.html.php:1';
		$expected .= "\n";
		$expected .= 'msgctxt "Lee"';
		$expected .= "\n";
		$expected .= 'msgid "Bruce"#';
		$this->assertPattern($expected, $result);

		$result = $this->command->response->error;
		$this->assertEmpty($result);
	}
}

?>