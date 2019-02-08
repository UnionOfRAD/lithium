<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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