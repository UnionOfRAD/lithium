<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\analysis;

use lithium\core\Libraries;
use lithium\analysis\Logger;
use lithium\tests\mocks\analysis\MockLoggerAdapter;

/**
 * Logger adapter test case
 */
class LoggerTest extends \lithium\test\Unit {

	public function skip() {
		$this->_testPath = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($this->_testPath), "{$this->_testPath} is not readable.");
	}

	public function setUp() {
		Logger::config(array('default' => array('adapter' => new MockLoggerAdapter())));
	}

	public function tearDown() {
		Logger::reset();
	}

	public function testConfig() {
		$test = new MockLoggerAdapter();
		$config = array('logger' => array('adapter' => $test, 'filters' => array()));

		$result = Logger::config($config);
		$this->assertNull($result);

		$result = Logger::config();
		$config['logger'] += array('priority' => true);
		$expected = $config;
		$this->assertEqual($expected, $result);
	}

	public function testReset() {
		$test = new MockLoggerAdapter();
		$config = array('logger' => array('adapter' => $test, 'filters' => array()));

		$result = Logger::config($config);

		$result = Logger::reset();
		$this->assertNull($result);

		$result = Logger::config();
		$this->assertFalse($result);

		$this->assertFalse(Logger::write('info', 'Test message.'));
	}

	public function testWrite() {
		$result = Logger::write('info', 'value');
		$this->assertTrue($result);
	}

	public function testIntegrationWriteFile() {
		$base = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		$config = array('default' => array(
			'adapter' => 'File', 'timestamp' => false, 'format' => "{:message}\n"
		));
		Logger::config($config);

		$result = Logger::write('info', 'Message line 1');
		$this->assertTrue(file_exists($base . '/info.log'));

		$expected = "Message line 1\n";
		$result = file_get_contents($base . '/info.log');
		$this->assertEqual($expected, $result);

		$result = Logger::write('info', 'Message line 2');
		$this->assertTrue($result);

		$expected = "Message line 1\nMessage line 2\n";
		$result = file_get_contents($base . '/info.log');
		$this->assertEqual($expected, $result);

		unlink($base . '/info.log');
	}

	public function testWriteWithInvalidPriority() {
		$this->expectException("Attempted to write log message with invalid priority `foo`.");
		Logger::foo("Test message");
	}

	public function testWriteByName() {
		$base = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		Logger::config(array('default' => array(
			'adapter' => 'File',
			'timestamp' => false,
			'priority' => false,
			'format' => "{:message}\n"
		)));

		$this->assertFalse(file_exists($base . '/info.log'));

		$this->assertFalse(Logger::write('info', 'Message line 1'));
		$this->assertFalse(file_exists($base . '/info.log'));

		$this->assertTrue(Logger::write(null, 'Message line 1', array('name' => 'default')));

		$expected = "Message line 1\n";
		$result = file_get_contents($base . '/.log');
		$this->assertEqual($expected, $result);

		unlink($base . '/.log');
	}
}

?>