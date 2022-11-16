<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

use SplDoublyLinkedList;
use lithium\core\Environment;
use lithium\aop\Filters;
use lithium\core\ConfigException;

/**
 * The `Adaptable` static class is the base class from which all adapter implementations extend.
 *
 * `Adaptable` provides the logic necessary for generic configuration of named adapter
 * configurations (such as the ones used in `Cache`) as well as a unified method of locating and
 * obtaining an instance to a specified adapter.
 *
 * All immediate subclasses to `Adaptable` must define the protected attributes `$_configurations`
 * and `$_adapters`. The former is where all local adapter named configurations will be
 * stored (as an array of named configuration settings), and the latter must contain the
 * `Libraries::locate()`-compatible path string (or array of strings) specifying how adapter classes
 * should be located.
 *
 * This static class should **never** be called explicitly.
 */
class Adaptable {

	/**
	 * Must always be re-defined in sub-classes. Can provide initial
	 * configurations, i.e. `'development'` or `'default'` along with
	 * default options for each.
	 *
	 * Example:
	 * ```
	 * [
	 *  'production' => [],
	 *  'development' => [],
	 *  'test' => []
	 * ]
	 * ```
	 *
	 * @var array Array of configurations, indexed by name.
	 */
	protected static $_configurations = [];

	/**
	 * Must only be re-defined in sub-classes if the class is using strategies.
	 * Holds the `Libraries::locate()` compatible path string where the strategy
	 * in question may be found i.e. `'strategy.storage.cache'`.
	 *
	 * @var string Path string.
	 */
	protected static $_strategies = null;

	/**
	 * Must always be re-defined in sub-classes. Holds the
	 * `Libraries::locate()` compatible path string where the adapter in
	 * question may be found i.e. `'adapter.storage.cache'`.
	 *
	 * @var string Path string.
	 */
	protected static $_adapters = null;

	/**
	 * Sets configurations for a particular adaptable implementation, or returns the current
	 * configuration settings.
	 *
	 * @param array|string $config An array of configurations, indexed by name to set
	 *        configurations in one go or a name for which to return the configuration.
	 * @return array|void Configuration or void if setting configurations.
	 */
	public static function config($config = null) {
		if ($config && is_array($config)) {
			static::$_configurations = $config;
			return;
		}
		if ($config) {
			return static::_config($config);
		}
		$result = [];
		static::$_configurations = array_filter(static::$_configurations);

		foreach (array_keys(static::$_configurations) as $key) {
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
		static::$_configurations = [];
	}

	/**
	 * Returns adapter class name for given `$name` configuration, using
	 * the `$_adapter` path defined in Adaptable subclasses.
	 *
	 * @param string|null $name Class name of adapter to load.
	 * @return object Adapter object.
	 */
	public static function adapter($name = null) {
		$config = static::_config($name);

		if ($config === null) {
			throw new ConfigException("Configuration `{$name}` has not been defined.");
		}

		if (isset($config['object'])) {
			return $config['object'];
		}
		$class = static::_class($config, static::$_adapters);
		$settings = static::$_configurations[$name];
		$settings[0]['object'] = static::_initAdapter($class, $config);
		static::$_configurations[$name] = $settings;
		return static::$_configurations[$name][0]['object'];
	}

	/**
	 * Obtain an `SplDoublyLinkedList` of the strategies for the given `$name` configuration, using
	 * the `$_strategies` path defined in `Adaptable` subclasses.
	 *
	 * @param string $name Class name of adapter to load.
	 * @return object `SplDoublyLinkedList` of strategies, or `null` if none are defined.
	 */
	public static function strategies($name) {
		$config = static::_config($name);

		if ($config === null) {
			throw new ConfigException("Configuration `{$name}` has not been defined.");
		}
		if (!isset($config['strategies'])) {
			return null;
		}
		$stack = new SplDoublyLinkedList();

		foreach ($config['strategies'] as $key => $strategy) {
			if (!is_array($strategy)) {
				$name = $strategy;
				$class = static::_strategy($name, static::$_strategies);
				$stack->push(new $class());
				continue;
			}
			$class = static::_strategy($key, static::$_strategies);
			$index = (isset($config['strategies'][$key])) ? $key : $class;
			$stack->push(new $class($config['strategies'][$index]));
		}
		return $stack;
	}

	/**
	 * Applies strategies configured in `$name` for `$method` on `$data`.
	 *
	 * @param string $method The strategy method to be applied.
	 * @param string $name The named configuration
	 * @param mixed $data The data to which the strategies will be applied.
	 * @param array $options If `mode` is set to 'LIFO', the strategies are applied in reverse.
	 *        order of their definition.
	 * @return mixed Result of application of strategies to data. If no strategies
	 *         have been configured, this method will simply return the original data.
	 */
	public static function applyStrategies($method, $name, $data, array $options = []) {
		$options += ['mode' => null];

		if (!$strategies = static::strategies($name)) {
			return $data;
		}
		if (!count($strategies)) {
			return $data;
		}

		if (isset($options['mode']) && ($options['mode'] === 'LIFO')) {
			$strategies->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO);
			unset($options['mode']);
		}

		foreach ($strategies as $strategy) {
			if (method_exists($strategy, $method)) {
				$data = $strategy->{$method}($data, $options);
			}
		}
		return $data;
	}

