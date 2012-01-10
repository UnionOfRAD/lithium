<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use Mongo;
use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use MongoBinData;
use lithium\util\Inflector;
use lithium\core\NetworkException;
use Exception;

/**
 * A data source adapter which allows you to connect to the MongoDB database engine. MongoDB is an
 * Open Source distributed document database which bridges the gap between key/value stores and
 * relational databases. To learn more about MongoDB, see here:
 * [http://www.mongodb.org/](http://www.mongodb.org/).
 *
 * Rather than operating on records and record sets, queries against MongoDB will return nested sets
 * of `Document` objects. A `Document`'s fields can contain both simple and complex data types
 * (i.e. arrays) including other `Document` objects.
 *
 * After installing MongoDB, you can connect to it as follows:
 * {{{
 * // config/bootstrap/connections.php:
 * Connections::add('default', array('type' => 'MongoDb', 'database' => 'myDb'));
 * }}}
 *
 * By default, it will attempt to connect to a Mongo instance running on `localhost` on port
 * 27017. See `__construct()` for details on the accepted configuration settings.
 *
 * @see lithium\data\entity\Document
 * @see lithium\data\Connections::add()
 * @see lithium\data\source\MongoDb::__construct()
 */
class MongoDb extends \lithium\data\Source {

	/**
	 * The Mongo class instance.
	 *
	 * @var object
	 */
	public $server = null;

	/**
	 * The MongoDB object instance.
	 *
	 * @var object
	 */
	public $connection = null;

	/**
	 * Classes used by this class.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'entity'   => 'lithium\data\entity\Document',
		'array'    => 'lithium\data\collection\DocumentArray',
		'set'      => 'lithium\data\collection\DocumentSet',
		'result'   => 'lithium\data\source\mongo_db\Result',
		'exporter' => 'lithium\data\source\mongo_db\Exporter',
		'relationship' => 'lithium\data\model\Relationship'
	);

	/**
	 * Map of typical SQL-like operators to their MongoDB equivalents.
	 *
	 * @var array Keys are SQL-like operators, value is the MongoDB equivalent.
	 */
	protected $_operators = array(
		'<'   => '$lt',
		'>'   => '$gt',
		'<='  =>  '$lte',
		'>='  => '$gte',
		'!='  => array('single' => '$ne', 'multiple' => '$nin'),
		'<>'  => array('single' => '$ne', 'multiple' => '$nin'),
		'or'  => '$or',
		'||'  => '$or',
		'not' => '$not',
		'!'   =>  '$not'
	);

	/**
	 * A closure or anonymous function which receives an instance of this class, a collection name
	 * and associated meta information, and returns an array defining the schema for an associated
	 * model, where the keys are field names, and the values are arrays defining the type
	 * information for each field. At a minimum, type arrays must contain a `'type'` key. For more
	 * information on schema definitions, and an example schema callback implementation, see the
	 * `$_schema` property of the `Model` class.
	 *
	 * @see lithium\data\Model::$_schema
	 * @var Closure
	 */
	protected $_schema = null;

	/**
	 * An array of closures that handle casting values to specific types.
	 *
	 * @var array
	 */
	protected $_handlers = array();

