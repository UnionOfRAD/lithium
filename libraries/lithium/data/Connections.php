<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use \Exception;
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
class Connections extends \lithium\core\Adaptable {

	/**
	 * A Collection of the configurations you add through Connections::add().
	 *
	 * @var Collection
	 */
	protected static $_configurations = null;

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'data.source';

	/**
	 * Initialization of static class
	 * Starts static properties and includes the app Connections.php file.
	 *
	 * @return void
	 */
	public static function __init() {
		parent::__init();
		require LITHIUM_APP_PATH . '/config/connections.php';
	}

	/**
	 * Add connection configurations to your app in `/app/config/connections.php`
	 *
	 * For example:
	 * {{{
	 * Connections::add('default', 'database', array(
	 *     'adapter' => 'MySql',
	 *     'host' => 'localhost',
	 *     'login' => 'root',
	 *     'password' => '',
	 *     'database' => 'my_blog'
	 * ));
	 * }}}
	 *
	 * or
	 *
	 * {{{
	 * Connections::add('couch', 'http', array(
	 *     'adapter' => 'Couch','host' => '127.0.0.1', 'port' => 5984
	 * ));
	 * }}}
	 *
	 * @param string $name The name by which this connection is referenced. Use this name to
	 *        retrieve the connection again using `Connections::get()`, or to bind a model to it
	 *        using `Model::$_meta['connection']`.
	 * @param string $type The type of data source that defines this connection; typically a class
	 *        or name-space name. Relational database data sources, use `'database'`, while CouchDB
	 *        and other HTTP-related data sources use `'http'`, etc. For classes which directly
	 *        extend `lithium\data\Source`, and do not use an adapter, simply use the name of the
	 *        class, i.e. `'MongoDb'`.
	 * @param array $config Contains all additional configuration information used by the
	 *        connection, including the name of the adapter class where applicable (i.e. `MySql`),
	 *        the server name and port or socket to connect to, and (typically) the name of the
	 *        database or other entity to use. Each adapter has its own specific configuration
	 *        settings for handling things like connection persistence, data encoding, etc. See the
	 *        individual adapter or data source class for more information on what configuration
	 *        settings it supports.
	 * @return array Returns the final post-processed connection information, as stored in the
	 *         internal configuration array used by `Connections`.
	 * @see lithium\data\Model::$_meta
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
		return static::$_configurations[$name] = (array) $config + $defaults;
	}

	/**
	 * Read the configuration or access the connections you have set up.
	 *
	 * Usage:
	 * {{{
	 * // Gets the names of all available configurations
	 * $configurations = Connections::get();
	 *
	 * // Gets the configuration array for the connection named 'db'
	 * $config = Connections::get('db', array('config' => true));
	 *
	 * // Gets the instance of the connection object, configured with the settings defined for
	 * // this object in Connections::add()
	 * $dbConnection = Connection::get('db');
	 *
	 * // Gets the connection object, but only if it has already been built.
	 * // Otherwise returns null.
	 * $dbConnection = Connection::get('db', array('autoCreate' => false));
	 * }}}
	 *
	 * @param string $name The name of the connection to get, as defined in the first parameter of
	 *        `add()`, when the connection was initially created.
	 * @param array $options Options to use when returning the connection:
	 *        - `'autoCreate'`: If `false`, the connection object is only returned if it has
	 *          already been instantiated by a previous call.
	 *        - `'config'`: If `true`, returns an array representing the connection's internal
	 *          configuration, instead of the connection itself.
	 * @return mixed A configured instance of the connection, or an array of the configuration used.
	 */
	public static function get($name = null, $options = array()) {
		$defaults = array('config' => false, 'autoCreate' => true);
		$options += $defaults;

		if (empty($name)) {
			return static::$_configurations->keys();
		}

		if (!isset(static::$_configurations[$name])) {
			return null;
		}
		if ($options['config']) {
			return static::_config($name);
		}
		$settings = static::$_configurations[$name];

		if (!isset($settings[0]['adapter']) || !is_object($settings[0]['adapter'])) {
			if (!$options['autoCreate']) {
				return null;
			}
		}
		return static::adapter($name);
	}

	/**
	 * Constructs a data source or adapter object instance from a configuration array.
	 *
	 * @param array $config
	 * @param array $paths
	 * @return object
	 */
	protected static function _class($config, $paths = array()) {
		if (!$config['adapter']) {
			$config['adapter'] = $config['type'];
		} else {
			$paths = array_merge(array("adapter.data.source.{$config['type']}"), (array) $paths);
		}
		return parent::_class($config, $paths);
	}
}

?>