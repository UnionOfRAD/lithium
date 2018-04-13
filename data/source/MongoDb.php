<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source;

use stdClass;
use Exception;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\ReadConcern;
use MongoDB\Driver\WriteConcern;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\Command;
use lithium\aop\Filters;
use lithium\util\Set;
use lithium\util\Inflector;
use lithium\core\ConfigException;
use lithium\core\NetworkException;
use lithium\net\HostString;

/**
 * A data source adapter which allows you to connect to the MongoDB database engine. MongoDB is an
 * Open Source distributed document database which bridges the gap between key/value stores and
 * relational databases.
 *
 * Rather than operating on records and record sets, queries against MongoDB will return nested sets
 * of `Document` objects. A `Document`'s fields can contain both simple and complex data types
 * (i.e. arrays) including other `Document` objects.
 *
 * After installing MongoDB, you can connect to it as follows:
 * ```
 * Connections::add('default', [
 *     'type' => 'MongoDb',
 *     'database' => 'my_app',
 *     'host' => 'example.org:27017',
 *     'uriOptions' => ['replicaSet' => 'Cluster0'],
 * ]);
 * ```
 *
 * Or with multiple hosts:
 * ```
 *     'host' => [host1:27017, host2:27018]
 * ]);
 * ```
 *
 * Or using a DSN:
 * ```
 * Connections::add('default', [
 *     'type' => 'MongoDb',
 *     'dsn' => 'mongodb://c0-s1.mongodb.net:27017,c0-s2.mongodb.net:27017/my_app?replicaSet=rs1',
 * ]);
 * ```
 *
 * Specifiyng a cluster works with MongoDB 3.6 and above, too:
 * ```
 *     'dsn' => 'mongodb+srv://cluster0-my_app.mongodb.net/my_app',
 * ]);
 * ```
 *
 * By default, it will attempt to connect to a Mongo instance running on `localhost` on port
 * 27017. See the MongoDB documentation for details on the accepted configuration settings.
 *
 * This adapter is officially supported on PHP 7, where it simply needs the `mongodb`
 * extension. MongoDB 3.4 and up is required, versions lower than this might work,
 * but are not supported or tested.
 *
 * @see lithium\data\entity\Document
 * @see lithium\data\Connections::add()
 * @link https://secure.php.net/manual/en/mongodb-driver-manager.construct.php
 * @link https://docs.mongodb.com/manual/reference/connection-string/#connections-connection-options
 * @link https://pecl.php.net/package/mongodb
 * @link http://www.mongodb.org/
 */
class MongoDb extends \lithium\data\Source {

	/**
	 * The default host used to connect to the server.
	 */
	const DEFAULT_HOST = 'localhost';

	/**
	 * The default port used to connect to the server.
	 */
	const DEFAULT_PORT = 27017;

	/**
	 * The Manager class instance.
	 *
	 * @var object
	 */
	public $manager = null;

	/**
	 * Classes used by this class.
	 *
	 * @var array
	 */
	protected $_classes = [
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
		'result' => 'lithium\data\source\mongo_db\Result',
		'schema' => 'lithium\data\source\mongo_db\Schema',
		'exporter' => 'lithium\data\source\mongo_db\Exporter',
		'relationship' => 'lithium\data\model\Relationship',
		'manager' => 'MongoDB\Driver\Manager'
	];

	/**
	 * Map of typical SQL-like operators to their MongoDB equivalents.
	 *
	 * @var array Keys are SQL-like operators, value is the MongoDB equivalent.
	 */
	protected $_operators = [
		'<' => '$lt',
		'>' => '$gt',
		'<=' => '$lte',
		'>=' => '$gte',
		'!=' => ['single' => '$ne', 'multiple' => '$nin'],
		'<>' => ['single' => '$ne', 'multiple' => '$nin'],
		'or' => '$or',
		'||' => '$or',
		'not' => '$not',
		'!' => '$not',
		'and' => '$and',
		'&&' => '$and',
		'nor' => '$nor'
	];