	/**
	 * List of configuration keys which will be automatically assigned to their corresponding
	 * protected class properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('schema', 'handlers', 'classes' => 'merge');

	/**
	 * Instantiates the MongoDB adapter with the default connection information.
	 *
	 * @see lithium\data\Connections::add()
	 * @see lithium\data\source\MongoDb::$_schema
	 * @link http://php.net/manual/en/mongo.construct.php PHP Manual: Mongo::__construct()
	 * @param array $config All information required to connect to the database, including:
	 *        - `'database'` _string_: The name of the database to connect to. Defaults to `null`.
	 *        - `'host'` _string_: The IP or machine name where Mongo is running, followed by a
	 *           colon, and the port number. Defaults to `'localhost:27017'`.
	 *        - `'persistent'` _mixed_: Determines a persistent connection to attach to. See the
	 *           `$options` parameter of
	 *            [`Mongo::__construct()`](http://www.php.net/manual/en/mongo.construct.php) for
	 *            more information. Defaults to `false`, meaning no persistent connection is made.
	 *        - `'timeout'` _integer_: The number of milliseconds a connection attempt will wait
	 *          before timing out and throwing an exception. Defaults to `100`.
	 *        - `'schema'` _closure_: A closure or anonymous function which returns the schema
	 *          information for a model class. See the `$_schema` property for more information.
	 *        - `'gridPrefix'` _string_: The default prefix for MongoDB's `chunks` and `files`
	 *          collections. Defaults to `'fs'`.
	 *        - `'replicaSet'` _boolean_: See the documentation for `Mongo::__construct()`. Defaults
	 *          to `false`.
	 *
	 * Typically, these parameters are set in `Connections::add()`, when adding the adapter to the
	 * list of active connections.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'persistent' => false,
			'login'      => null,
			'password'   => null,
			'host'       => Mongo::DEFAULT_HOST . ':' . Mongo::DEFAULT_PORT,
			'database'   => null,
			'timeout'    => 100,
			'replicaSet' => false,
			'schema'     => null,
			'gridPrefix' => 'fs'
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();

		$this->_operators += array(
			'like' => function($key, $value) { return new MongoRegex($value); }
		);

		$this->_handlers += array(
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				$v = is_numeric($v) ? intval($v) : strtotime($v);
				return (!$v || time() == $v) ? new MongoDate() : new MongoDate($v);
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code'    => function($v) { return new MongoCode($v); },
			'binary'  => function($v) { return new MongoBinData($v); }
		);
	}

	/**
	 * Ensures that the server connection is closed and resources are freed when the adapter
	 * instance is destroyed.
	 *
	 * @return void
	 */
	public function __destruct() {
		if ($this->_isConnected) {
			$this->disconnect();
		}
	}

