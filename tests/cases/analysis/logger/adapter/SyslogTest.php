<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\analysis\logger\adapter;

use lithium\analysis\Logger;
use lithium\analysis\logger\adapter\Syslog;

/**
 * Syslog adapter test.
 */
class SyslogTest extends \lithium\test\Unit {

	public function setUp() {
		$this->syslog = new Syslog();
		Logger::config(['syslog' => ['adapter' => $this->syslog]]);
	}

	public function testConfiguration() {
		$loggers = Logger::config();
		$result = isset($loggers['syslog']);
		$this->assertTrue($result);
	}

	public function testConstruct() {
		$expected = [
			'identity' => false,
			'options' => LOG_ODELAY,
			'facility' => LOG_USER,
			'init' => true
		];
		$result = $this->syslog->_config;
		$this->assertEqual($expected, $result);

		$syslog = new Syslog([
			'identity' => 'SyslogTest',
			'priority' => LOG_DEBUG
		]);
		$expected = [
			'identity' => 'SyslogTest',
			'options' => LOG_ODELAY,
			'facility' => LOG_USER,
			'priority' => LOG_DEBUG,
			'init' => true
		];
		$result = $syslog->_config;
		$this->assertEqual($expected, $result);
	}

	public function testWrite() {
		$result = Logger::write('info', 'SyslogTest message...', ['name' => 'syslog']);
		$this->assertNotEmpty($result);
	}
}

?>