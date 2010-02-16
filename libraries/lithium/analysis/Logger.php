<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\analysis;

use \lithium\util\String;
use \lithium\core\Libraries;
use \lithium\util\Collection;

class Logger extends \lithium\core\Adaptable {

	/**
	 * Stores configurations for cache adapters.
	 *
	 * @var object `Collection` of logger configurations.
	 */
	protected static $_configurations = null;

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.analysis.logger';

	/**
	 * Writes `$message` to the log specified by the `$type` configuration.
	 *
	 * @param string $type Configuration to be used for writing.
	 * @param string $message Message to be written.
	 * @return boolean `True` on successful write, `false` otherwise.
	 */
	public static function write($type, $message) {
		if (!$config = static::_config($type)) {
			return false;
		}

		$methods = array($type => static::adapter($type)->write($type, $message));
		$result = false;

		foreach ($methods as $name => $method) {
			$params = compact('type', 'message');
			$config = static::_config($name);
			$result = $result || static::_filter(__METHOD__, $params, $method, $config['filters']);
		}
		return $result;
	}
}

?>