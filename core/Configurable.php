<?php

/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use lithium\core\Environment;
use lithium\core\Object;

/**
 * The `Configurable` static class is the base class for class which need to be
 * configured using `lithium\core\Environment` class
 *
 */
class Configurable extends \lithium\core\Object {

	/**
	 * Can provide configurations based on the environment,
	 * i.e. `'development'`, `'production'` or `'test'`
	 *
	 * @var array of configurations, indexed by name.
	 */
	public $_configurations = array();

	/**
	 * A closure called by `_config()` which allows to automatically
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
	public $initConfig = null;

	/**
	 * Sets configurations for a particular adaptable implementation, or returns the current
	 * configuration settings.
	 *
	 * @param array $config Configurations, indexed by name.
	 * @return object `Collection` of configurations or void if setting configurations.
	 */
	public function set($name = null, $config = null) {
		if ($config && is_array($config)) {
			$this->_configurations[$name] = $config;
			return;
		}
		if($config === false){
			unset($this->_configurations[$name]);
		}
	}

	/**
	 * Gets an array of settings for the given named configuration in the current
	 * environment.
	 *
	 * @see lithium\core\Environment
	 * @param string $name Named configuration.
	 * @return array Settings for the named configuration.
	 */
	public function get($name = null) {
		if($name === null) {
			$result = array();
			$this->_configurations = array_filter($this->_configurations);

			foreach ($this->_configurations as $key => $value) {
				$result[$key] = $this->get($key);
			}
			return $result;
		}

		$settings = &$this->_configurations;

		if (!isset($settings[$name])) {
			return null;
		}
		
		if (isset($settings[$name][0])) {
			return $settings[$name][0];
		}
		$env = Environment::get();

		$config = isset($settings[$name][$env]) ? $settings[$name][$env] : $settings[$name];

		$method = is_callable($this->initConfig) ? $this->initConfig : null;
		$settings[$name][0] = $method ? $method($name, $config) : $config;
		return $settings[$name][0];
	}

	/**
	 * Clears all configurations.
	 *
	 * @return void
	 */
	public function reset() {
		$this->_configurations = array();
	}
}
