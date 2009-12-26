<?php

namespace lithium\tests\cases\util\audit\logger\adapter;

use \lithium\util\audit\Logger;
use \lithium\util\audit\logger\adapter\Syslog;

/**
 * Syslog adapter test.
 */
class SyslogTest extends \lithium\test\Unit {

	public function setUp() {
		$this->syslog = new Syslog();
		Logger::config(array('syslog' => array('adapter' => $this->syslog)));
	}

	public function testConfiguration() {
		$loggers = Logger::config();
		$result = isset($loggers['syslog']);
		$this->assertTrue($result);
	}

	public function testConstruct() {
		$expected = array(
			'identity' => false,
			'options' => LOG_ODELAY,
			'facility' => LOG_USER,
			'priority' => LOG_INFO,
			'init' => true
		);
		$result = $this->syslog->_config;
		$this->assertEqual($expected, $result);

		$syslog = new Syslog(array(
			'identity' => 'SyslogTest',
			'priority' => LOG_DEBUG
		));
		$expected = array(
			'identity' => 'SyslogTest',
			'options' => LOG_ODELAY,
			'facility' => LOG_USER,
			'priority' => LOG_DEBUG,
			'init' => true
		);
		$result = $syslog->_config;
		$this->assertEqual($expected, $result);
	}

	public function testWrite() {
		$result = Logger::write('syslog', 'SyslogTest message...');
		$this->assertTrue($result);
	}
}

?>