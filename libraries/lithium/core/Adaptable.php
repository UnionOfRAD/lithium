<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \Exception;
use \lithium\util\Collection;
use \lithium\core\Environment;

/**
 * The `Adaptable` static class is the base class from which all adapter implementations extend.
 *
 * `Adaptable` provides the logic necessary for generic configuration of named adapter
 * configurations (such as the ones used in `Cache`) as well as a unified method of locating and
 * obtaining an instance to a specified adapter.
 *
 * All immediate subclasses to `Adaptable` must define the protected attributes `$_configurations`
 * and `$_adapters`. The former is where all local adapter named configurations will be
 * stored (as a Collection of named configuration settings), and the latter must contain the
 * Libraries::locate() compatible path string.
 *
 * This static class should **never** be called explicitly.
 *
 * @see lithium\storage\Cache
 * @see lithium\storage\Session
 * @see lithium\analysis\Logger
 *
 * @todo Implement as abstract class with abstract method `adapter` when Inspector has been fixed.
 */
class Adaptable extends \lithium\core\StaticObject {

	/**
	 * To be re-defined in sub-classes.
	 *
	 * @var object `Collection` of configurations, indexed by name.
	 */
	protected static $_configurations = null;

	/**
	 * To be re-defined in sub-classes.
	 *
	 * Holds the Libraries::locate() compatible path string where the adapter in question
	 * may be found.
	 *
	 * @var string Path string.
	 */
	protected static $_adapters = null;

	/**
	 * Initialization of static class.
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
	 * @param array $config Configurations, indexed by name.
	 * @return object|void `Collection` of configurations or void if setting configurations.
	 */
	public static function config($config = null) {
		if (!static::$_configurations) {
			static::__init();
		}
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
	 * Clears all configurations.
	 *
	 * @return void
	 */
	public static function reset() {
		static::__init();
	}

	/**
	 * Returns adapter class name for given `$name` configuration.
	 *
	 * @param string $name Class name of adapter to load.
	 * @return object Adapter object.
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
	 * @param string $name The named configuration whose adapter will be checked.
	 * @return boolean|null  True if adapter is enabled, false if not. This method will return
	 *         null if no configuration under the given $name exists.
	 */
	public static function enabled($name) {
		if (!static::_config($name)) {
			return;
		}
		$adapter = static::adapter($name);
		return $adapter::enabled();
	}

	/**
	 * Looks up an adapter class by name, using the `$_adapters` property set by a subclass of
	 * `Adaptable`.
	 *
	 * @see lithium\core\libraries::locate()
	 * @param string $config The configuration array of the adapter to be located.
	 * @param array $paths Optional array of search paths that will be checked.
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
	 * Gets an array of settings for the given named configuration in the current
	 * environment.
	 *
	 * The default types of settings for all adapters will contain keys for:
	 * `adapter` - The class name of the adapter
	 * `filters` - An array of filters to be applied to the adapter methods
	 *
	 * @see lithium\core\Environment
	 * @param string $name Named configuration.
	 * @return array Settings for the named configuration.
	 */
	protected static function _config($name) {
		if (!isset(static::$_configurations[$name])) {
			return null;
		}
		$settings = static::$_configurations[$name];

		if (isset($settings[0])) {
			return $settings[0];
		}
		$env = Environment::get();
		$config = isset($settings[$env]) ? $settings[$env] : $settings;
		static::$_configurations[$name] += array(static::_initConfig($name, $config));
		return static::$_configurations[$name][0];
	}

	/**
	 * A stub method called by `_config()` which allows `Adaptable` subclasses to automatically
	 * assign or auto-generate additional configuration data, once a configuration is first
	 * accessed. This allows configuration data to be lazy-loaded from adapters or other data
	 * sources.
	 *
	 * @param string $name The name of the configuration which is being accessed. This is the key
	 *               name containing the specific set of configuration passed into `config()`.
	 * @param array $config Contains the configuration assigned to `$name`. If this configuration is
	 *              segregated by environment, then this will contian the configuration for the
	 *              current environment.
	 * @return array Returns the final array of settings for the given named configuration.
	 */
	protected static function _initConfig($name, $config) {
		$defaults = array('adapter' => null, 'filters' => array());
		return (array) $config + $defaults;
	}
}

?>