<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data;

use lithium\core\Libraries;

/**
 * The `Connections` class manages a list of named configurations that connect to external
 * resources. Connections are usually comprised of a type (i.e. `'database'` or `'http'`), a
 * reference to an adapter class (i.e. `'MySql'` or `'CouchDb'`), and authentication credentials.
 *
 * While connections can be added and removed dynamically during the course of your application
 * (using `Connections::add()`), it is most typical to define all connections at once, in
 * `config/bootstrap/connections.php`.
 *
 * The `Connections` class handles adapter classes efficiently by only loading adapter classes and
 * creating instances when they are requested (using `Connections::get()`).
 *
 * Adapters are usually subclasses of `lithium\data\Source`.
 *
 * @see lithium\data\Source
 */
class Connections extends \lithium\core\Adaptable {

	/**
	 * A Collection of the configurations you add through Connections::add().
	 *
	 * @var `lithium\util\Collection`
	 */
	protected static $_configurations = [];

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'data.source';

	/**
	 * Add connection configurations to your app in `config/bootstrap/connections.php`
	 *
	 * For example:
	 * ```
	 * Connections::add('default', [
	 *	'type' => 'database',
	 *	'adapter' => 'MySql',
	 *	'host' => 'localhost',
	 *	'login' => 'root',
	 *	'password' => '',
	 *	'database' => 'my_blog'
	 * ]);
	 * ```
	 *
	 * or
	 *
	 * ```
	 * Connections::add('couch', [
	 *	'type' => 'http',
	 *	'adapter' => 'CouchDb',
	 *	'host' => '127.0.0.1',
	 *	'port' => 5984
	 * ]);
	 * ```
	 *
	 * or
	 *
	 * ```
	 * Connections::add('mongo', [
	 *	'type' => 'MongoDb',
	 *	'database' => 'my_app'
	 * ]);
	 * ```
	 *
	 * @see lithium\data\Model::$_meta
	 * @param string $name The name by which this connection is referenced. Use this name to
	 *        retrieve the connection again using `Connections::get()`, or to bind a model to it
	 *        using `Model::$_meta['connection']`.
	 * @param array $config Contains all additional configuration information used by the
	 *        connection, including the name of the adapter class where applicable (i.e. `MySql`),
	 *        and typcially the server host/socket to connect to, the name of the database or
	 *        other entity to use. Each adapter has its own specific configuration settings for
	 *        handling things like connection persistence, data encoding, etc. See the
	 *        individual adapter or data source class for more information on what configuration
	 *        settings it supports. Basic / required options supported are:
	 *        - `'type'` _string_: The type of data source that defines this connection; typically a
	 *          class or namespace name. Relational database data sources, use `'database'`, while
	 *          CouchDB and other HTTP-related data sources use `'http'`, etc. For classes which
	 *          directly extend `lithium\data\Source`, and do not use an adapter, simply use the
	 *          name of the class, i.e. `'MongoDb'`.
	 *        - `'adapter'` _string_: For `type`s such as `'database'` which are adapter-driven,
	 *          provides the name of the adapter associated with this configuration.
	 * @return array Returns the final post-processed connection information, as stored in the
	 *         internal configuration array used by `Connections`.
	 */
	public static function add($name, array $config = []) {
		$defaults = [
			'type'     => null,
			'adapter'  => null
		];
		return static::$_configurations[$name] = $config + $defaults;
	}

	/**
	 * Removing a configuration.
	 *
	 * @param string $name The name by which this connection is referenced.
	 */
	public static function remove($name) {
		unset(static::$_configurations[$name]);
	}

	/**
	 * Read the configuration or access the connections you have set up.
	 *
	 * Usage:
	 * ```
	 * // Gets the names of all available configurations
	 * $configurations = Connections::get();
	 *
	 * // Gets the configuration array for the connection named 'db'
	 * $config = Connections::get('db', ['config' => true]);
	 *
	 * // Gets the instance of the connection object, configured with the settings defined for
	 * // this object in Connections::add()
	 * $dbConnection = Connections::get('db');
	 *
	 * // Gets the connection object, but only if it has already been built.
	 * // Otherwise returns null.
	 * $dbConnection = Connections::get('db', ['autoCreate' => false]);
	 * ```
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
	public static function get($name = null, array $options = []) {
		static $mockAdapter;

		$defaults = ['config' => false, 'autoCreate' => true];
		$options += $defaults;

		if ($name === false) {
			if (!$mockAdapter) {
				$class = Libraries::locate('data.source', 'Mock');
				$mockAdapter = new $class();
			}
			return $mockAdapter;
		}

		if (!$name) {
			return array_keys(static::$_configurations);
		}

		if (!isset(static::$_configurations[$name])) {
			return null;
		}
		if ($options['config']) {
			return static::_config($name);
		}
		$settings = static::$_configurations[$name];

		if (!isset($settings[0]['object']) && !$options['autoCreate']) {
			return null;
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
	protected static function _class($config, $paths = []) {
		if (!$config['adapter']) {
			$config['adapter'] = $config['type'];
		} else {
			$paths = array_merge(["adapter.data.source.{$config['type']}"], (array) $paths);
		}
		return parent::_class($config, $paths);
	}
}

?>