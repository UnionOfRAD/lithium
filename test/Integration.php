<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

use lithium\aop\Filters;

/**
 * This is the base class for integration tests.
 *
 * Integration tests are for determining that different parts of the framework will work
 * together (integrate) as expected. An example of a common integration test would be for
 * ensuring that an adapter interacts correctly with the class it is designed to interface
 * with. Example: the `Session` class and the `Php` adapter. Unit tests will ensure that
 * both the `Session` and `Php` classes behave correctly under isolation, while an integration
 * test ensures that the two classes interact and interface correctly.
 */
class Integration extends \lithium\test\Unit {

	/**
	 * Auto init for applying Integration filter to this test class.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		Filters::apply($this, 'run', function($params, $next) {
			$before = $this->results();

			$next($params);

			$after = $this->results();

			while (count($after) > count($before)) {
				$result = array_pop($after);
				if ($result['result'] === 'fail') {
					return false;
				}
			}
		});
	}

	/**
	 * Checks for a working internet connection.
	 *
	 * This method is used to check for a working connection to google.com, both
	 * testing for proper DNS resolution and reading the actual URL.
	 *
	 * @param array $config Override the default URL to check.
	 * @return boolean True if a network connection is established, false otherwise.
	 */
	protected function _hasNetwork($config = []) {
		$defaults = [
			'scheme' => 'http',
			'host' => 'google.com'
		];
		$config += $defaults;

		$url = "{$config['scheme']}://{$config['host']}";
		$failed = false;

		set_error_handler(function($errno, $errstr) use (&$failed) {
			$failed = true;
		});

		dns_check_record($config['host'], 'A');

		if ($handle = fopen($url, 'r')) {
			fclose($handle);
		}

		restore_error_handler();
		return !$failed;
	}
}

?>