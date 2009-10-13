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

namespace lithium\data;

use \lithium\util\String;
use \lithium\util\Collection;

class Connections extends \lithium\core\Object {

	protected static $_configurations = null;

	protected static $_connections = null;

	public static function __init() {
		static::$_connections = new Collection();
		static::$_configurations = new Collection();
		require LITHIUM_APP_PATH . '/config/connections.php';
	}

	public static function add($name, $type = null, $config = array()) {
		if (is_array($type)) {
			list($config, $type) = array($type, null);
		}
		$defaults = array(
			'type'     => $type ?: 'Database',
			'adapter'  => null,
			'host'     => 'localhost',
			'login'    => '',
			'password' => ''
		);
		return static::$_configurations[$name] = (array)$config + $defaults;
	}

	public static function get($name = null, $options = array()) {
		$defaults = array('config' => false, 'autoBuild' => true);
		$options += $defaults;

		if (empty($name)) {
			return static::$_configurations->keys();
		}

		if (!isset(static::$_configurations[$name])) {
			return null;
		}

		if ($options['config']) {
			return static::$_configurations[$name];
		}

		if (!isset(static::$_connections[$name]) && $options['autoBuild']) {
			return static::$_connections[$name] = static::_build(static::$_configurations[$name]);
		} elseif (!$options['autoBuild']) {
			return null;
		}
		return static::$_connections[$name];
	}

	/**
	* clear connections and configurations
	*
	* @return void
	*/
	public static function clear() {
		static::$_connections = new Collection();
		static::$_configurations = new Collection();
	}

	/**
	 * Constructs a DataSource object or adapter object instance from a configuration array.
	 *
	 * @param array $config
	 * @return object
	 * @todo Refactor class paths into lithium\core\Libraries
	 */
	protected static function _build($config) {
		$path = 'lithium\data\source\{:type}' . ($config['adapter'] ? '\adapter\{:adapter}' : '');
		$class = String::insert($path, $config);
		return new $class($config);
	}
}

?>