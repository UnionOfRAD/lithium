<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use Mongo;
use MongoId;
use MongoCode;
use MongoDate;
use MongoDBRef;
use MongoRegex;
use MongoGridFSFile;
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
 * {{{//app/config/bootstrap/connections.php:
 * Connections::add('default', array('type' => 'MongoDb', 'database' => 'myDb'));}}}
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
		'entity' => 'lithium\data\entity\Document',
		'array'  => 'lithium\data\collection\DocumentArray',
		'set'    => 'lithium\data\collection\DocumentSet',
		'result' => 'lithium\data\source\mongo_db\Result',
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
		'!'   =>  '$not',
	);

	/**
	 * A closure or anonymous function which receives an instance of this class, a collection name
	 * and associated meta information, and returns an array defining the schema for that model,
	 * where the keys are field names, and the values are arrays defining the type information for
	 * the field. At a minimum, type arrays must contain a `'type'` key.
	 *
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
	protected $_autoConfig = array('schema', 'handlers');

	/**
	 * Instantiates the MongoDB adapter with the default connection information.
	 *
	 * @see lithium\data\Connections::add()
	 * @see lithium\data\source\MongoDb::$_schema
	 * @param array $config All information required to connect to the database, including:
	 *        - `'database'` _string_: The name of the database to connect to. Defaults to `null`.
	 *        - `'host'` _string_: The IP or machine name where Mongo is running, followed by a
	 *           colon, and the port number. Defaults to `'localhost:27017'`.
	 *        - `'persistent'` _boolean_: If a persistent connection (if available) should be made.
	 *            Defaults to `true`.
	 *        - `'port'`_mixed_: The port number Mongo is listening on. The default is '27017'.
	 *        - `'timeout'` _integer_: The number of milliseconds a connection attempt will wait
	 *          before timing out and throwing an exception. Defaults to `100`.
	 *        - `'schema'` _closure_: A closure or anonymous function which returns the schema
	 *          information for a model class. See the `$_schema` property for more information.
	 *        - `'gridPrefix'` _string_: The default prefix for MongoDB's `chunks` and `files`
	 *          collections. Defaults to `'fs'`.
	 *
	 * Typically, these parameters are set in `Connections::add()`, when adding the adapter to the
	 * list of active connections.
	 * @return object The adapter instance.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'persistent' => true,
			'login'      => null,
			'password'   => null,
			'host'       => Mongo::DEFAULT_HOST . ':' . Mongo::DEFAULT_PORT,
			'database'   => null,
			'timeout'    => 100,
			'schema'     => null,
			'gridPrefix' => 'fs',
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
				return new MongoDate(is_numeric($v) ? intval($v) : strtotime($v));
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; }
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
			'relationships' => true,
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
	 * Connects to the Mongo server.
	 *
	 * @return boolean True if connected, false otherwise.
	 */
	public function connect() {
		$config = $this->_config;
		$this->_isConnected = false;

		$host = $config['host'];
		$login = $config['login'] ? "{$config['login']}:{$config['password']}@" : '';
		$connection = "mongodb://{$login}{$host}" . ($login ? "/{$config['database']}" : '');

		try {
			$this->server = new Mongo($connection, array(
				'connect' => true,
				'persist' => $config['persistent'],
				'timeout' => $config['timeout']
			));
			if ($this->connection = $this->server->{$config['database']}) {
				$this->_isConnected = true;
			}
		} catch (Exception $e) {}
		return $this->_isConnected;
	}

	/**
	 * Disconnect from the Mongo server.
	 *
	 * @return boolean True on successful disconnect, false otherwise.
	 */
	public function disconnect() {
		if ($this->server && $this->server->connected) {
			try {
				$this->_isConnected = !$this->server->close();
			} catch (Exception $e) {}
			unset($this->connection, $this->server);
			return !$this->_isConnected;
		}
		return true;
	}

	/**
	 * Returns the list of collections in the currently-connected database.
	 *
	 * @param string $class The fully-name-spaced class name of the model object making the request.
	 * @return array Returns an array of objects to which models can connect.
	 */
	public function entities($class = null) {
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
	 * @param string $options
	 * @return boolean
	 */
	public function create($query, array $options = array()) {
		$this->_checkConnection();
		$params = compact('query', 'options');
		$_config = $this->_config;

		return $this->_filter(__METHOD__, $params, function($self, $params) use ($_config) {
			$query = $params['query'];
			$options = $params['options'];
			$data = array();

			$params = $query->export($self);
			$gridCol = "{$_config['gridPrefix']}.files";

			if ($query->source() == $gridCol && isset($params['data']['file'])) {
				$result = array('ok' => true);
				$data['_id'] = $self->invokeMethod('_saveFile', array($params));
			} else {
				$result = $self->connection->{$params['source']}->insert($params['data'], true);
			}

			if (isset($result['ok']) && (boolean) $result['ok'] === true) {
				$params['data'] += $data;
				$query->entity()->update($params['data']['_id']);
				return true;
			}
			return false;
		});
	}

	protected function _saveFile($params) {
		$uploadKeys = array('name', 'type', 'tmp_name', 'error', 'size');
		$data = $params['data'];
		$grid = $this->connection->getGridFS();
		$file = null;
		$method = null;

		switch (true) {
			case  (is_array($data['file']) && array_keys($data['file']) == $uploadKeys):
				if (!$data['file']['error'] && is_uploaded_file($data['file']['tmp_name'])) {
					$method = 'storeFile';
					$file = $data['file']['tmp_name'];
					$data += array('filename' => $data['file']['name']);
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
	 * @param string $options
	 * @return object
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
			$self->connection->resetError();
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
	 */
	public function update($query, array $options = array()) {
		$this->_checkConnection();
		$defaults = array('atomic' => true);
		$options += $defaults;
		$params = compact('query', 'options');
		$_config = $this->_config;

		return $this->_filter(__METHOD__, $params, function($self, $params) use ($_config) {
			$query = $params['query'];
			$options = $params['options'];
			$args = $query->export($self, $options);
			$gridCol = "{$_config['gridPrefix']}.files";

			if ($args['source'] == $gridCol && isset($args['data']['file'])) {
				$args['data']['_id'] = $self->invokeMethod('_saveFile', array($args));
			}
			unset($args['data']['_id']);
			$update = ($options['atomic']) ? array('$set' => $args['data']) : $args['data'];

			if ($self->connection->{$args['source']}->update($args['conditions'], $update)) {
				$query->entity() ? $query->entity()->update() : null;
				return true;
			}
			return false;
		});
	}

	/**
	 * Delete document
	 *
	 * @param string $query
	 * @param string $options
	 * @return boolean
	 */
	public function delete($query, array $options = array()) {
		$this->_checkConnection();

		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];
			$options = $params['options'];

			$params = $query->export($self);
			$conditions = $params['conditions'];
			return $self->connection->{$params['source']}->remove($conditions);
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
		$keys = Inflector::camelize($type == 'belongsTo' ? $class::meta('name') : $name, false);

		$config += compact('name', 'type', 'keys');
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
				$conditions[$key] = $this->cast($model, array($key => $value), $castOpts);
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
			}
			$conditions[$key] = $result;
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

	public function cast($model, array $data, array $options = array()) {
		$defaults = array('schema' => null, 'first' => false, 'pathKey' => null, 'arrays' => true);
		$options += $defaults;

		if ($model && !$options['schema']) {
			$options['schema'] = $model::schema() ?: array('_id' => array('type' => 'id'));
		}
		$schema = $options['schema'];
		unset($options['schema']);

		$typeMap = array(
			'MongoId'   => 'id',
			'MongoDate' => 'date',
			'datetime'  => 'date',
			'timestamp' => 'date',
			'int'       => 'integer'
		);

		foreach ($data as $key => $value) {
			if (is_object($value)) {
				continue;
			}
			$path = $options['pathKey'] ? "{$options['pathKey']}.{$key}" : $key;
			$field = (isset($schema[$path]) ? $schema[$path] : array());
			$field += array('type' => null, 'array' => null);
			$type = isset($typeMap[$field['type']]) ? $typeMap[$field['type']] : $field['type'];
			$isObject = ($type == 'object');
			$isArray = (is_array($value) && $field['array'] !== false && !$isObject);

			if (isset($this->_handlers[$type])) {
				$handler = $this->_handlers[$type];
				$value = $isArray ? array_map($handler, $value) : $handler($value);
			}
			if (!$options['arrays']) {
				$data[$key] = $value;
				continue;
			}
			$pathKey = $path;

			if (is_array($value)) {
				$arrayType = !$isObject && (array_keys($value) === range(0, count($value) - 1));
				$opts = $arrayType ? array('class' => 'array') + $options : $options;
				$value = $this->item($model, $value, compact('pathKey') + $opts);
			} elseif ($field['array']) {
				$opts = array('class' => 'array') + $options;
				$value = $this->item($model, array($value), compact('pathKey') + $opts);
			}
			$data[$key] = $value;
		}
		return $options['first'] ? reset($data) : $data;
	}

	protected function _checkConnection() {
		if (!$this->_isConnected && !$this->connect()) {
			throw new NetworkException("Could not connect to the database.");
		}
	}
}

?>