	/**
	 * List of comparison operators to use when performing boolean logic in a query.
	 *
	 * @var array
	 */
	protected $_boolean = ['&&', '||', 'and', '$and', 'or', '$or', 'nor', '$nor'];

	/**
	 * A closure or anonymous function which receives an instance of this class, a
	 * collection name and associated meta information, and returns an array defining the
	 * schema for an associated model, where the keys are field names, and the values are
	 * arrays defining the type information for each field. At a minimum, type arrays
	 * must contain a `'type'` key. For more information on schema definitions see the
	 * `$_schema` property of the `Model` class.
	 *
	 * This example shows how to implement a schema callback in your database connection
	 * configuration that fetches and returns the schema data. It defines an optional
	 * MongoDB convention in which the schema for each individual collection is stored
	 * in a `schemas` collection, where each document contains the name of a collection,
	 * along with a `'data'` key, which contains the schema for that collection.
	 *
	 * ```
	 * Connections::add('default', [
	 *  'type' => 'MongoDb',
	 *  'host' => 'localhost',
	 *  'database' => 'app',
	 *  'schema' => function($db, $collection, $meta) {
	 *      $result = $db->connection->schemas->findOne(compact('collection'));
	 *      return $result ? $result['data'] : [];
	 *  }
	 * ]);
	 * ```
	 *
	 * A complete schema defintion looks like:
	 * ```
	 * [
	 *     '_id'  => ['type' => 'id'],
	 *     'name' => ['type' => 'string', 'default' => 'Moe', 'null' => false],
	 *     'sign' => ['type' => 'string', 'default' => 'bar', 'null' => false],
	 *     'age'  => ['type' => 'integer', 'default' => 0, 'null' => false]
	 * ];
	 * ```
	 *
	 * The types in the schema map to database native type like this:
	 * ```
	 *  id      => MongoDB\BSON\ObjectId
	 *  date    => MongoDB\BSON\UTCDateTime
	 *  regex   => MongoDB\BSON\Regex
	 *  integer => integer
	 *  float   => float
	 *  boolean => boolean
	 *  code    => MongoDB\BSON\Javascript
	 *  binary  => MongoDB\BSON\Binary
	 * ```
	 *
	 * @see lithium\data\Model::$_schema
	 * @var Closure|null
	 */
	protected $_schema = null;

