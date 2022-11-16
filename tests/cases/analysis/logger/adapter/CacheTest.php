<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\analysis\logger\adapter;

use lithium\storage\Cache As CacheStorage;
use lithium\analysis\Logger;
use lithium\analysis\logger\adapter\Cache;

class MockCache extends Cache {

	public function config() {
		return $this->_config;
	}
}

/**
 * Tests the "Cache" logger adapter.
 */
class CacheTest extends \lithium\test\Unit {

	public $cachelog;

	/**
	 * Sets up and configers the logger and also the cache storage for testing.
	 */
	public function setUp() {
		CacheStorage::config([
			'cachelog' => [
				'adapter' => 'Memory'
			]
		]);
		$this->cachelog = new MockCache([
			'key' => 'cachelog_testkey',
			'config' => 'cachelog'
		]);
		Logger::config([
			'cachelog' => [
				'adapter' => $this->cachelog,
				'key' => 'cachelog_testkey',
				'config' => 'cachelog'
			]
		]);
	}

	/**
	 * Test the initialization of the cache log adapter.
	 */
	public function testConstruct() {
		$expected = [
			'config' => "cachelog",
			'expiry' => CacheStorage::PERSIST,
			'key' => "cachelog_testkey"
		];
		$result = $this->cachelog->config();
		$this->assertEqual($expected, $result);
	}

	/**
	 * Test if the configuration is correctly set in the logger.
	 */
	public function testConfiguration() {
		$loggers = Logger::config();
		$this->assertArrayHasKey('cachelog', $loggers);
	}

	/**
	 * Tests the correct writing to the cache adapter. In this test we use the
	 * "Memory" cache adapter so that we can easily verify the written message.
	 */
	public function testWrite() {
		$message = "CacheLog test message...";
		$result = Logger::write('info', $message, ['name' => 'cachelog']);
		$this->assertNotEmpty($result);
		$result = CacheStorage::read('cachelog', 'cachelog_testkey');
		$this->assertEqual($message, $result);
	}
}

?>