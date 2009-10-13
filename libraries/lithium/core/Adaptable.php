<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \lithium\util\Collection;

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
		$default = array('adapter' => null, 'filters' => array());

		if ($config) {
			$items = array_map(function($i) use ($default) { return $i + $default; }, $config);
			static::$_configurations = new Collection(compact('items'));
		}
		return static::$_configurations;
	}

	/**
	 * Clears configurations
	 *
	 * @return void
	 */
	public static function reset() {
		static::$_configurations = new Collection();
	}

	/**
	 * Returns adapter class name for given $name configuration
	 *
	 * @param  string $library Dot-delimited location of library, in a format
	 *                         compatible with Libraries::locate().
	 * @param  string $name    Classname of adapter to load
	 * @return string          Adapter object
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

}
?>