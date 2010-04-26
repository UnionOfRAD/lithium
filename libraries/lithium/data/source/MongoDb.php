<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use \Mongo;
use \MongoId;
use \MongoCode;
use \MongoDBRef;
use \Exception;
use \lithium\util\Inflector;

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
 * {{{//app/config/connections.php:
 * Connections::add('default', array('type' => 'MongoDb', 'database' => 'myDb'));}}}
 *
 * By default, it will attempt to connect to a Mongo instance running on `localhost` on port
 * 27017. See `__construct()` for details on the accepted configuration settings.
 *
 * @see lithium\data\collection\Document
 * @see lithium\data\Connections::add()
 * @see lithium\data\source\MongoDb::__construct()
 */
class MongoDb extends \lithium\data\Source {

	/**
	 * The Mongo class instance.
	 *
	 * @var object
	 */
	public $connection = null;

	/**
	 * The MongoDB object instance.
	 *
	 * @var object
	 */
	protected $_db = null;

	/**
	 * Classes used by this class.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'document' => '\lithium\data\collection\Document'
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
	 * Instantiates the MongoDB adapter with the default connection information.
	 *
	 * @see lithium\data\Connections::add()
	 * @param array $config All information required to connect to the database, including:
	 *        - `'database'`: The name of the database to connect to. Defaults to 'lithium'.
	 *        - `'host'`: The IP or machine name where Mongo is running. Defaults to 'localhost'.
	 *        - `'persistent'`: If a persistent connection (if available) should be made. Defaults
	 *            to true.
	 *        - `'port'`: The port number Mongo is listening on. The default is '27017'.
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
			'host'       => 'localhost',
			'database'   => 'lithium',
			'port'       => '27017',
			'timeout'    => 100
		);
		parent::__construct($config + $defaults);
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
	 * Check for required PHP extension
	 *
	 * @return boolean
	 */
	public static function enabled() {
		return extension_loaded('mongo');
	}

	/**
	 * Configures a model class by overriding the default dependencies for `'recordSet'` and
	 * `'record'` , and sets the primary key to `'_id'`, in keeping with Mongo's conventions.
	 *
	 * @see lithium\data\Model::$_meta
	 * @see lithium\data\Model::$_classes
	 * @param string $class The fully-namespaced model class name to be configured.
	 * @return Returns an array containing keys `'classes'` and `'meta'`, which will be merged with
	 *         their respective properties in `Model`.
	 */
	public function configureClass($class) {
		return array('meta' => array('key' => '_id'), 'classes' => array(
			'record' => $this->_classes['document']
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

		$host = "{$config['host']}:{$config['port']}";
		$login = $config['login'] ? "{$config['login']}:{$config['password']}@" : '';
		$connection = "mongodb://{$login}{$host}" . ($login ? "/{$config['database']}" : '');

		try {
			$this->connection = new Mongo($connection, array(
				'connect' => true,
				'persist' => $config['persistent'],
				'timeout' => $config['timeout']
			));
			if ($this->_db = $this->connection->{$config['database']}) {
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
		if ($this->connection && $this->connection->connected) {
			try {
				$this->_isConnected = !$this->connection->close();
			} catch (Exception $e) {}
			unset($this->_db, $this->connection);
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
		return array_map(function($col) { return $col->getName(); }, $this->_db->listCollections());
	}

	/**
	 * Gets the column 'schema' for a given MongoDB collection. Not applicable to this data source.
	 *
	 * @param mixed $entity Would normally specify a table name.
	 * @param array $meta
	 * @return array Returns an associative array describing the given table's schema.
	 */
	public function describe($entity, $meta = array()) {
		return array();
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
		return call_user_func_array(array(&$this->connection, $method), $params);
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
		$params = compact('query', 'options');
		$conn =& $this->connection;
		$db =& $this->_db;

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, &$db) {
			$query = $params['query'];
			$options = $params['options'];

			$data = $query->data();
			$params = $query->export($self);
			$result = $db->{$params['table']}->insert($data, true);

			if (isset($result['ok']) && $result['ok'] === 1.0) {
				$id = $data['_id'];
				$query->record()->update(is_object($id) ? $id->__toString() : null);
				return true;
			}
			return false;
		});
	}

	/**
	 * Read from document
	 *
	 * @param string $query
	 * @param string $options
	 * @return object
	 */
	public function read($query, array $options = array()) {
		$defaults = array(
			'return' => 'resource',
			'model' => null
		);
		$options += $defaults;

		$db =& $this->_db;
		$conn =& $this->connection;
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, &$db) {
			$query = $params['query'];
			$options = $params['options'];
			$params = $query->export($self);

			$table = $params['table'];
			$conditions = $params['conditions'];

			if ($group = $params['group']) {
				$group += array(
					'$reduce' => $params['reduce'] ?: null, 'initial' => $params['initial'] ?: null
				);

				$stats = $db->command(array('group' => $group + array(
					'ns' => $table,
					'cond' => $conditions
				)));
				$data = isset($stats['retval']) ? $stats['retval'] : null;
				unset($stats['retval']);

				$params = array('document', $query, compact('data', 'stats') + array(
					'model' => $options['model']
				));
				return $self->invokeMethod('_result', $params);
			}
			$result = $db->{$table}->find($conditions, $params['fields']);

			if ($query->calculate()) {
				return $result;
			}

			$order = $params['order'];
			$limit = $params['limit'];
			$offset = $params['offset'];
			$result = $result->sort($order)->limit($limit)->skip($offset);
			$options = compact('result') + array('model' => $options['model']);

			return $self->invokeMethod('_result', array('document', $query, $options));
		});
	}

	/**
	 * Update document
	 *
	 * @param string $query
	 * @param string $options
	 * @return boolean
	 */
	public function update($query, array $options = array()) {
		$db =& $this->_db;
		$conn =& $this->connection;
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, &$db) {
			$query = $params['query'];
			$options = $params['options'];

			$params = $query->export($self);
			$data = $query->data();

			if ($db->{$params['table']}->update($params['conditions'], $data)) {
				$query->record()->update();
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
		$db =& $this->_db;
		$conn =& $this->connection;
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params) use (&$conn, &$db) {
			$query = $params['query'];
			$options = $params['options'];

			$params = $query->export($self);
			$params['conditions'] = $self->invokeMethod('_toMongoId', array($params['conditions']));
			return $db->{$params['table']}->remove($params['conditions']);
		});
	}

