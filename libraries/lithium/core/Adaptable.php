<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \lithium\util\Collection;

/**
 * The `Adaptable` static class is the base class from which all adapter implementations
 * extend.
 *
 * `Adaptable` provides the logic necessary for generic configuration of named adapter
 * configurations (such as the ones used in `Cache`, as well as a unified method of locating and
 * obtaining an instance to a specified adapter.
 *
 * All immediate subclasses to `Adaptable` must implement the `adapter` method, and must also
 * define the protected `$_configurations` as a class attribute. The latter is where all local
 * adapter named configurations will be stored, as a Collection of named configuration settings.
 *
 * This static class should never be called explicitly.
 *
 * @see lithium\storage\Cache
 * @see lithium\storage\Session
 * @see lithium\util\audit\Logger
 *
 * @todo Implement as abtract class with abstract method `adapter` when Inspector has been fixed.
 */
class Adaptable extends \lithium\core\StaticObject {

	/**
	 * To be re-defined in sub-classes.
	 *
	 * @var object Collection of configurations, indexed by name.
	 */
	protected static $_configurations = null;

	/**
	 * Initialization of static class
	 *
	 * @return void
	 */
	public static function __init() {
		return static::$_configurations = new Collection();
	}

	/**
	 * Sets configurations for a particular adaptable implementation, or returns
	 * the current configuration settings.
	 *
	 * @param  array  $config Configurations, indexed by name
	 * @return object         Collection of configurations
	 */
	public static function config($config = null) {
		$default = array('adapter' => null, 'filters' => array(), 'strategies' => array());

		if ($config) {
			$items = array_map(function($i) use ($default) { return $i + $default; }, $config);
			static::$_configurations = new Collection(compact('items'));
		}
		return static::$_configurations;
	}

	/**
	 * Clears configurations.
	 *
	 * @return void
	 */
	public static function reset() {
		static::$_configurations = new Collection();
	}

	/**
	 * Returns adapter class name for given $name configuration.
	 *
	 * @param  string $library Dot-delimited location of library, in a format compatible
	 *         with Libraries::locate().
	 * @param  string $name  Classname of adapter to load.
	 * @return object  Adapter object.
	 */
	protected static function _adapter($library, $name = null) {
		$settings = static::$_configurations;

		if (empty($name)) {
			$names = $settings->keys();
			if (empty($names)) {
				return;
			}
			$name = end($names);
		}

		if (!isset($settings[$name])) {
			return;
		}

		if (is_string($settings[$name]['adapter'])) {
			$config = $settings[$name];

			if (!$class = Libraries::locate($library, $config['adapter'])) {
				return null;
			}
			$settings[$name] = array('adapter' => new $class($config)) + $settings[$name];
		}

		return $settings[$name]['adapter'];
	}

	/**
	 * Determines if the adapter specified in the named configuration is enabled.
	 *
	 * `Enabled` can mean various things, e.g. having a PECL memcached extension compiled
	 * & loaded, as well as having the memcache server up & available.
	 *
	 * @param  string  $name The cache configuration whose adapter will be checked
	 * @return boolean|null  True if adapter is enabled, false if not. This method will return
	 *         null if no configuration under the given $name exists.
	 */
	public static function enabled($name) {
		$settings = static::config();
		return (isset($settings[$name])) ? static::adapter($name)->enabled() : null;
	}
}
?>