	/**
	 * List of configuration keys which will be automatically assigned to their corresponding
	 * protected class properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = ['schema', 'classes' => 'merge'];

	/**
	 * With no parameter, checks to see if adapter's dependencies are installed. With a
	 * parameter, queries for a specific supported feature.
	 *
	 * A compatibility layer cannot be detected via `extension_loaded()`, thus we check
	 * for the existence of one of the legacy classes to determine if this adapter can be
	 * enabled at all.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *               `"arrays"`.
	 * @return boolean Returns `true` if the particular feature (or if MongoDB) support is enabled,
	 *         otherwise `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return extension_loaded('mongodb');
		}
		$features = [
			'arrays' => true,
			'transactions' => false,
			'booleans' => true,
			'relationships' => true,
			'schema' => false,
			'sources' => true
		];
		return isset($features[$feature]) ? $features[$feature] : null;
	}

	/**
	 * Constructor.
	 *
	 * @see lithium\data\Connections::add()
	 * @see lithium\data\source\MongoDb::$_schema
	 * @link http://php.net/mongo.construct.php PHP Manual: Mongo::__construct()
	 * @param array $config Configuration options required to connect to the database, including:
	 *        - `'host'` _string|array_: A string in the form of `'<host>'`, `'<host>:<port>'` or
	 *          `':<port>'` indicating the host and/or port to connect to.
	 *          Use the array format for multiple hosts: `['167.221.1.5:11222', '167.221.1.6']`
	 *        - `'login'` _string_: Username to use when connecting to server. Defaults to `'root'`.
	 *        - `'password'` _string_: Password to use when connecting to server. Defaults to `''`.
	 *        - `'database'` _string_: The name of the database to connect to. Defaults to `null`.
	 *        - `'timeout'` _integer_: The number of milliseconds a connection attempt will wait
	 *          before timing out and throwing an exception. Defaults to `100`.
	 *        - `'schema'` _\Closure_: A closure or anonymous function which returns the schema
	 *          information for a model class. See the `$_schema` property for more information.
	 *
	 *        Disables auto-connect, which is by default enabled in `Source`. Instead before
	 *        each query execution the connection is checked and if needed (re-)established.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'host' => static::DEFAULT_HOST . ':' . static::DEFAULT_PORT,
			'login' => null,
			'password' => null,
			'database' => null,
			'dsn' => null,
			'timeout' => 1000,
			'uriOptions' => [
				'w' => 'majority',
				'wTimeoutMS' => 10000,
				'journal' => true,
				'readConcernLevel' => 'local',
				'readPreference' => 'primary',
				'readPreferenceTags' => [],
			],
			'driverOptions' => [
				'allow_invalid_hostname' => false,
				'weak_cert_validation' => false
			]
		];
		$config = Set::merge($defaults, $config);
		if (!isset($config['uriOptions']['connectTimeoutMS']) && $config['timeout']) {
			$config['uriOptions']['connectTimeoutMS'] = $config['timeout'];
		}
		parent::__construct($config);
	}

	/**
	 * Initializer. Adds operator handlers which will later allow to correctly cast any
	 * values. Constructs a DSN from configuration, if not given.
	 *
	 * @see lithium\data\source\MongoDb::$_operators
	 * @see lithium\data\source\MongoDb::_operators()
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		if (is_string($this->_config['host']) && stristr($this->_config['host'], '://')) {
			$this->_config['dsn'] = $this->_config['host'];
		}

		switch (true) {
			case (!empty($this->_config['dsn'])):
				$connectionConfig = $this->_parseConnectionString($this->_config['dsn']);
				$this->_config = Set::merge($this->_config, $connectionConfig);
				break;
			case (!$this->_config['host']):
				throw new ConfigException('No host configured.');
			default:
				$hosts = [];
				foreach ((array) $this->_config['host'] as $host) {
					$host = HostString::parse($host) + [
						'host' => static::DEFAULT_HOST,
						'port' => static::DEFAULT_PORT
					];
					$hosts[] = "{$host['host']}:{$host['port']}";
				}
				if ((!$this->_config['login'])) {
					$this->_config['dsn'] = sprintf('mongodb://%s', implode(',', $hosts));
				} else {
					$this->_config['dsn'] = sprintf(
						'mongodb://%s:%s@%s/%s',
						$this->_config['login'],
						$this->_config['password'],
						implode(',', $hosts),
						$this->_config['database']
					);
				}
		}

		$manager = $this->_classes['manager'];
		$this->manager = new $manager(
			$this->_config['dsn'],
			$this->_config['uriOptions'],
			$this->_config['driverOptions']
		);

		$this->_operators += [
			'like' => function($key, $value) {
				return new Regex($value);
			},
			'$exists' => function($key, $value) {
				return ['$exists' => (boolean) $value];
			},
			'$type' => function($key, $value) {
				return ['$type' => (integer) $value];
			},
			'$mod' => function($key, $value) {
				$value = (array) $value;
				return ['$mod' => [current($value), next($value) ?: 0]];
			},
			'$size' => function($key, $value) {
				return ['$size' => (integer) $value];
			},
			'$elemMatch' => function($operator, $values, $options = []) {
				$options += [
					'castOpts' => [],
					'field' => ''
				];
				$options['castOpts'] += ['pathKey' => $options['field']];
				$values = (array) $values;

				if (empty($options['castOpts']['schema'])) {
					return ['$elemMatch' => $values];
				}
				foreach ($values as $key => &$value) {
					$value = $options['castOpts']['schema']->cast(
						null, $key, $value, $options['castOpts']
					);
				}
				return ['$elemMatch' => $values];
			}
		];
	}

	/**
	 * Configures a model class by overriding the default dependencies for `'set'` and
	 * `'entity'` , and sets the primary key to `'_id'`, in keeping with Mongo's conventions.
	 *
	 * @see lithium\data\Model::$_meta
	 * @see lithium\data\Model::$_classes
	 * @param string $class The fully-namespaced model class name to be configured.
	 * @return Returns an array containing keys `'classes'` and `'meta'`, which will be merged with
	 *         their respective properties in `Model`.
	 */
	public function configureClass($class) {
		return [
			'classes' => $this->_classes,
			'schema' => [],
			'meta' => ['key' => '_id', 'locked' => false]
		];
	}

