<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2013, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

use lithium\core\Environment;
use lithium\core\AutoConfigurable;

/**
 * The `Configuration` class allow to store `Environement` based configurations.
 *
 * @see lithium\core\Environment
 */
class Configuration {

	use AutoConfigurable;

	/**
	 * Can provide configurations based on the environment,
	 * i.e. `'development'`, `'production'` or `'test'`
	 *
	 * @var array of configurations, indexed by name.
	 */
	protected $_configurations = [];

	/**
	 * A closure called by `_config()` which allows to automatically
	 * assign or auto-generate additional configuration data, once a configuration is first
	 * accessed. This allows configuration data to be lazy-loaded from adapters or other data
	 * sources.
	 *
	 * @param string $name Name of the configuration which is being accessed. This is the key
	 *               name containing the specific set of configuration passed into `config()`.
	 * @param array $config Configuration assigned to `$name`. If this configuration
	 *              is segregated by environment, then this will contain the configuration for
	 *              the current environment.
	 * @return array Returns the final array of settings for the given named configuration.
	 */
	public $initConfig = null;

	/**
	 * Sets configurations for a particular adaptable implementation, or returns the current
	 * configuration settings.
	 *
	 * @param string $name Name of the scope.
	 * @param array $config Configuration to set.
	 */
	public function set($name = null, $config = null) {
		if (is_array($config)) {
			$this->_configurations[$name] = $config;
			return;
		}
		if ($config === false) {
			unset($this->_configurations[$name]);
		}
	}

	/**
	 * Gets an array of settings for the given named configuration in the current
	 * environment.
	 *
	 * @see lithium\core\Environment
	 * @param string $name Name of the configuration.
	 * @return array Settings of the named configuration.
	 */
	public function get($name = null) {
		if ($name === null) {
			$result = [];
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
	 */
	public function reset() {
		$this->_configurations = [];
	}
}

?>