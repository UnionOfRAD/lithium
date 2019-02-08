<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\analysis;

use lithium\core\Libraries;
use lithium\analysis\Logger;
use lithium\aop\Filters;

/**
 * Logger adapter integration test cases
 */
class LoggerTest extends \lithium\test\Integration {

	public function testWriteFilter() {
		$base = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");

		Filters::apply('lithium\analysis\Logger', 'write', function($params, $next) {
			$params['message'] = 'Filtered Message';
			return $next($params);
		});

		$config = ['default' => [
			'adapter' => 'File', 'timestamp' => false, 'format' => "{:message}\n"
		]];
		Logger::config($config);

		$result = Logger::write('info', 'Original Message');
		$this->assertFileExists($base . '/info.log');

		$expected = "Filtered Message\n";
		$result = file_get_contents($base . '/info.log');
		$this->assertEqual($expected, $result);

		Filters::apply('lithium\analysis\Logger', 'write', false);
		unlink($base . '/info.log');
	}
}

?>