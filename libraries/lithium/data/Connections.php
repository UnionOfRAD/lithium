<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use \lithium\util\String;
use \lithium\util\Collection;
use \lithium\core\Libraries;

/**
 * The `Connections` class manages a list of named configurations that connect to external
 * resources. Connections are usually comprised of a type (i.e. `'Database'` or `'Http'`), a
 * reference to an adapter class (i.e. `'MySql'` or `'MongoDb'`), and authentication credentials.
 *
 * While connections can be added and removed dynamically during the course of your application
 * (using `Connections::add()`), it is most typical to define all connections at once, in
 * `app/config/connections.php`.
 *
 * `Connections` handles adapter classes efficiently by only loading adapter classes and creating
 * instances when they are requested (using `Connections::get()`).
 *
 * Adapters are usually subclasses of `lithium\data\Source`.
 *
 * @see lithium\data\Source
 */
class Connections extends \lithium\core\StaticObject {

	/**
	 * A Collection of the configurations you add through Connections::add()
	 *
	 * @var Collection
	 */
	protected static $_configurations = null;

	/**
	 * As each connection is built, a reference to the instance is stored in
	 * this Collection under the name it was given when configuration was added.
	 *
	 * @var Collection
	 */
	protected static $_connections = null;

	/**
	 * Initialization of static class
	 * Starts static properties and includes the app connections.php file
	 *
	 * @return void
	 */
	public static function __init() {
		static::$_connections = new Collection();
		static::$_configurations = new Collection();
		require LITHIUM_APP_PATH . '/config/connections.php';
	}

	/**
	 * Add connection configurations to your app in `/app/config/connections.php`
	 *
	 * @example {{{
     *              Connections::add('database', 'Database', array(
	 *                  'adapter' => 'MySql',
	 *                  'host' => 'localhost',
	 *                  'login' => 'root',
	 *                  'password' => '',
	 *                  'database' => 'lithium-blog'
	 *               ));
     *           }}}
	 *           {{{
	 *               Connections::add(couch', 'http', array(
	 *                   'adapter' => 'Couch','host' => '127.0.0.1', 'port' => 5984
	 *               ));
	 *           }}}
	 *           {{{
	 *               Connections::add('sql', $config);
	 *           }}}
	 * @param string $name
	 * @param string $type
	 * @param array $config
	 * @return array
	 */
	public static function add($name, $type = null, $config = array()) {
		if (is_array($type)) {
			list($config, $type) = array($type, null);
		}
		$defaults = array(
			'type'     => $type ?: 'database',
			'adapter'  => null,
			'host'     => 'localhost',
			'login'    => '',
			'password' => ''
		);
		return static::$_configurations[$name] = (array)$config + $defaults;
	}

	/**
	 * Read the configuration or access the connections you have set up.
	 *
	 * @example  {{{
	 *               $configurations = Connections::get();
	 *           }}}
	 *           {{{
	 *               $config = Connections::get('db', array('config' => true));
	 *           }}}
	 *           {{{
	 *               $dbConnection = Connection::get('db', array('autoBuild' => true));
	 *           }}}
	 *           {{{
	 *               $dbConnection = Connection::get('db');
	 *           }}}
	 * @param string $name
	 * @param array $options
	 * @return object
	 */
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
	* Hard reset of connections and configurations, clearing out any currently configured or built
	*
	* @return void
	*/
	public static function clear() {
		static::$_connections = new Collection();
		static::$_configurations = new Collection();
	}

	/**
	 * Constructs a data source or adapter object instance from a configuration array.
	 *
	 * @param array $config
	 * @return object
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
		return new $class($config);
	}
}

?>
