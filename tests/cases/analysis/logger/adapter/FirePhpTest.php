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
use lithium\analysis\logger\adapter\FirePhp;
use lithium\action\Response;

/**
 * This tests make sure that the FirePhp log adapter works as expected.
 *
 * @deprecated
 */
class FirePhpTest extends \lithium\test\Unit {

	protected $_backup = null;

	/**
	 * Sets up and configures the logger and also the cache storage for testing.
	 */
	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);

		$this->firephp = new FirePhp();
		Logger::config(['firephp' => ['adapter' => $this->firephp]]);
	}

	public function tearDown() {
		error_reporting($this->_backup);
	}

	/**
	 * Test the initialization of the FirePhp log adapter.
	 */
	public function testConstruct() {
		$expected = ['init' => true];
		$this->assertEqual($expected, $this->firephp->_config);
	}

	/**
	 * Test if the configuration is correctly set in the logger.
	 */
	public function testConfiguration() {
		$loggers = Logger::config();
		$this->assertArrayHasKey('firephp', $loggers);
	}

	/**
	 * Tests the writing mechanism. At first, no Response object is bound to the logger, so
	 * it queues up the messages. When the Response object finally gets bound, it flushes the
	 * needed headers and all messages at once. All messages coming after this point get added
	 * to the header immediately.
	 */
	public function testWrite() {
		$response = new Response();
		$result = Logger::write('debug', 'FirePhp to the rescue!', ['name' => 'firephp']);
		$this->assertNotEmpty($result);
		$this->assertEmpty($response->headers());

		$host = 'meta.firephp.org';
		$expected = [
			"X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2",
			"X-Wf-1-Plugin-1: http://{$host}/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3",
			"X-Wf-1-Structure-1: http://{$host}/Wildfire/Structure/FirePHP/FirebugConsole/0.1",
			"X-Wf-1-1-1-1: 41|[{\"Type\":\"LOG\"},\"FirePhp to the rescue!\"]|"
		];
		Logger::adapter('firephp')->bind($response);
		$this->assertEqual($expected, $response->headers());

		$result = Logger::write('debug', 'Add this immediately.', ['name' => 'firephp']);
		$this->assertNotEmpty($result);
		$expected[] = 'X-Wf-1-1-1-2: 40|[{"Type":"LOG"},"Add this immediately."]|';
		$this->assertEqual($expected, $response->headers());
	}
}

?>