	/**
	 * Connections are created lazily on read/write queries so this method is not applicable.
	 *
	 * @return boolean Returns `true`.
	 */
	public function connect() {
		return $this->_isConnected = true;
	}

	/**
	 * Disconnect from the Mongo server.
	 *
	 * @return boolean Returns `true`.
	 */
	public function disconnect() {
		unset($this->manager);
		return true;
	}

	/**
	 * Returns the list of collections in the currently-connected database.
	 *
	 * @param string $class The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of objects to which models can connect.
	 */
	public function sources($class = null) {
		$collections = $this->manager->executeCommand($this->_config['database'], new Command(['listCollections' => 1]));
		$names = [];
		foreach ($collections as $collection) {
			$names[] = $collection->name;
		}
		return $names;
	}

	/**
	 * Gets the column 'schema' for a given MongoDB collection. Only returns a schema if the
	 * `'schema'` configuration flag has been set in the constructor.
	 *
	 * @see lithium\data\source\MongoDb::$_schema
	 * @param mixed $collection Specifies a collection name for which the schema should be queried.
	 * @param mixed $fields Any schema data pre-defined by the model.
	 * @param array $meta Any meta information pre-defined in the model.
	 * @return array Returns an associative array describing the given collection's schema.
	 */
	public function describe($collection, $fields = [], array $meta = []) {
		if (!$fields && ($func = $this->_schema)) {
			$fields = $func($this, $collection, $meta);
		}
		return $this->_instance('schema', compact('fields'));
	}

	/**
	 * Quotes identifiers.
	 *
	 * MongoDb does not need identifiers quoted, so this method simply returns the identifier.
	 *
	 * @param string $name The identifier to quote.
	 * @return string The quoted identifier.
	 */
	public function name($name) {
		return $name;
	}

	/**
	 * A method dispatcher that allows direct calls to native methods in PHP's `Mongo` object. Read
	 * more here: http://php.net/manual/class.mongo.php
	 *
	 * For example (assuming this instance is stored in `Connections` as `'mongo'`):
	 * ```
	 * // Manually repairs a MongoDB instance
	 * Connections::get('mongo')->repairDB($db); // returns null
	 * ```
	 *
	 * @param string $method The name of native method to call. See the link above for available
	 *        class methods.
	 * @param array $params A list of parameters to be passed to the native method.
	 * @return mixed The return value of the native method specified in `$method`.
	 */
	public function __call($method, $params) {
		return call_user_func_array([$this->manager, $method], $params);
	}

	/**
	 * Determines if a given method can be called.
	 *
	 * @param string $method Name of the method.
	 * @param boolean $internal Provide `true` to perform check from inside the
	 *                class/object. When `false` checks also for public visibility;
	 *                defaults to `false`.
	 * @return boolean Returns `true` if the method can be called, `false` otherwise.
	 */
	public function respondsTo($method, $internal = false) {
		$childRespondsTo = is_object($this->manager) && is_callable([$this->manager, $method]);
		return parent::respondsTo($method, $internal) || $childRespondsTo;
	}

	/**
	 * Normally used in cases where the query is a raw string (as opposed to a `Query` object),
	 * to database must determine the correct column names from the result resource. Not
	 * applicable to this data source.
	 *
	 * @internal param mixed $query
	 * @internal param \lithium\data\source\resource $resource
	 * @internal param object $context
	 * @return array
	 */
	public function schema($query, $resource = null, $context = null) {
		return [];
	}

