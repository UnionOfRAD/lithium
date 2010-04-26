<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\analysis\logger\adapter;

use \SplFileInfo;
use \DirectoryIterator;

/**
 * A simple log adapter that writes messages to files. By default, messages are written to
 * `app/resources/tmp/logs/<type>.log`, where `<type>` is the log message priority level.
 *
 * {{{
 * lithium\analysis\Logger::config(array(
 * 	'debug' => array('adapter' => 'File')
 * ));
 * lithium\analysis\Logger::write('debug', 'Something happened!');
 * }}}
 *
 * This will cause the message and the timestamp of the log event to be written to
 * `app/resources/tmp/logs/debug.log`.
 */
class File extends \lithium\core\Object {

	/**
	 * Class constructor.
	 *
	 * @param array $config Settings used to configure the adapter. Available options:
	 *              - `'path'` _string_: The directory to write log files to. Defaults to
	 *                `app/resources/tmp/logs`.
	 *              - `'timestamp'` _string_: The `date()`-compatible format of the timetstamp, or
	 *                `false` to disable timestamps. Defaults to `'Y-m-d H:i:s'`.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'path' => LITHIUM_APP_PATH . '/resources/tmp/logs',
			'timestamp' => 'Y-m-d H:i:s',
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Appends $data to file $type.
	 *
	 * @param string $type
	 * @param string $message
	 * @return boolean `True` on successful write, `false` otherwise.
	 */
	public function write($type, $message) {
		$config = $this->_config;

		return function($self, $params, $chain) use (&$config) {
			$type = $params['priority'];
			$message = $params['message'];
			$time = $config['timestamp'] ? date($config['timestamp']) . ' ' : '';
			$path = $config['path'];
			return file_put_contents("{$path}/{$type}.log", "{$time}{$message}\n", FILE_APPEND);
		};
	}
}

?>