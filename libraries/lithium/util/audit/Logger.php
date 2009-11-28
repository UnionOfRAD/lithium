<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\audit;

use \lithium\util\String;
use \lithium\core\Libraries;
use \lithium\util\Collection;

class Logger extends \lithium\core\Adaptable {

	/**
	 * Stores configurations for cache adapters
	 *
	 * @var object Collection of logger configurations
	 */
	protected static $_configurations = null;

	/**
	 * Writes $message to the log specified by the $name
	 * configuration.
	 *
	 * @param  string $name    Configuration to be used for writing
	 * @param  string $message Message to be written
	 * @return boolean         True on successful write, false otherwise
	 */
	public static function write($type, $message) {
		$settings = static::config();

		if (!isset($settings[$type]) || !$settings->count()) {
			return false;
		}

		$methods = array($type => static::adapter($type)->write($type, $message));
		$result = false;

		foreach ($methods as $name => $method) {
			$params = compact('type', 'message');
			$filters = $settings[$name]['filters'];
			$result = $result || static::_filter(__METHOD__, $params, $method, $filters);
		}
		return $result;
	}

	/**
	 * Returns adapter for given named configuration
	 *
	 * @param  string $name Cache configuration name
	 * @return object       Adapter for named configuration
	 */
	public static function adapter($name) {
		return static::_adapter('adapters.util.audit.logger', $name);
	}
}

?>