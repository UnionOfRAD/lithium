<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\analysis;

use lithium\core\Libraries;
use lithium\analysis\Logger;
use lithium\tests\mocks\analysis\MockLoggerAdapter;

/**
 * Logger adapter test case
 */
class LoggerTest extends \lithium\test\Unit {

	protected $_testPath;

	public function skip() {
		$path = Libraries::get(true, 'resources');

		if (is_writable($path)) {
			foreach (["{$path}/tmp/tests", "{$path}/tmp/logs"] as $dir) {
				if (!is_dir($dir)) {
					mkdir($dir, 0777, true);
				}
			}
		}
		$this->_testPath = "{$path}/tmp/tests";
		$this->skipIf(!is_writable($this->_testPath), "Path `{$this->_testPath}` is not writable.");
	}

	public function setUp() {
		Logger::config(['default' => ['adapter' => new MockLoggerAdapter()]]);
	}

	public function tearDown() {
		Logger::reset();
	}

	public function testConfig() {
		$test = new MockLoggerAdapter();
		$config = ['logger' => ['adapter' => $test, 'filters' => []]];

		$result = Logger::config($config);
		$this->assertNull($result);

		$result = Logger::config();
		$config['logger'] += ['priority' => true];
		$expected = $config;
		$this->assertEqual($expected, $result);
	}

	public function testReset() {
		$test = new MockLoggerAdapter();
		$config = ['logger' => ['adapter' => $test, 'filters' => []]];

		$result = Logger::config($config);

		$result = Logger::reset();
		$this->assertNull($result);

		$result = Logger::config();
		$this->assertEmpty($result);

		$this->assertFalse(Logger::write('info', 'Test message.'));
	}

	public function testWrite() {
		$result = Logger::write('info', 'value');
		$this->assertNotEmpty($result);
	}

	public function testIntegrationWriteFile() {
		$base = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		$config = ['default' => [
			'adapter' => 'File', 'timestamp' => false, 'format' => "{:message}\n"
		]];
		Logger::config($config);

		$result = Logger::write('info', 'Message line 1');
		$this->assertFileExists($base . '/info.log');

		$expected = "Message line 1\n";
		$result = file_get_contents($base . '/info.log');
		$this->assertEqual($expected, $result);

		$result = Logger::write('info', 'Message line 2');
		$this->assertNotEmpty($result);

		$expected = "Message line 1\nMessage line 2\n";
		$result = file_get_contents($base . '/info.log');
		$this->assertEqual($expected, $result);

		unlink($base . '/info.log');
	}

	public function testWriteWithInvalidPriority() {
		$this->assertException("Attempted to write log message with invalid priority `foo`.", function() {
			Logger::foo("Test message");
		});
	}

	public function testWriteByName() {
		$base = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		Logger::config(['default' => [
			'adapter' => 'File',
			'timestamp' => false,
			'priority' => false,
			'format' => "{:message}\n"
		]]);

		$this->assertFileNotExists($base . '/info.log');

		$this->assertEmpty(Logger::write('info', 'Message line 1'));
		$this->assertFileNotExists($base . '/info.log');

		$this->assertNotEmpty(Logger::write(null, 'Message line 1', ['name' => 'default']));

		$expected = "Message line 1\n";
		$result = file_get_contents($base . '/.log');
		$this->assertEqual($expected, $result);

		unlink($base . '/.log');
	}

	public function testMultipleAdaptersWriteByNameDefault() {
		$base = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		Logger::config([
			'default' => [
				'adapter' => 'File',
				'file' => function($data, $config) { return "{$data['priority']}_default.log"; },
				'timestamp' => false,
				'format' => "{:message}\n"
			],
			'secondary' => [
				'adapter' => 'File',
				'file' => function($data, $config) { return "{$data['priority']}_secondary.log"; },
				'timestamp' => false,
				'format' => "{:message}\n"
			],
		]);

		$this->assertFileNotExists($base . '/info_default.log');

		$this->assertNotEmpty(Logger::write('info', 'Default Message line 1', [
			'name' => 'default'
		]));

		$this->assertFileExists($base . '/info_default.log');

		$expected = "Default Message line 1\n";
		$result = file_get_contents($base . '/info_default.log');
		$this->assertEqual($expected, $result);

		unlink($base . '/info_default.log');

	}

	public function testMultipleAdaptersWriteByNameSecondary() {
		$base = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		Logger::config([
			'default' => [
				'adapter' => 'File',
				'file' => function($data, $config) { return "{$data['priority']}_default.log"; },
				'timestamp' => false,
				'format' => "{:message}\n"
			],
			'secondary' => [
				'adapter' => 'File',
				'file' => function($data, $config) { return "{$data['priority']}_secondary.log"; },
				'timestamp' => false,
				'format' => "{:message}\n"
			],
		]);

		$this->assertFileNotExists($base . '/info_secondary.log');

		$this->assertNotEmpty(Logger::write('info', 'Secondary Message line 1', [
			'name' => 'secondary'
		]));

		$this->assertFileExists($base . '/info_secondary.log');

		$expected = "Secondary Message line 1\n";
		$result = file_get_contents($base . '/info_secondary.log');
		$this->assertEqual($expected, $result);

		unlink($base . '/info_secondary.log');

	}
}

?>