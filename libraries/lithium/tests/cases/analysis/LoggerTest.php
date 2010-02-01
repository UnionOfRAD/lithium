<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\analysis;

use \lithium\analysis\Logger;
use \lithium\util\Collection;
use \lithium\tests\mocks\analysis\MockLoggerAdapter;

/**
 * Logger adapter test case
 */
class LoggerTest extends \lithium\test\Unit {

	public function setUp() {
		Logger::config(array('default' => array('adapter' => new MockLoggerAdapter())));
	}

	public function tearDown() {
		Logger::reset();
	}

	public function testConfig() {
		$test = new MockLoggerAdapter();
		$config = array('logger' => array(
			'adapter' => $test,
			'filters' => array()
		));

		$result = Logger::config($config);
		$this->assertNull($result);

		$result = Logger::config();
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

		$this->assertFalse(Logger::write('default', 'Test message.'));
	}

	public function testWrite() {
		$result = Logger::write('default', 'value');
		$this->assertTrue($result);
	}

	public function testIntegrationWriteFile() {
		$config = array('default' => array('adapter' => 'File'));
		Logger::config($config);

		$result = Logger::write('default', 'Message line 1');
		$this->assertTrue(file_exists(LITHIUM_APP_PATH . '/resources/tmp/logs/default.log'));

		$expected = "Message line 1\n";
		$result = file_get_contents(LITHIUM_APP_PATH . '/resources/tmp/logs/default.log');
		$this->assertEqual($expected, $result);

		$result = Logger::write('default', 'Message line 2');
		$this->assertTrue($result);

		$expected = "Message line 1\nMessage line 2\n";
		$result = file_get_contents(LITHIUM_APP_PATH . '/resources/tmp/logs/default.log');
		$this->assertEqual($expected, $result);

		unlink(LITHIUM_APP_PATH . '/resources/tmp/logs/default.log');
	}
}

?>