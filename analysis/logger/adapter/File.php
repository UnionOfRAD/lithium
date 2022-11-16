<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\analysis\logger\adapter;

use lithium\util\Text;
use lithium\core\Libraries;
use lithium\core\AutoConfigurable;

/**
 * A simple log adapter that writes messages to files. By default, messages are written to
 * `resources/tmp/logs/<type>.log`, where `<type>` is the log message priority level.
 *
 * ```
 * use lithium\analysis\Logger;
 *
 * Logger::config([
 * 	'simple' => ['adapter' => 'File']
 * ]);
 * Logger::write('debug', 'Something happened!');
 * ```
 *
 * This will cause the message and the timestamp of the log event to be written to
 * `resources/tmp/logs/debug.log`. For available configuration options for this adapter, see
 * the `__construct()` method.
 *
 * @see lithium\analysis\logger\adapter\File::__construct()
 */
class File {

	use AutoConfigurable;

	/**
	 * Constructor.
	 *
	 * @see lithium\util\Text::insert()
	 * @param array $config Settings used to configure the adapter. Available options:
	 *        - `'path'` _string_: The directory to write log files to. Defaults to
	 *          `<app>/resources/tmp/logs`.
	 *        - `'timestamp'` _string_: The `date()`-compatible timestamp format. Defaults to
	 *          `'Y-m-d H:i:s'`.
	 *        - `'file'` _\Closure_: A closure which accepts two parameters: an array
	 *          containing the current log message details, and an array containing the `File`
	 *          adapter's current configuration. It must then return a file name to write the
	 *          log message to. The default will produce a log file name corresponding to the
	 *          priority of the log message, i.e. `"debug.log"` or `"alert.log"`.
	 *        - `'format'` _string_: A `Text::insert()`-compatible string that specifies how
	 *          the log message should be formatted. The default format is
	 *          `"{:timestamp} {:message}\n"`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'path' => Libraries::get(true, 'resources') . '/tmp/logs',
			'timestamp' => 'Y-m-d H:i:s',
			'file' => function($data, $config) { return "{$data['priority']}.log"; },
			'format' => "{:timestamp} {:message}\n"
		];
		$this->_autoConfig($config + $defaults, []);
		$this->_autoInit($config);
	}

	/**
	 * Appends a message to a log file.
	 *
	 * @see lithium\analysis\Logger::$_priorities
	 * @param string $priority The message priority. See `Logger::$_priorities`.
	 * @param string $message The message to write to the log.
	 * @return \Closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write($priority, $message) {
		return function($params) {
			$path = $this->_config['path'] . '/' . $this->_config['file']($params, $this->_config);
			$params['timestamp'] = date($this->_config['timestamp']);
			$message = Text::insert($this->_config['format'], $params);
			return file_put_contents($path, $message, FILE_APPEND);
		};
	}
}

?>