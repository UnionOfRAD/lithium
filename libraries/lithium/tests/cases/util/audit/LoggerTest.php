<?php

namespace lithium\tests\cases\util\audit;

use \lithium\util\audit\Logger;
use \lithium\util\Collection;
use \lithium\tests\mocks\util\audit\MockLoggerAdapter;

/**
 * Logger adapter test case
 *
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
			'filters' => array(),
			'strategies' => array()
		));

		$result = Logger::config($config);
		$expected = new Collection(array('items' => $config));

		$this->assertEqual($expected, $result);
	}

	public function testReset() {
		$test = new MockLoggerAdapter();
		$config = array('logger' => array('adapter' => $test, 'filters' => array()));

		$result = Logger::config($config);

		$result = Logger::reset();
		$this->assertNull($result);

		$result = Logger::config();
		$this->assertEqual(new Collection(), $result);
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