	/**
	 * Create new document
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function create($query, array $options = []) {
		$defaults = [
			'ordered' => true,
			'w' => $this->_config['uriOptions']['w'],
			'wTimeoutMS' => $this->_config['uriOptions']['wTimeoutMS'],
			'journal' => $this->_config['uriOptions']['journal']
		];
		$options += $defaults;

		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$exporter = $this->_classes['exporter'];

			$query = $params['query'];
			$options = $params['options'];

			$args = $query->export($this, ['keys' => ['source', 'data']]);
			$data = $exporter::get('create', $args['data']);
			$source = $args['source'];

			$insertQuery = new BulkWrite(['ordered' => $options['ordered']]);
			$data['create']['_id'] = empty($data['create']['_id']) ? new ObjectId() : $data['create']['_id'];
			$insertQuery->insert($data['create']);

			try {
				$writeConcern = new WriteConcern($options['w'], $options['wTimeoutMS'], $options['journal']);
				$this->manager->executeBulkWrite("{$this->_config['database']}.{$source}", $insertQuery, $writeConcern);

				if ($query->entity()) {
					$query->entity()->sync($data['create']['_id']);
				}
				return true;
			} catch (BulkWriteException $e) {
				return false;
			}
		});
	}

	/**
	 * Read from document
	 *
	 * @param string $query
	 * @param array $options
	 * @return object
	 * @filter
	 */
	public function read($query, array $options = []) {
		$defaults = [
			'readPreference' => $this->_config['uriOptions']['readPreference'],
			'readPreferenceTags' => $this->_config['uriOptions']['readPreferenceTags'],
			'return' => 'resource'
		];
		$options += $defaults;

		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$query = $params['query'];
			$options = $params['options'];
			$args = $query->export($this);
			$source = $args['source'];
			$model = $query->model();

			if ($group = $args['group']) {
				$result = $this->_group($group, $args, $options);
				$config = ['class' => 'set', 'defaults' => false] + compact('query') + $result;
				return $model::create($config['data'], $config);
			}

			$readQuery = new Query($args['conditions'], [
				'projection' => $args['fields'],
				'sort' => $args['order'],
				'limit' => $args['limit'],
				'skip' => $args['offset']
			]);

			$readPreference = new ReadPreference($options['readPreference'], $options['readPreferenceTags']);
			$resource = $this->manager->executeQuery("{$this->_config['database']}.{$source}", $readQuery, $readPreference);

			$result = $this->_instance('result', compact('resource'));
			$config = compact('result', 'query') + ['class' => 'set', 'defaults' => false];
			$collection = $model::create([], $config);

			if (is_object($query) && $query->with()) {
				$model::embed($collection, $query->with());
			}
			return $collection;
		});
	}

	protected function _group($group, $args, $options) {
		$conditions = $args['conditions'];
		$group += ['$reduce' => $args['reduce'], 'initial' => $args['initial']];
		$command = ['group' => $group + ['ns' => $args['source'], 'cond' => $conditions]];

		$stats = $this->connection->command($command);
		$data = isset($stats['retval']) ? $stats['retval'] : null;
		unset($stats['retval']);
		return compact('data', 'stats');
	}

	/**
	 * Update document
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function update($query, array $options = []) {
		$defaults = [
			'ordered' => true,
			'multiple' => true,
			'upsert' => false,
			'w' => $this->_config['uriOptions']['w'],
			'wTimeoutMS' => $this->_config['uriOptions']['wTimeoutMS'],
			'journal' => $this->_config['uriOptions']['journal']
		];
		$options += $defaults;

		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$exporter = $this->_classes['exporter'];

			$options = $params['options'];
			$query = $params['query'];
			$args = $query->export($this, ['keys' => ['conditions', 'source', 'data']]);
			$source = $args['source'];
			$data = $args['data'];

			if ($query->entity()) {
				$data = $exporter::get('update', $data);
			}

			$update = $query->entity() ? $exporter::toCommand($data) : $data;

			if (empty($update)) {
				return true;
			}
			if ($options['multiple'] && !preg_grep('/^\$/', array_keys($update))) {
				$update = ['$set' => $update];
			}

			$updateQuery = new BulkWrite(['ordered' => $options['ordered']]);
			$updateQuery->update($args['conditions'], $update, [
				'multi' => $options['multiple'],
				'upsert' => $options['upsert']
			]);

			try {
				$writeConcern = new WriteConcern($options['w'], $options['wTimeoutMS'], $options['journal']);
				$this->manager->executeBulkWrite("{$this->_config['database']}.{$source}", $updateQuery, $writeConcern);
				$query->entity() ? $query->entity()->sync() : null;
				return true;
			} catch (BulkWriteException $e) {
				return false;
			}
		});
	}

	/**
	 * Delete document
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function delete($query, array $options = []) {
		$defaults = [
			'ordered' => true,
			'justOne' => false,
			'w' => $this->_config['uriOptions']['w'],
			'wTimeoutMS' => $this->_config['uriOptions']['wTimeoutMS'],
			'journal' => $this->_config['uriOptions']['journal']
		];
		$options += $defaults;
		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$query = $params['query'];
			$options = $params['options'];
			$args = $query->export($this, ['keys' => ['source', 'conditions']]);
			$source = $args['source'];
			$conditions = $args['conditions'];

			$deleteQuery = new BulkWrite(['ordered' => $options['ordered']]);
			$deleteQuery->delete($conditions, ['limit' => $options['justOne']]);

			try {
				$writeConcern = new WriteConcern($options['w'], $options['wTimeoutMS'], $options['journal']);
				$this->manager->executeBulkWrite("{$this->_config['database']}.{$source}", $deleteQuery, $writeConcern);
				if ($query->entity()) {
					$query->entity()->sync(null, [], ['dematerialize' => true]);
				}
				return true;
			} catch (BulkWriteException $e) {
				return false;
			}
		});
	}

	/**
	 * Executes calculation-related queries, such as those required for `count`.
	 *
	 * @param string $type Only accepts `count`.
	 * @param mixed $query The query to be executed.
	 * @param array $options Optional arguments for the `read()` query that will be executed
	 *        to obtain the calculation result.
	 * @return integer Result of the calculation.
	 */
	public function calculation($type, $query, array $options = []) {
		$query->calculate($type);

		switch ($type) {
			case 'count':
				$args = $query->export($this);

				$pipeline = [];
				if ($args['conditions']) {
					$pipeline[] = ['$match' => $args['conditions']];
				}
				$pipeline[] = ['$group' => ['_id' => null, 'count' => ['$sum' => 1]]];

				$result = $this->manager->executeCommand($this->_config['database'], new Command([
					'aggregate' => $args['source'],
					'pipeline' => $pipeline,
					'cursor' => new stdClass,
				]))->toArray();

				return (!empty($result)) ? current($result)->count : 0;
		}
	}

	/**
	 * Document relationships.
	 *
	 * @param string $class
	 * @param string $type Relationship type, e.g. `belongsTo`.
	 * @param string $name
	 * @param array $config
	 * @return array
	 */
	public function relationship($class, $type, $name, array $config = []) {
		$fieldName = $this->relationFieldName($type, $name);
		$config += compact('name', 'type', 'key', 'fieldName');
		$config['from'] = $class;

		return $this->_instance('relationship', $config + [
			'strategy' => function($rel) use ($config, $class, $name, $type) {
				if (isset($config['key'])) {
					return [];
				}
				$link = null;
				$hasLink = isset($config['link']);

				$result = [];
				$to = $rel->to();
				$local = $class::key();
				$className = $class::meta('name');

				$keys = [
					[$class, $name],
					[$class, Inflector::singularize($name)],
					[$to, Inflector::singularize($className)],
					[$to, $className]
				];
				foreach ($keys as $map) {
					list($on, $key) = $map;
					$key = lcfirst(Inflector::camelize($key));

					if (!$on::hasField($key)) {
						continue;
					}
					$join = ($on === $class) ? [$key => $on::key()] : [$local => $key];
					$result['key'] = $join;

					if (isset($config['link'])) {
						return $result;
					}
					$fieldType = $on::schema()->type($key);

					if ($fieldType === 'id' || $fieldType === 'MongoDB\BSON\ObjectId') {
						$isArray = $on::schema()->is('array', $key);
						$link = $isArray ? $rel::LINK_KEY_LIST : $rel::LINK_KEY;
						break;
					}
				}
				if (!$link && !$hasLink) {
					$link = ($type === "belongsTo") ? $rel::LINK_CONTAINED : $rel::LINK_EMBEDDED;
				}
				return $result + ($hasLink ? [] : compact('link'));
			}
		]);
	}

	/**
	 * Formats `group` clauses for MongoDB.
	 *
	 * @param string|array $group The group clause.
	 * @param object $context
	 * @return array Formatted `group` clause.
	 */
	public function group($group, $context) {
		if (!$group) {
			return;
		}
		if (is_string($group) && strpos($group, 'function') === 0) {
			return ['$keyf' => new Javascript($group)];
		}
		$group = (array) $group;

		foreach ($group as $i => $field) {
			if (is_int($i)) {
				$group[$field] = true;
				unset($group[$i]);
			}
		}
		return ['key' => $group];
	}


	/**
	 * Return formatted identifiers for fields.
	 *
	 * @param array $fields Fields to be parsed
	 * @param object $context
	 * @return array Parsed fields array
	 */
	public function fields($fields, $context) {
		$result = [];
		foreach ($fields as $key => $value) {
			if (is_numeric($key)) {
				$result[$value] = true;
			} else {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Return formatted clause for limit.
	 *
	 * MongoDB doesn't require limit identifer formatting; as a result, this method is not
	 * implemented.
	 *
	 * @param mixed $limit The `limit` clause to be formatted.
	 * @param object $context The `Query` object instance.
	 * @return mixed Formatted `limit` clause.
	 */
	public function limit($limit, $context) {
		return $limit ?: 0;
	}

	/**
	 * Return formatted clause for order.
	 *
	 * @param mixed $order The `order` clause to be formatted
	 * @param object $context
	 * @return mixed Formatted `order` clause.
	 */
	public function order($order, $context) {
		if (!$order) {
			return [];
		}
		if (is_string($order)) {
			return [$order => 1];
		}
		if (!is_array($order)) {
			return [];
		}
		foreach ($order as $key => $value) {
			if (!is_string($key)) {
				unset($order[$key]);
				$order[$value] = 1;
				continue;
			}
			if (is_string($value)) {
				$order[$key] = strtolower($value) === 'asc' ? 1 : -1;
			}
		}
		return $order;
	}

	/**
	 * Returns the field name of a relation name (camelBack).
	 *
	 * @param string The type of the relation.
	 * @param string The name of the relation.
	 * @return string
	 */
	public function relationFieldName($type, $name) {
		$fieldName = Inflector::camelize($name, false);
		if (preg_match('/Many$/', $type)) {
			$fieldName = Inflector::pluralize($fieldName);
		} else {
			$fieldName = Inflector::singularize($fieldName);
		}
		return $fieldName;
	}

	/**
	 * Maps incoming conditions with their corresponding MongoDB-native operators.
	 *
	 * @param array $conditions Array of conditions
	 * @param object $context Context with which this method was called; currently
	 *        inspects the return value of `$context->type()`.
	 * @return array Transformed conditions
	 */
	public function conditions($conditions, $context) {
		if (!$conditions) {
			return [];
		}
		if ($code = $this->_isJavascript($conditions)) {
			return $code;
		}
		$schema = null;
		$model = null;

		if ($context) {
			$schema = $context->schema();
			$model = $context->model();
		}
		return $this->_conditions($conditions, $model, $schema, $context);
	}

	/**
	 * Protected helper method used to format conditions.
	 *
	 * @todo Catch Document/Array objects used in conditions and extract their values.
	 * @param array $conditions The conditions array to be processed.
	 * @param string $model The name of the model class used in the query.
	 * @param object $schema The object containing the schema definition.
	 * @param object $context The `Query` object.
	 * @return array Processed query conditions.
	 */
	protected function _conditions(array $conditions, $model, $schema, $context) {
		$ops = $this->_operators;
		$castOpts = [
			'first' => true, 'database' => $this, 'wrap' => false, 'asContent' => true
		];

		$cast = function($key, $value) use (&$schema, &$castOpts) {
			return $schema ? $schema->cast(null, $key, $value, $castOpts) : $value;
		};

		foreach ($conditions as $key => $value) {
			if (in_array($key, $this->_boolean)) {
				$operator = isset($ops[$key]) ? $ops[$key] : $key;

				foreach ($value as $i => $compare) {
					$value[$i] = $this->_conditions($compare, $model, $schema, $context);
				}
				unset($conditions[$key]);
				$conditions[$operator] = $value;
				continue;
			}
			if (is_object($value)) {
				continue;
			}
			if (!is_array($value)) {
				$conditions[$key] = $cast($key, $value);
				continue;
			}
			$current = key($value);

			if (!isset($ops[$current]) && $current[0] !== '$') {
				$conditions[$key] = ['$in' => $cast($key, $value)];
				continue;
			}
			$conditions[$key] = $this->_operators($key, $value, $schema);
		}
		return $conditions;
	}

	protected function _isJavascript($conditions) {
		if (is_string($conditions)) {
			$conditions = new Javascript($conditions);
		}
		if ($conditions instanceof Javascript) {
			return ['$where' => $conditions];
		}
	}

	protected function _operators($field, $operators, $schema) {
		$castOpts = compact('schema');
		$castOpts += ['first' => true, 'database' => $this, 'wrap' => false];

		$cast = function($key, $value) use (&$schema, &$castOpts) {
			return $schema ? $schema->cast(null, $key, $value, $castOpts) : $value;
		};

		foreach ($operators as $key => $value) {
			if (!isset($this->_operators[$key])) {
				$operators[$key] = $cast($field, $value);
				continue;
			}
			$operator = $this->_operators[$key];

			if (is_array($operator)) {
				$operator = $operator[is_array($value) ? 'multiple' : 'single'];
			}
			if (is_callable($operator)) {
				return $operator($key, $value, compact('castOpts', 'field'));
			}
			unset($operators[$key]);
			$operators[$operator] = $cast($field, $value);
		}
		return $operators;
	}

	protected function _parseConnectionString($connection) {

		$result = array_fill_keys(['login', 'password', 'host', 'database'], null);
		$parts = array_fill_keys(['scheme', 'user', 'pass', 'host', 'port', 'path', 'query'], '');
		$parts = parse_url($connection) + $parts;

		if (!empty($parts['query'])) {
			parse_str($parts['query'], $result['uriOptions']);
			$result['uriOptions'] = array_map(function($value) {
				return (is_numeric($value)) ? (int) $value : $value;
			}, $result['uriOptions']);
		}

		$hostList = (stristr($parts['host'], ','))
			? explode(',', $parts['host'])
			: $parts['host'];

		$hosts = [];
		foreach ((array) $hostList as $host) {
			$host = HostString::parse($host) + [
				'host' => static::DEFAULT_HOST,
				'port' => static::DEFAULT_PORT
			];
			$hosts[] = "{$host['host']}:{$host['port']}";
		}
		$result['host'] = (count($hosts) > 1) ? $hosts : $hosts[0];
		$result['login'] = !empty($parts['user']) ? $parts['user'] : null;
		$result['password'] = $parts['pass'];
		$result['database'] = trim($parts['path'], '/');

		$error = "Failed to parse MongoDB URI `$connection`";
		if (!$result['database']) {
			throw new ConfigException("$error. No database configured.");
		}

		return $result;
	}

}

?>