	/**
	 * Determines if the adapter specified in the named configuration is enabled.
	 *
	 * `Enabled` can mean various things, e.g. having a PECL memcached extension compiled
	 * & loaded, as well as having the memcache server up & available.
	 *
	 * @param string $name The named configuration whose adapter will be checked.
	 * @return boolean  True if adapter is enabled, false if not. This method will
	 *         return null if no configuration under the given $name exists.
	 */
	public static function enabled($name) {
		$class = static::_class(static::_config($name) ?: ['adapter' => null], static::$_adapters);
		return $class::enabled();
	}

	/**
	 * Provides an extension point for modifying how adapters are instantiated.
	 *
	 * @see lithium\core\Object::__construct()
	 * @param string $class The fully-namespaced class name of the adapter to instantiate.
	 * @param array $config The configuration array to be passed to the adapter instance. See the
	 *              `$config` parameter of `Object::__construct()`.
	 * @return object The adapter's class.
	 * @filter
	 */
	protected static function _initAdapter($class, array $config) {
		$params = compact('class', 'config');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			return new $params['class']($params['config']);
		});
	}

	/**
	 * Looks up an adapter by class by name.
	 *
	 * @see lithium\core\libraries::locate()
	 * @param array $config Configuration array of class to be found.
	 * @param array $paths Optional array of search paths that will be checked.
	 * @return string Returns a fully-namespaced class reference to the adapter class.
	 */
	protected static function _class($config, $paths = []) {
		if (!$name = $config['adapter']) {
			$self = get_called_class();
			throw new ConfigException("No adapter set for configuration in class `{$self}`.");
		}
		if (!$class = static::_locate($paths, $name)) {
			$self = get_called_class();
			throw new ConfigException("Could not find adapter `{$name}` in class `{$self}`.");
		}
		return $class;
	}

	/**
	 * Looks up a strategy by class by name.
	 *
	 * @see lithium\core\libraries::locate()
	 * @param string $name The strategy to locate.
	 * @param array $paths Optional array of search paths that will be checked.
	 * @return string Returns a fully-namespaced class reference to the adapter class.
	 */
	protected static function _strategy($name, $paths = []) {
		if (!$name) {
			$self = get_called_class();
			throw new ConfigException("No strategy set for configuration in class `{$self}`.");
		}
		if (!$class = static::_locate($paths, $name)) {
			$self = get_called_class();
			throw new ConfigException("Could not find strategy `{$name}` in class `{$self}`.");
		}
		return $class;
	}

	/**
	 * Perform library location for an array of paths or a single string-based path.
	 *
	 * @param string|array $paths Paths that Libraries::locate() will utilize.
	 * @param string $name The name of the class to be located.
	 * @return string Fully-namespaced path to the class, or null if not found.
	 */
	protected static function _locate($paths, $name) {
		foreach ((array) $paths as $path) {
			if ($class = Libraries::locate($path, $name)) {
				return $class;
			}
		}
		return null;
	}

	/**
	 * Gets an array of settings for the given named configuration in the current
	 * environment. Each configuration will at least contain an `'adapter'` option.
	 *
	 * @see lithium\core\Environment
	 * @param string $name Named configuration.
	 * @return array|null Settings for the named configuration.
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

		if (isset($settings[$env]) && isset($settings[true])) {
			$config += $settings[true];
		}
		static::$_configurations[$name] += [static::_initConfig($name, $config)];
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
	 *              segregated by environment, then this will contain the configuration for the
	 *              current environment.
	 * @return array Returns the final array of settings for the given named configuration.
	 */
	protected static function _initConfig($name, $config) {
		return (array) $config + ['adapter' => null];
	}
}

?>