	/**
	 * With no parameter, checks to see if the `mongo` extension is installed. With a parameter,
	 * queries for a specific supported feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *               `"arrays"`.
	 * @return boolean Returns `true` if the particular feature (or if MongoDB) support is enabled,
	 *         otherwise `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return extension_loaded('mongo');
		}
		$features = array(
			'arrays' => true,
			'transactions' => false,
			'booleans' => true,
			'relationships' => true
		);
		return isset($features[$feature]) ? $features[$feature] : null;
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
		return array(
			'meta' => array('key' => '_id', 'locked' => false),
			'schema' => array()
		);
	}

	/**
	 * Connects to the Mongo server. Matches up parameters from the constructor to create a Mongo
	 * database connection.
	 *
	 * @see lithium\data\source\MongoDb::__construct()
	 * @link http://php.net/manual/en/mongo.construct.php PHP Manual: Mongo::__construct()
	 * @return boolean Returns `true` the connection attempt was successful, otherwise `false`.
	 */
	public function connect() {
		$cfg = $this->_config;
		$this->_isConnected = false;

		$host = is_array($cfg['host']) ? join(',', $cfg['host']) : $cfg['host'];
		$login = $cfg['login'] ? "{$cfg['login']}:{$cfg['password']}@" : '';
		$connection = "mongodb://{$login}{$host}" . ($login ? "/{$cfg['database']}" : '');
		$options = array(
			'connect' => true, 'timeout' => $cfg['timeout'], 'replicaSet' => $cfg['replicaSet']
		);

		try {
			if ($persist = $cfg['persistent']) {
				$options['persist'] = $persist === true ? 'default' : $persist;
			}
			$this->server = new Mongo($connection, $options);

			if ($this->connection = $this->server->{$cfg['database']}) {
				$this->_isConnected = true;
			}
		} catch (Exception $e) {
			throw new NetworkException("Could not connect to the database.", 503, $e);
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnect from the Mongo server.
	 *
	 * Don't call the Mongo->close() method. The driver documentation states this should not
	 * be necessary since it auto disconnects when out of scope.
	 * With version 1.2.7, when using replica sets, close() can cause a segmentation fault.
	 *
	 * @return boolean True
	 */
	public function disconnect() {
		if ($this->server && $this->server->connected) {
			$this->_isConnected = false;
			unset($this->connection, $this->server);
		}
		return true;
	}

	/**
	 * Returns the list of collections in the currently-connected database.
	 *
	 * @param string $class The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of objects to which models can connect.
	 */
	public function sources($class = null) {
		$this->_checkConnection();
		$conn = $this->connection;
		return array_map(function($col) { return $col->getName(); }, $conn->listCollections());
	}

	/**
	 * Gets the column 'schema' for a given MongoDB collection. Only returns a schema if the
	 * `'schema'` configuration flag has been set in the constructor.
	 *
	 * @see lithium\data\source\MongoDb::$_schema
	 * @param mixed $entity Would normally specify a collection name.
	 * @param array $meta
	 * @return array Returns an associative array describing the given collection's schema.
	 */
	public function describe($entity, array $meta = array()) {
		if (!$schema = $this->_schema) {
			return array();
		}
		return $schema($this, $entity, $meta);
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
	 * {{{// Manually repairs a MongoDB instance
	 * Connections::get('mongo')->repairDB($db); // returns null
	 * }}}
	 *
	 * @param string $method The name of native method to call. See the link above for available
	 *        class methods.
	 * @param array $params A list of parameters to be passed to the native method.
	 * @return mixed The return value of the native method specified in `$method`.
	 */
	public function __call($method, $params) {
		if ((!$this->server) && !$this->connect()) {
			return null;
		}
		return call_user_func_array(array(&$this->server, $method), $params);
	}

	/**
	 * Normally used in cases where the query is a raw string (as opposed to a `Query` object),
	 * to database must determine the correct column names from the result resource. Not
	 * applicable to this data source.
	 *
	 * @param mixed $query
	 * @param resource $resource
	 * @param object $context
	 * @return array
	 */
	public function schema($query, $resource = null, $context = null) {
		return array();
	}

	/**
	 * Create new document
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function create($query, array $options = array()) {
		$defaults = array('safe' => false, 'fsync' => false);
		$options += $defaults;
		$this->_checkConnection();

		$params = compact('query', 'options');
		$_config = $this->_config;
		$_exp = $this->_classes['exporter'];

		return $this->_filter(__METHOD__, $params, function($self, $params) use ($_config, $_exp) {
			$query   = $params['query'];
			$options = $params['options'];

			$args    = $query->export($self, array('keys' => array('source', 'data')));
			$data    = $_exp::get('create', $args['data']);
			$source  = $args['source'];

			if ($source == "{$_config['gridPrefix']}.files" && isset($data['create']['file'])) {
				$result = array('ok' => true);
				$data['create']['_id'] = $self->invokeMethod('_saveFile', array($data['create']));
			} else {
				$result = $self->connection->{$source}->insert($data['create'], $options);
			}

			if ($result === true || isset($result['ok']) && (boolean) $result['ok'] === true) {
				if ($query->entity()) {
					$query->entity()->sync($data['create']['_id']);
				}
				return true;
			}
			return false;
		});
	}

	protected function _saveFile($data) {
		$uploadKeys = array('name', 'type', 'tmp_name', 'error', 'size');
		$grid = $this->connection->getGridFS();
		$file = null;
		$method = null;

		switch (true) {
			case  (is_array($data['file']) && array_keys($data['file']) == $uploadKeys):
				if (!$data['file']['error'] && is_uploaded_file($data['file']['tmp_name'])) {
					$method = 'storeFile';
					$file = $data['file']['tmp_name'];
					$data['filename'] = $data['file']['name'];
				}
			break;
			case (is_string($data['file']) && file_exists($data['file'])):
				$method = 'storeFile';
				$file = $data['file'];
			break;
			case $data['file']:
				$method = 'storeBytes';
				$file = $data['file'];
			break;
		}

		if (!$method || !$file) {
			return;
		}

		if (isset($data['_id'])) {
			$data += (array) get_object_vars($grid->get($data['_id']));
			$grid->delete($data['_id']);
		}
		unset($data['file']);
		return $grid->{$method}($file, $data);
	}

	/**
	 * Read from document
	 *
	 * @param string $query
	 * @param array $options
	 * @return object
	 * @filter
	 */
	public function read($query, array $options = array()) {
		$this->_checkConnection();
		$defaults = array('return' => 'resource');
		$options += $defaults;

		$params = compact('query', 'options');
		$_config = $this->_config;

		return $this->_filter(__METHOD__, $params, function($self, $params) use ($_config) {
			$query = $params['query'];
			$options = $params['options'];
			$args = $query->export($self);
			$source = $args['source'];

			if ($group = $args['group']) {
				$result = $self->invokeMethod('_group', array($group, $args, $options));
				$config = array('class' => 'set') + compact('query') + $result;
				return $self->item($query->model(), $config['data'], $config);
			}
			$collection = $self->connection->{$source};

			if ($source == "{$_config['gridPrefix']}.files") {
				$collection = $self->connection->getGridFS();
			}
			$result = $collection->find($args['conditions'], $args['fields']);

			if ($query->calculate()) {
				return $result;
			}
			$resource = $result->sort($args['order'])->limit($args['limit'])->skip($args['offset']);
			$result = $self->invokeMethod('_instance', array('result', compact('resource')));
			$config = compact('result', 'query') + array('class' => 'set');
			return $self->item($query->model(), array(), $config);
		});
	}

	protected function _group($group, $args, $options) {
		$conditions = $args['conditions'];
		$group += array('$reduce' => $args['reduce'], 'initial' => $args['initial']);
		$command = array('group' => $group + array('ns' => $args['source'], 'cond' => $conditions));

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
	public function update($query, array $options = array()) {
		$defaults = array('upsert' => false, 'multiple' => true, 'safe' => false, 'fsync' => false);
		$options += $defaults;
		$this->_checkConnection();

		$params = compact('query', 'options');
		$_config = $this->_config;
		$_exp = $this->_classes['exporter'];

		return $this->_filter(__METHOD__, $params, function($self, $params) use ($_config, $_exp) {
			$options = $params['options'];
			$query  = $params['query'];
			$args   = $query->export($self, array('keys' => array('conditions', 'source', 'data')));
			$source = $args['source'];
			$data   = $args['data'];

			if ($query->entity()) {
				$data = $_exp::get('update', $data);
			}

			if ($source == "{$_config['gridPrefix']}.files" && isset($data['update']['file'])) {
				$args['data']['_id'] = $self->invokeMethod('_saveFile', array($data['update']));
			}
			$update = $query->entity() ? $_exp::toCommand($data) : $data;

			if ($options['multiple'] && !preg_grep('/^\$/', array_keys($update))) {
				$update = array('$set' => $update);
			}
			if ($self->connection->{$source}->update($args['conditions'], $update, $options)) {
				$query->entity() ? $query->entity()->sync() : null;
				return true;
			}
			return false;
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
	public function delete($query, array $options = array()) {
		$this->_checkConnection();
		$defaults = array('justOne' => false, 'safe' => false, 'fsync' => false);
		$options = array_intersect_key($options + $defaults, $defaults);
		$_config = $this->_config;

		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) use ($_config) {
			$query = $params['query'];
			$options = $params['options'];
			$args = $query->export($self, array('keys' => array('source', 'conditions')));
			$source = $args['source'];

			if ($source == "{$_config['gridPrefix']}.files") {
				return $self->invokeMethod('_deleteFile', array($args['conditions']));
			}

			return $self->connection->{$args['source']}->remove($args['conditions'], $options);
		});
	}

	protected function _deleteFile($conditions, $options = array()) {
		$defaults = array('safe' => true);
		$options += $defaults;

		$grid = $this->connection->getGridFS();
		
		return $grid->remove($conditions, $options);
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
	public function calculation($type, $query, array $options = array()) {
		$query->calculate($type);

		switch ($type) {
			case 'count':
				return $this->read($query, $options)->count();
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
	public function relationship($class, $type, $name, array $config = array()) {
		$key = Inflector::camelize($type == 'belongsTo' ? $class::meta('name') : $name, false);

		$config += compact('name', 'type', 'key');
		$config['from'] = $class;
		$relationship = $this->_classes['relationship'];

		$defaultLinks = array(
			'hasOne' => $relationship::LINK_EMBEDDED,
			'hasMany' => $relationship::LINK_EMBEDDED,
			'belongsTo' => $relationship::LINK_CONTAINED
		);
		$config += array('link' => $defaultLinks[$type]);
		return new $relationship($config);
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
			return array('$keyf' => new MongoCode($group));
		}
		$group = (array) $group;

		foreach ($group as $i => $field) {
			if (is_int($i)) {
				$group[$field] = true;
				unset($group[$i]);
			}
		}
		return array('key' => $group);
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
		$schema = array();
		$model = null;

		if (!$conditions) {
			return array();
		}
		if ($code = $this->_isMongoCode($conditions)) {
			return $code;
		}
		if ($context) {
			$model = $context->model();
			$schema = $context->schema();
		}
		return $this->_conditions($conditions, $model, $schema, $context);
	}

	protected function _conditions($conditions, $model, $schema, $context) {
		$castOpts = compact('schema') + array('first' => true, 'arrays' => false);

		foreach ($conditions as $key => $value) {
			if ($key === '$or' || $key === 'or' || $key === '||') {
				foreach ($value as $i => $or) {
					$value[$i] = $this->_conditions($or, $model, $schema, $context);
				}
				unset($conditions[$key]);
				$conditions['$or'] = $value;
				continue;
			}
			if (is_object($value)) {
				continue;
			}
			if (!is_array($value)) {
				$conditions[$key] = $this->cast(null, array($key => $value), $castOpts);
				continue;
			}
			$current = key($value);
			$isOpArray = (isset($this->_operators[$current]) || $current[0] === '$');

			if (!$isOpArray) {
				$data = array($key => $value);
				$conditions[$key] = array('$in' => $this->cast($model, $data, $castOpts));
				continue;
			}
			$operations = array();

			foreach ($value as $op => $val) {
				if (is_object($result = $this->_operator($model, $key, $op, $val, $schema))) {
					$operations = $result;
					break;
				}
				$operations += $this->_operator($model, $key, $op, $val, $schema);
			}
			$conditions[$key] = $operations;
		}
		return $conditions;
	}

	protected function _isMongoCode($conditions) {
		if ($conditions instanceof MongoCode) {
			return array('$where' => $conditions);
		}
		if (is_string($conditions)) {
			return array('$where' => new MongoCode($conditions));
		}
	}

	protected function _operator($model, $key, $op, $value, $schema) {
		$castOpts = compact('schema') + array('first' => true, 'arrays' => false);

		switch (true) {
			case !isset($this->_operators[$op]):
				return array($op => $this->cast($model, array($key => $value), $castOpts));
			case is_callable($this->_operators[$op]):
				return $this->_operators[$op]($key, $value);
			case is_array($this->_operators[$op]):
				$format = (is_array($value)) ? 'multiple' : 'single';
				$operator = $this->_operators[$op][$format];
			break;
			default:
				$operator = $this->_operators[$op];
			break;
		}
		return array($operator => $value);
	}

	/**
	 * Return formatted identifiers for fields.
	 *
	 * MongoDB does nt require field identifer escaping; as a result,
	 * this method is not implemented.
	 *
	 * @param array $fields Fields to be parsed
	 * @param object $context
	 * @return array Parsed fields array
	 */
	public function fields($fields, $context) {
		return $fields ?: array();
	}

	/**
	 * Return formatted clause for limit.
	 *
	 * MongoDB does nt require limit identifer formatting; as a result,
	 * this method is not implemented.
	 *
	 * @param mixed $limit The `limit` clause to be formatted
	 * @param object $context
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
		switch (true) {
			case !$order:
				return array();
			case is_string($order):
				return array($order => 1);
			case is_array($order):
				foreach ($order as $key => $value) {
					if (!is_string($key)) {
						unset($order[$key]);
						$order[$value] = 1;
						continue;
					}
					if (is_string($value)) {
						$order[$key] = strtoupper($value) == 'ASC' ? 1 : -1;
					}
				}
			break;
		}
		return $order ?: array();
	}

	public function cast($entity, array $data, array $options = array()) {
		$defaults = array('schema' => null, 'first' => false);
		$options += $defaults;
		$model = null;
		$exists = false;

		if (!$data) {
			return $data;
		}

		if (is_string($entity)) {
			$model = $entity;
			$entity = null;
			$options['schema'] = $options['schema'] ?: $model::schema();
		} elseif ($entity) {
			$options['schema'] = $options['schema'] ?: $entity->schema();
			$model = $entity->model();

			if (is_a($entity, $this->_classes['entity'])) {
				$exists = $entity->exists();
			}
		}
		$schema = $options['schema'] ?: array('_id' => array('type' => 'id'));
		unset($options['schema']);

		$exporter = $this->_classes['exporter'];
		$options += compact('model', 'exists') + array('handlers' => $this->_handlers);
		return parent::cast($entity, $exporter::cast($data, $schema, $this, $options), $options);
	}

	protected function _checkConnection() {
		if (!$this->_isConnected && !$this->connect()) {
			throw new NetworkException("Could not connect to the database.");
		}
	}
}

?>