<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use lithium\core\Environment;

/**
 * The `Configuration` class allow to store `Environement` based configurations.
 *
 * @see lithium\core\Environment
 */
class Configuration extends \lithium\core\Object {

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
		if ($config && is_array($config)) {
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
	 */
	public function reset() {
		$this->_configurations = array();
	}
}

?>