<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use \Exception;
use \lithium\util\String;
use \lithium\util\Collection;
use \lithium\core\Libraries;

class Connections extends \lithium\core\StaticObject {

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
		$class = $config['adapter'];
		if (!class_exists($class)) {
			if (empty($config['adapter'])) {
				$config['adapter'] = $config['type'];
				$config['type'] = null;
			}
			$class = Libraries::locate("dataSources.{$config['type']}", $config['adapter']);
		}
		if (class_exists($class)) {
			return new $class($config);
		}
		throw new Exception("{$config['adapter']} adapter could not be found");
	}
}

?>