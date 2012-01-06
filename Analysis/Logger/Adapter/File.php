<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Analysis\Logger\Adapter;

use Lithium\Util\String;
use Lithium\Core\Libraries;

/**
 * A simple log adapter that writes messages to files. By default, messages are written to
 * `resources/tmp/logs/<type>.log`, where `<type>` is the log message priority level.
 *
 * {{{
 * use Lithium\Analysis\Logger;
 *
 * Logger::config(array(
 * 	'simple' => array('adapter' => 'File')
 * ));
 * Logger::write('debug', 'Something happened!');
 * }}}
 *
 * This will cause the message and the timestamp of the log event to be written to
 * `resources/tmp/logs/debug.log`. For available configuration options for this adapter, see
 * the `__construct()` method.
 *
 * @see Lithium\Analysis\Logger\Adapter\File::__construct()
 */
class File extends \Lithium\Core\Object {

	/**
	 * Class constructor.
	 *
	 * @see Lithium\Util\String::insert()
	 * @param array $config Settings used to configure the adapter. Available options:
	 *              - `'path'` _string_: The directory to write log files to. Defaults to
	 *                `<app>/resources/tmp/logs`.
	 *              - `'timestamp'` _string_: The `date()`-compatible timestamp format. Defaults to
	 *                `'Y-m-d H:i:s'`.
	 *              - `'file'` _closure_: A closure which accepts two parameters: an array
	 *                containing the current log message details, and an array containing the `File`
	 *                adapter's current configuration. It must then return a file name to write the
	 *                log message to. The default will produce a log file name corresponding to the
	 *                priority of the log message, i.e. `"debug.log"` or `"alert.log"`.
	 *              - `'format'` _string_: A `String::insert()`-compatible string that specifies how
	 *                the log message should be formatted. The default format is
	 *                `"{:timestamp} {:message}\n"`.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'path' => Libraries::get(true, 'resources') . '/tmp/logs',
			'timestamp' => 'Y-m-d H:i:s',
			'file' => function($data, $config) { return "{$data['priority']}.log"; },
			'format' => "{:timestamp} {:message}\n"
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Appends a message to a log file.
	 *
	 * @see Lithium\Analysis\Logger::$_priorities
	 * @param string $priority The message priority. See `Logger::$_priorities`.
	 * @param string $message The message to write to the log.
	 * @return closure Function returning boolean `true` on successful write, `false` otherwise.
	 */
	public function write($priority, $message) {
		$config = $this->_config;

		return function($self, $params) use (&$config) {
			$path = $config['path'] . '/' . $config['file']($params, $config);
			$params['timestamp'] = date($config['timestamp']);
			$message = String::insert($config['format'], $params);
			return file_put_contents($path, $message, FILE_APPEND);
		};
	}
}

?>