<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Analysis\Logger\Adapter;

use Lithium\Analysis\Logger;
use Lithium\Analysis\Logger\Adapter\Syslog;

/**
 * Syslog adapter test.
 */
class SyslogTest extends \Lithium\Test\Unit {

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
		$result = Logger::write('info', 'SyslogTest message...', array('name' => 'syslog'));
		$this->assertTrue($result);
	}
}

?>