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
		'entity' => '\lithium\data\entity\Document',
		'set' => '\lithium\data\collection\DocumentSet',
		'relationship' => '\lithium\data\model\Relationship'
	);

	/**
	 * Map of typical SQL-like operators to their MongoDB equivalents.
	 *
	 * @var array Keys are SQL-like operators, value is the MongoDB equivalent.
	 */
	protected $_operators = array(
		'<' => '$lt',
		'>' => '$gt',
		'<=' =>  '$lte',
		'>=' => '$gte',
		'!=' => array('single' => '$ne', 'multiple' => '$nin'),
		'<>' => array('single' => '$ne', 'multiple' => '$nin')
	);

	/**
	 * A closure or anonymous function which receives an instance of this class, a collection name
	 * and associated meta information, and returns an array defining the schema for that model,
	 * where the keys are field names, and the values are arrays defining the type information for
	 * the field. At a minimum, type arrays must contain a `'type'` key.
	 *
	 * @var closure
	 */
	protected $_schema = null;

	/**
	 * List of configuration keys which will be automatically assigned to their corresponding
	 * protected class properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('schema');

	/**
	 * Instantiates the MongoDB adapter with the default connection information.
	 *
	 * @see lithium\data\Connections::add()
	 * @see lithium\data\source\MongoDb::$_schema
	 * @param array $config All information required to connect to the database, including:
	 *        - `'database'` _string_: The name of the database to connect to. Defaults to `'app'`.
	 *        - `'host'` _string_: The IP or machine name where Mongo is running, followed by a
	 *           colon, and the port number. Defaults to `'localhost:27017'`.
	 *        - `'persistent'` _boolean_: If a persistent connection (if available) should be made.
	 *            Defaults to `true`.
	 *        - `'port'`_mixed_: The port number Mongo is listening on. The default is '27017'.
	 *        - `'timeout'` _integer_: The number of milliseconds a connection attempt will wait
	 *          before timing out and throwing an exception. Defaults to `100`.
	 *        - `'schema'` _closure_: A closure or anonymous function which returns the schema
	 *          information for a model class. See the `$_schema` property for more information.
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
			'host'       => 'localhost:27107',
			'database'   => 'app',
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
		return array('meta' => array('key' => '_id'), 'classes' => array(
			'entity' => $this->_classes['entity'],
			'set' => $this->_classes['set'],
		));
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

			$data = $self->invokeMethod('_toMongoId', array($query->data()));
			$params = $query->export($self);
			$gridCol = "{$_config['gridPrefix']}.files";

			if ($query->source() == $gridCol && isset($params['data']['file'])) {
				$result = array('ok' => true);
				$data['_id'] = $self->invokeMethod('_saveFile', array($params));
			} else {
				$result = $self->connection->{$params['source']}->insert($data, true);
			}

			if (isset($result['ok']) && (boolean) $result['ok'] === true) {
				$query->entity()->update($data['_id']);
				return true;
			}
			return false;
		});
	}

	protected function _saveFile($params) {
		$uploadKeys = array('name', 'type', 'tmp_name', 'error', 'size');
		$data = $this->_toMongoId($params['data']);
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
		return $this->connection->getGridFS()->{$method}($file, $data);
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
		$defaults = array('return' => 'resource', 'model' => null);
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
				return $self->item($options['model'], $config['data'], $config);
			}
			$collection = $self->connection->{$source};

			if ($source == "{$_config['gridPrefix']}.files") {
				$collection = $self->connection->getGridFS();
			}
			$result = $collection->find($args['conditions'], $args['fields']);

			if ($query->calculate()) {
				return $result;
			}
			$result = $result->sort($args['order'])->limit($args['limit'])->skip($args['offset']);
			$config = compact('result', 'query') + array('class' => 'set');
			return $self->item($options['model'], array(), $config);
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

			$update = $self->invokeMethod('_toMongoId', array($args['data']));
			$update = ($options['atomic']) ? array('$set' => $update) : $update;

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
			$conditions = $self->invokeMethod('_toMongoId', array($params['conditions']));
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
	 * Allows for iteration over result sets.
	 *
	 * @param string $type One of 'next' or 'close'.
	 * @param object $resource The resource to act upon.
	 * @param object $context
	 * @return mixed If `$type` is `next` and the resource has a `next` item, that item is
	 *         returned. Null otherwise.
	 */
	public function result($type, $resource, $context) {
		if (!is_object($resource)) {
			return null;
		}

		switch ($type) {
			case 'next':
				$result = $resource->hasNext() ? $resource->getNext() : null;

				if ($result instanceof MongoGridFSFile) {
					$result = array('file' => $result) + $result->file;
				}
			break;
			case 'close':
				unset($resource);
				$result = null;
			break;
			default:
				$result = parent::result($type, $resource, $context);
			break;
		}
		return $result;
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
	 * Map incoming conditions with their corresponding MongoDB-native operators.
	 *
	 * @param array $conditions Array of conditions
	 * @param object $context Context with which this method was called; currently
	 *        inspects the return value of `$context->type()`.
	 * @return array Transformed conditions
	 */
	public function conditions($conditions, $context) {
		switch (true) {
			case !$conditions:
				return array();
			case $conditions instanceof MongoCode:
				return array('$where' => $conditions);
			case is_string($conditions):
				return array('$where' => new MongoCode($conditions));
			case is_callable($context) && $context->type() == 'create':
				return $this->_toMongoId($conditions);
		}
		$conditions = $this->_toMongoId($conditions);

		foreach ($conditions as $key => $value) {
			if ($key[0] === '$') {
				continue;
			}
			if (is_array($value) && (isset($this->_operators[key($value)]))) {
				list($type, $data) = each($value);

				if (is_callable($this->_operators[$type])) {
					$handler = $this->_operators[$type];
					$conditions[$key] = $handler($key, $data);
					continue;
				}
				if (is_array($this->_operators[$type])) {
					$format = (is_array($data)) ? 'multiple' : 'single';
					$operator = $this->_operators[$type][$format];
				} else {
					$operator = $this->_operators[$type];
				}
				$conditions[$key] = array($operator => $data);
				continue;
			}
			if (is_array($value) && strpos(key($value), '$') !== 0) {
				$conditions[$key] = array('$in' => $value);
			}
		}
		return $conditions;
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

	/**
	 * Returns a newly-created `Document` object, bound to a model and populated with default data
	 * and options.
	 *
	 * @param string $model A fully-namespaced class name representing the model class to which the
	 *               `Document` object will be bound.
	 * @param array $data The default data with which the new `Document` should be populated.
	 * @param array $options Any additional options to pass to the `Record`'s constructor.
	 * @return object Returns a new, un-saved `Document` object bound to the model class specified
	 *         in `$model`.
	 */
	public function item($model, array $data = array(), array $options = array()) {
		return parent::item($model, $data, array('handle' => $this) + $options);
	}

	/**
	 * Adds a proper MongoId to the passed `$data` if an appropriate `_id` field with a
	 * 24-character alphanumeric identifier exists.
	 *
	 * @param array|object $data The passed data. If `$data` is an object, no MongoId will
	 *                     be generated and the original object will be returned.
	 * @return array|object The passed `$data` with the `_id` parameter transformed into a
	 *         `MongoId` object, or the original data if `_id` is not set, or if `_id` is already
	 *         an object.
	 */
	protected function _toMongoId($data) {
		if (isset($data['_id']) && !is_object($data['_id'])) {
			if (preg_match('/^[0-9a-f]{24}$/', (string) $data['_id'])) {
				$data['_id'] = new MongoId($data['_id']);
			}
		}
		return $data;
	}

	protected function _checkConnection() {
		if (!$this->_isConnected && !$this->connect()) {
			throw new NetworkException("Could not connect to the database.");
		}
	}
}

?>