	/**
	 * Returns a newly-created `Document` object, bound to a model and populated with default data
	 * and options.
	 *
	 * @param string $model A fully-namespaced class name representing the model class to which the
	 *               `Document` object will be bound.
	 * @param array $data The default data with which the new `Document` should be populated.
	 * @param array $options Any additional options to pass to the `Document`'s constructor.
	 * @return object Returns a new, un-saved `Document` object bound to the model class specified
	 *         in `$model`.
	 */
	public function item($model, array $data = array(), array $options = array()) {
		$class = $this->_classes['document'];
		return new $class(compact('model', 'data') + $options);
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
	 * @param array $options
	 * @return array
	 */
	public function relationship($class, $type, $name, array $options = array()) {
		$key = Inflector::underscore($type == 'belongsTo' ? $name : $class::meta('name'));
		$defaults = compact('key') + array(
			'type' => $type,
			'class' => null,
			'fields' => true
		);
		$options += $defaults;

		if (!$options['class']) {
			$assoc = preg_replace("/\\w+$/", "", $class) . $name;
			$options['class'] = class_exists($assoc) ? $assoc : Libraries::locate('models', $assoc);
		}
		return $options + $defaults;
	}

	/**
	 * Creates a link between two `Document` objects.
	 *
	 * @param object $object
	 * @param object $related
	 * @param array $options
	 * @return boolean Returns `true` if MongoDB was able to create a link between the two
	 *         documents, otherwise `false`, if the link failed or if both `$object` and `$related`
	 *         are not top-level, pre-existing `Document` objects.
	 */
	public function link($object, $related, array $options = array()) {
		if (!$object->_id || !$related->_id) {
			return false;
		}
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
				return $resource->hasNext() ? $resource->getNext() : null;
			case 'close':
				unset($resource);
				return null;
			default:
				return parent::result($type, $resource, $context);
		}
	}

	/**
	 * Formats `group` clauses fro MongoDB.
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
		return $order ?: array();
	}

	/**
	 * Wrap query result with a Document instance (configurable by setting the proper `classes`
	 * configuration entry).
	 *
	 * @param string $type Type of result; defaults to `document`.
	 * @param mixed $query The query.
	 * @param array $config Optional Document configuration parameters, passed on construction.
	 * @return object Instance of `MongoDB::$_classes[$type]`, which defaults to `Document`.
	 */
	protected function _result($type, $query, array $config = array()) {
		$defaults = array('handle' => &$this, 'exists' => true);
		$class = $this->_classes[$type];
		return new $class(compact('query') + $config + $defaults);
	}

	/**
	 * Adds a proper MongoId to the passed `$data` if an appropriate `_id` field with a
	 * 24-character alphanumeric identifier exists.
	 *
	 * @param array|object $data The passed data. If `$data` is an object, no MongoId will
	 *        be generated and the original object will be returned.
	 * @return array|object The passed `$data` with the `_id` parameter transformed into a
	 *         MongoId object, or the original data if `_id` is not set, or if `_id` is already
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
}

?>