<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \Exception;
use \lithium\util\Collection;
use \lithium\core\Environment;

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

	protected static $_adapters = null;

	/**
	 * Initialization of static class
	 *
	 * @return void
	 */
	public static function __init() {
		return static::$_configurations = new Collection();
	}

	/**
	 * Sets configurations for a particular adaptable implementation, or returns the current
	 * configuration settings.
	 *
	 * @param array $config Configurations, indexed by name
	 * @return object `Collection` of configurations
	 */
	public static function config($config = null) {
		if ($config && is_array($config)) {
			static::$_configurations = new Collection(array('items' => $config));
			return;
		}
		if ($config) {
			return static::_config($config);
		}
		$result = array();

		foreach (static::$_configurations->keys() as $key) {
			$result[$key] = static::_config($key);
		}
		return $result;
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
	 * Returns adapter class name for given `$name` configuration.
	 *
	 * @param  string $name Class name of adapter to load.
	 * @return object  Adapter object.
	 */
	public static function adapter($name = null) {
		$config = static::_config($name);

		if ($config === null) {
			throw new Exception("Adapter configuration {$name} has not been defined");
		}

		if (isset($config['adapter']) && is_object($config['adapter'])) {
			return $config['adapter'];
		}
		$class = static::_class($config, static::$_adapters);
		$settings = static::$_configurations[$name];
		$settings[0]['adapter'] = new $class($config);

		static::$_configurations[$name] = $settings;
		return static::$_configurations[$name][0]['adapter'];
	}

	/**
	 * Determines if the adapter specified in the named configuration is enabled.
	 *
	 * `Enabled` can mean various things, e.g. having a PECL memcached extension compiled
	 * & loaded, as well as having the memcache server up & available.
	 *
	 * @param string $name The cache configuration whose adapter will be checked
	 * @return mixed `True` if adapter is enabled, `false` if not. This method
	 *         will return `null` if no configuration under the given `$name` exists.
	 */
	public static function enabled($name) {
		return is_null(static::_config($name)) ? null : static::adapter($name)->enabled();
	}

	/**
	 * Looks up an adapter class by name, using the `$_adapters` property set by a subclass of
	 * `Adaptable`.
	 *
	 * @param string $name The class name of the adapter to locate.
	 * @return string Returns a fully-namespaced class reference to the adapter class.
	 */
	protected static function _class($config, $paths = array()) {
		$self = get_called_class();
		if (!$name = $config['adapter']) {
			throw new Exception("No adapter set for configuration in class {$self}");
		}
		foreach ((array) $paths as $path) {
			if ($class = Libraries::locate($path, $name)) {
				return $class;
			}
		}
		throw new Exception("Could not find adapter {$name} in class {$self}");
	}

	/**
	 * Gets an array of settings for the given named configuration. 
	 *
	 * @param string $name 
	 * @return array
	 */
	protected static function _config($name) {
		$defaults = array('adapter' => null, 'filters' => array(), 'strategies' => array());

		if (!isset(static::$_configurations[$name])) {
			return null;
		}
		$settings = static::$_configurations[$name];

		if (isset($settings[0])) {
			return $settings[0];
		}
		$env = Environment::get();
		$config = isset($settings[$env]) ? $settings[$env] : $settings;

		static::$_configurations[$name] = array((array) $config + $defaults) + $settings;
		return static::$_configurations[$name][0];
	}
}

?>