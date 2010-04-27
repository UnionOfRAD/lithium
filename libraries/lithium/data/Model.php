<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use \lithium\util\Set;
use \lithium\util\Inflector;
use \RuntimeException;
use \UnexpectedValueException;
use \BadMethodCallException;

/**
 * The `Model` class is the starting point for the domain logic of your application.
 * Models are tasked with providing meaning to otherwise raw and unprocessed data (e.g.
 * user profile).
 *
 * Models expose a consistent and unified API to interact with an underlying datasource (e.g.
 * MongoDB, MySQL) for operations such as querying, saving, updating and deleting data from the
 * persistent storage.
 *
 * Models allow you to interact with your data in two fundamentally different ways: querying, and
 * data mutation (saving/updating/deleting). All query-related operations may be done through the
 * static `find` method, along with some additional utility methods provided for convenience.
 *
 * Examples:
 * {{{
 * // Return all 'post' records
 * Post::find('all');
 * Post::all();
 *
 * // With conditions and a limit
 * Post::find('all', array('conditions' => array('published' => true), 'limit' => 10));
 * Post::all(array('conditions' => array('published' => true), 'limit' => 10));
 *
 * // Integer count of all 'post' records
 * Post::find('count');
 * Post::count(); //
 *
 * // With conditions
 * Post::find('count', array('conditions' => array('published' => true)));
 * Post::count(array('conditions' => array('published' => true)));
 *
 * }}}
 *
 * The actual objects returned from `find` calls will depend on the type of datasource in use.
 * MongoDB, for example, will return results as a `Document`, while MySQL will return results
 * as a `RecordSet`. Both of these classes extend a common `data\Collection` class, and provide
 * the necessary abstraction to make working with either type completely transparent.
 *
 * For data mutation (saving/updating/deleting), the `Model` class acts as a broker to the proper
 * objects. When creating a new record, for example, a call to `Post::create()` will return a
 * `data\model\Record` object, which can then be acted upon.
 *
 * Example:
 * {{{
 * $post = Post::create();
 * $post->author = 'Robert';
 * $post->title = 'Newest Post!';
 * $post->content = 'Lithium rocks. That is all.';
 *
 * $post->save();
 * }}}
 *
 * @see lithium\data\model\Record
 * @see lithium\data\collection\Document
 * @see lithium\data\collection\RecordSet
 * @see lithium\data\Connections
 *
 */
class Model extends \lithium\core\StaticObject {

	/**
	 * Criteria for data validation.
	 *
	 * Example usage:
	 * {{{
	 * public $validates = array(
	 *     'title' => 'please enter a title',
	 *     'email' => array(
	 *         array('notEmpty', 'message' => 'Email is empty.'),
	 *         array('email', 'message' => 'Email is not valid.'),
	 *     )
	 * );
	 * }}}
	 *
	 * @var array
	 */
	public $validates = array();

	/**
	 * Model hasOne relations.
	 * Not yet implemented.
	 *
	 * @var array
	 */
	public $hasOne = array();

	/**
	 * Model hasMany relations.
	 * Not yet implemented.
	 *
	 * @var array
	 */
	public $hasMany = array();

	/**
	 * Model belongsTo relations.
	 * Not yet implemented.
	 *
	 * @var array
	 */
	public $belongsTo = array();

	/**
	 * Stores model instances for internal use.
	 *
	 * While the `Model` public API does not require instantiation thanks to late static binding
	 * introduced in PHP 5.3, LSB does not apply to class attributes. In order to prevent you
	 * from needing to redeclare every single `Model` class attribute in subclasses, instances of
	 * the models are stored and used internally.
	 *
	 * @var array
	 */
	protected static $_instances = array();

	/**
	 * Stores the filters that are applied to the model instances stored in `Model::$_instances`.
	 *
	 * @var array
	 */
	protected $_instanceFilters = array();

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'connections' => '\lithium\data\Connections',
		'record'      => '\lithium\data\model\Record',
		'query'       => '\lithium\data\model\Query',
		'validator'   => '\lithium\util\Validator'
	);

	/**
	 * A list of the current relation types for this `Model`.
	 *
	 * @var array
	 */
	protected $_relations = array();

	/**
	 * List of relation types and the configuration fields that these relations
	 * require/accept.
	 *
	 * Valid relation types are:
	 *
	 * - `belongsTo`
	 * - `hasOne`
	 * - `hasMany`
	 *
	 * @var array
	 */
	protected $_relationTypes = array(
		'belongsTo' => array('class', 'key', 'conditions', 'fields'),
		'hasOne'    => array('class', 'key', 'conditions', 'fields', 'dependent'),
		'hasMany'   => array(
			'class', 'key', 'conditions', 'fields', 'order', 'limit',
			'dependent', 'exclusive', 'finder', 'counter'
		)
	);

	/**
	 * Specifies all meta-information for this model class, including the name of the data source it
	 * connects to, how it interacts with that class, and how its data structure is defined.
	 *
	 * - `connection`: The name of the connection (as defined in `Connections::add()`) to which the
	 *   model should bind
	 * - `key`: The primary key or identifier key for records / documents this model produces,
	 *   i.e. `'id'` or `array('_id', '_rev')`. Defaults to `'id'`.
	 * - `name`: The canonical name of this model. Defaults to the class name.
	 * - `source`: The name of the database table or document collection to bind to. Defaults to the
	 *   lower-cased and underscored name of the class, i.e. `class UserProfile` maps to
	 *   `'user_profiles'`.
	 * - `title`: The field or key used as the title for each record. Defaults to `'title'` or
	 *   `'name'`, if those fields are available.
	 *
	 * @var array
	 * @see lithium\data\Connections::add()
	 */
	protected $_meta = array(
		'key' => 'id',
		'name' => null,
		'title' => null,
		'class' => null,
		'source' => null,
		'connection' => 'default',
		'initialized' => false
	);

	/**
	 * Stores the data schema.
	 *
	 * The schema is lazy-loaded by the first call to `Model::schema()`, unless it has been
	 * manually defined in the `Model` subclass.
	 *
	 * For schemaless persistent storage (e.g. MongoDB), this is never populated
	 * automatically - if you desire a fixed schema to interact with in those cases, you will
	 * be required to define it yourself.
	 *
	 * Example:
	 * {{{
	 * protected $_schema = array(
	 *     'name' => array('default' => 'Moe', 'type' => 'string', 'null' => false),
	 *     'sign' => array('default' => 'bar', 'type' => 'string', 'null' => false),
	 *     'age'  => array('default' => 0, 'type' => 'number', 'null' => false)
	 * );
	 * }}}
	 *
	 * @var array
	 */
	protected $_schema = array();

	/**
	 * Default query parameters.
	 *
	 * - `conditions`: The conditional query elements, e.g.
	 *                 `'conditions' => array('published' => true)`
	 * - `fields`: The fields that should be retrieved. When set to `null`, defaults to
	 *             all fields.
	 * - `order`: The order in which the data will be returned, e.g. `'order' => 'ASC'`.
	 * - `limit`: The maximum number of records to return.
	 * - `page`: For pagination of data.
	 *
	 * @var array
	 */
	protected $_query = array(
		'conditions' => null,
		'fields' => null,
		'order' => null,
		'limit' => null,
		'page' => null
	);

	/**
	 * Custom find query properties, indexed by name.
	 *
	 * @var array
	 */
	protected $_finders = array();

	/**
	 * List of base model classes. Any classes which are declared to be base model classes (i.e.
	 * extended but not directly interacted with) must be present in this list. Models can declare
	 * themselves as base models using the following code:
	 * {{{
	 * public function __init() {
	 * 	static::_isBase(__CLASS__, true);
	 * 	parent::__init();
	 * }
	 * }}}
	 *
	 * @var array
	 */
	protected static $_baseClasses = array(__CLASS__ => true);

	/**
	 * Sets default connection options and connects default finders.
	 *
	 * @param array $options
	 * @return void
	 * @todo Merge in inherited config from AppModel and other parent classes.
	 */
	public static function __init() {
		static::config();
	}

	/**
	 * Configures the model for use. This method is called by `Model::__init()`.
	 *
	 * This method will set the `Model::$_classes`, `Model::$_meta`, `Model::$_finders` class
	 * attributes, as well as obtain a handle to the configured persistent storage connection.
	 *
	 * @param array $options Possible options are:
	 * - `classes`: Dynamic class dependencies.
	 * - `meta`: Meta-information for this model, such as the connection.
	 * - `finders`: Custom finders for this model.
	 * @return void
	 */
	public static function config(array $options = array()) {
		if (static::_isBase($class = get_called_class())) {
			return;
		}
		$name = static::_name();
		$self = static::_instance();
		$defaults = array('classes' => array(), 'meta' => array(), 'finders' => array());

		$meta    = $options + $self->_meta;
		$classes = $self->_classes;
		$schema  = array();
		$config  = array();

		foreach (static::_parents() as $parent) {
			$base = get_class_vars($parent);

			foreach (array('meta', 'schema', 'classes') as $key) {
				if (isset($base["_{$key}"])) {
					${$key} += $base["_{$key}"];
				}
			}
			if ($class == __CLASS__) {
				break;
			}
		}

		if ($meta['connection']) {
			$conn = $classes['connections']::get($meta['connection']);
			$config = ($conn) ? $conn->configureClass($class) : array();
		}
		$config += $defaults;

		$self->_classes = ($config['classes'] + $classes);
		$self->_meta = (compact('class', 'name') + $config['meta'] + $meta);
		$self->_meta['initialized'] = false;
		$self->_schema += $schema;

		$self->_finders += $config['finders'] + $self->_findFilters();
		static::_instance()->_relations = static::_relations();
	}

	/**
	 * Exports an array of custom finders which use the filter system to wrap around `find()`.
	 *
	 * @return void
	 */
	protected static function _findFilters() {
		$self = static::_instance();
		$query =& $self->_query;
		$classes = $self->_classes;

		return array(
			'first' => function($self, $params, $chain) {
				$params['options']['limit'] = 1;
				$data = $chain->next($self, $params, $chain);
				return is_object($data) ? $data->rewind() : $data;
			},
			'list' => function($self, $params, $chain) {
				$result = array();
				$meta = $self::meta();

				array_map(
					function($record) use (&$result, $meta) {
						$result[$record->{$meta['key']}] = $record->{$meta['title']};
					},
					$chain->next($self, $params, $chain)
				);
				return $result;
			},
			'count' => function($self, $params, $chain) {
				$model = $self;
				$type = $params['type'];
				$options = array_filter($params['options']);

				$classes = $options['classes'];
				unset($options['classes']);

				if (!isset($options['conditions']) && $options) {
					$options = array('conditions' => $options) + compact('classes', 'model');
				}

				$query = new $classes['query'](array('type' => 'read') + $options);
				return $self::invokeMethod('_connection')->calculation('count', $query, $options);
			}
		);
	}

	/**
	 * Allows the use of syntactic-sugar like `Model::all()` instead of `Model::find('all')`.
	 *
	 * @see lithium\data\Model::find()
	 * @see http://php.net/manual/en/language.oop5.overloading.php
	 *
	 * @throws BadMethodCallException On unhandled call, will throw an exception.
	 * @param string $method Method name caught by `__callStatic`.
	 * @param array $params Arguments given to the above `$method` call.
	 * @return mixed Results of dispatched `Model::find()` call.
	 */
	public static function __callStatic($method, $params) {
		$self = static::_instance();

		if ($method == 'all' || isset($self->_finders[$method])) {
			if (isset($params[0]) && (is_string($params[0]) || is_int($params[0]))) {
				$params[0] = array('conditions' => array($self->_meta['key'] => $params[0]));
			}
			return $self::find($method, $params ? $params[0] : array());
		}
		$pattern = '/^findBy(?P<field>\w+)$|^find(?P<type>\w+)By(?P<fields>\w+)$/';

		if (preg_match($pattern, $method, $m)) {
			$field = Inflector::underscore($m['field'] ? $m['field'] : $m['fields']);
			$type = isset($m['type']) ? $m['type'] : 'first';
			$type[0] = strtolower($type[0]);

			$conditions = array($field => array_shift($params));
			return $self::find($type, compact('conditions') + $params);
		}

		$message = "Method %s not defined or handled in class %s";
		throw new BadMethodCallException(sprintf($message, $method, get_class($self)));
	}

	/**
	 * The `find` method allows you to retrieve data from the connected data source.
	 *
	 * Examples:
	 * {{{
	 * Model::find('all'); // returns all records
	 * Model::find('count'); // returns a count of all records
	 *
	 * // The first ten records that have 'author' set to 'Lithium'
	 * Model::find('all',
	 *     'conditions' => array('author' => "Lithium"),
	 *     'limit' => 10
	 * );
	 * }}}
	 *
	 * @param string $type The find type, which is looked up in `Model::$_finders`. By default it
	 *        accepts `all`, `first`, `list` and `count`,
	 * @param string $options Options for the query. By default, accepts:
	 *        - `conditions`: The conditional query elements, e.g.
	 *                 `'conditions' => array('published' => true)`
	 *        - `fields`: The fields that should be retrieved. When set to `null`, defaults to
	 *             all fields.
	 *        - `order`: The order in which the data will be returned, e.g. `'order' => 'ASC'`.
	 *        - `limit`: The maximum number of records to return.
	 *        - `page`: For pagination of data.
	 * @return void
	 * @filter This method can be filtered.
	 */
	public static function find($type, array $options = array()) {
		$self = static::_instance();
		$classes = $self->_classes;

		$defaults = array(
			'conditions' => null, 'fields' => null, 'order' => null, 'limit' => null, 'page' => 1
		);

		if ($type != 'all' && !isset($self->_finders[$type])) {
			$options['conditions'] = array($self->_meta['key'] => $type);
			$type = 'first';
		}

		$options += ((array) $self->_query + (array) $defaults + compact('classes'));
		$meta = array('meta' => $self->_meta, 'name' => get_called_class());
		$params = compact('type', 'options');

		$filter = function($self, $params) use ($meta) {
			$options = $params['options'] + array('model' => $meta['name']);
			$query = $options['classes']['query'];

			$connection = $self::invokeMethod('_connection');
			return $connection->read(new $query(array('type' => 'read') + $options), $options);
		};
		$finder = isset($self->_finders[$type]) ? array($self->_finders[$type]) : array();
		return static::_filter(__FUNCTION__, $params, $filter, $finder);
	}

	/**
	 * Gets or sets a finder by name.  This can be an array of default query options,
	 * or a closure that accepts an array of query options, and a closure to execute.
	 *
	 * @param string $name The finder name, e.g. `first`.
	 * @param string $options If you are setting a finder, this is the finder definition.
	 * @return mixed Finder definition if querying, null otherwise.
	 */
	public static function finder($name, $options = null) {
		$self = static::_instance();

		if (empty($options)) {
			return isset($self->_finders[$name]) ? $self->_finders[$name] : null;
		}
		$self->_finders[$name] = $options;
	}

	/**
	 * Set/get method for `Model` metadata.
	 *
	 * @see lithium\data\Model::$_meta
	 * @param string $key Model metadata key.
	 * @param string $value Model metadata value.
	 * @return mixed Metadata value for a given key.
	 */
	public static function meta($key = null, $value = null) {
		$self = static::_instance();

		if (!empty($value)) {
			$self->_meta[$key] = $value;
		}
		if (is_array($key)) {
			$self->_meta = $key + $self->_meta;
		}
		if (!$self->_meta['initialized']) {
			$self->_meta['initialized'] = true;
			if ($self->_meta['source'] === null) {
				$self->_meta['source'] = Inflector::tableize($self->_meta['name']);
			}
			$titleKeys = array('title', 'name', $self->_meta['key']);
			$self->_meta['title'] = $self->_meta['title'] ?: static::hasField($titleKeys);
		}
		if (is_array($key) || empty($key) || !empty($value)) {
			return $self->_meta;
		}
		return isset($self->_meta[$key]) ? $self->_meta[$key] : null;
	}

	/**
	 * If no values supplied, returns the name of the `Model` key. If values 
	 * are supplied, returns the key value.
	 *
	 * @param array $values An array of values.
	 * @return mixed Key value.
	 */
	public static function key($values = array()) {
		$key = static::_instance()->_meta['key'];

		if (is_object($values) && method_exists($values, 'to')) {
			$values = $values->to('array');
		} elseif (is_object($values) && isset($values->{$key})) {
			return $values->{$key};
		}

		if (!$values) {
			return $key;
		}
		$key = (array) $key;
		$scope = array_combine($key, array_fill(0, count($key), null));
		return array_intersect_key($values, $scope);
	}

	/**
	 * Returns a list of models related to `Model`, or a list of models related
	 * to this model, but of a certain type.
	 * 
	 * @param string $name A type of model relation.
	 * @return array An array of relation types.
	 */
	public static function relations($name = null) {
		$self = static::_instance();

		if (empty($name)) {
			return array_keys($self->_relations);
		}

		if (isset($self->_relationTypes[$name])) {
			return array_keys(array_filter($self->_relations, function($i) use ($name) {
				return $i['type'] == $name;
			}));
		}
		return isset($self->_relations[$name]) ? $self->_relations[$name] : null;
	}

	/**
	 * Lazy-initialize the schema for this Model object, if it is not already manually set in the
	 * object. You can declare `protected $_schema = array(...)` to define the schema manually.
	 *
	 * @param string $field Optional. You may pass a field name to get schema information for just
	 *        one field. Otherwise, an array containing all fields is returned.
	 * @return array
	 */
	public static function schema($field = null) {
		$self = static::_instance();

		if (!$self->_schema) {
			$self->_schema = $self->_connection()->describe($self::meta('source'), $self->_meta);
		}
		if (is_string($field) && $field) {
			return isset($self->_schema[$field]) ? $self->_schema[$field] : null;
		}
		return $self->_schema;
	}

	/**
	 * Checks to see if a particular field exists in a model's schema. Can check a single field, or
	 * return the first field found in an array of multiple options.
	 *
	 * @param mixed $field A single field (string) or list of fields (array) to check the existence
	 *        of.
	 * @return mixed If `$field` is a string, returns a boolean indicating whether or not that field
	 *         exists. If `$field` is an array, returns the first field found, or `false` if none of
	 *         the fields in the list are found.
	 */
	public static function hasField($field) {
		if (is_array($field)) {
			foreach ($field as $f) {
				if (static::hasField($f)) {
					return $f;
				}
			}
			return false;
		}
		$schema = static::schema();
		return ($schema && isset($schema[$field]));
	}

	/**
	 * Instantiates a new record object, initialized with any data passed in. For example:
	 * {{{
	 * $post = Post::create(array("title" => "New post"));
	 * echo $post->title; // echoes "New post"
	 * $post->save();
	 * }}}
	 *
	 * @param array $data Any data that this record should be populated with initially.
	 * @param array $options Options to be passed to item.
	 * @return object Returns a new, **un-saved** record object.
	 */
	public static function create(array $data = array(), array $options = array()) {
		$self = static::_instance();
		$classes = $self->_classes;
		$params = compact('data', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) use ($classes) {
			$data = $params['data'];
			$options = $params['options'];

			if ($schema = $self::schema()) {
				foreach ($schema as $field => $config) {
					if (!isset($data[$field]) && isset($config['default'])) {
						$data[$field] = $config['default'];
					}
				}
			}

			if ($self::meta('connection')) {
				return $self::invokeMethod('_connection')->item($self, $data, $options);
			}
			return new $classes['record'](array('model' => $self) + compact('data') + $options);
		});
	}

	/**
	 * An instance method (called on record and document objects) to create or update the record or
	 * document in the database that corresponds to `$record`. For example:
	 * {{{
	 * $post = Post::create();
	 * $post->title = "My post";
	 * $post->save(null, array('validate' => false));
	 * }}}
	 *
	 * @param object $record The record or document object to be saved in the database.
	 * @param array $data Any data that should be assigned to the record before it is saved.
	 * @param array $options Options:
	 *        - 'callbacks': If `false`, all callbacks will be disabled before executing. Defaults
	 *        to `true`.
	 *        - 'validate': If `false`, validation will be skipped, and the record will be
	 *        immediately saved. Defaults to `true`.
	 *        - 'whitelist': An array of fields that are allowed to be saved to this record.
	 *
	 * @return boolean Returns `true` on a successful save operation, `false` on failure.
	 */
	public function save($record, $data = null, array $options = array()) {
		$self = static::_instance();
		$classes = $self->_classes;
		$meta = array('model' => get_called_class()) + $self->_meta;

		$defaults = array('validate' => true, 'whitelist' => null, 'callbacks' => true);
		$options += $defaults + compact('classes');
		$params = compact('record', 'data', 'options');

		$filter = function($self, $params) use ($meta) {
			$record = $params['record'];
			$options = $params['options'];

			if ($params['data']) {
				$record->set($params['data']);
			}

			if ($options['validate'] && !$record->validates()) {
				return false;
			}

			$type = $record->exists() ? 'update' : 'create';
			$queryOptions = compact('type') + $options + $meta + compact('record');
			$query = new $options['classes']['query']($queryOptions);
			return $self::invokeMethod('_connection')->{$type}($query, $options);
		};

		if (!$options['callbacks']) {
			return $filter($record, $options);
		}
		return static::_filter(__FUNCTION__, $params, $filter);
	}

	/**
	 * Indicates whether the `Model`'s current data validates, given the 
	 * current rules setup.
	 *
	 * @param string $record Model record to validate.
	 * @param array $options Options.
	 * @return boolean Success.
	 */
	public function validates($record, array $options = array()) {
		$self = static::_instance();
		$validator = $self->_classes['validator'];
		$params = compact('record', 'options');

		$filter = function($parent, $params) use (&$self, $validator) {
			extract($params);

			if ($errors = $validator::check($record->data(), $self->validates, $options)) {
				$record->errors($errors);
			}
			return empty($errors);
		};
		return static::_filter(__FUNCTION__, $params, $filter);
	}

	/**
	 * Deletes the data associated with the current `Model`.
	 *
	 * @param string $record Record to delete.
	 * @param array $options Options.
	 * @return boolean Success.
	 */
	public function delete($record, array $options = array()) {
		$self = static::_instance();
		$query = $self->_classes['query'];
		$params = compact('record', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) use ($query) {
			$type = 'delete';
			$record = $params['record'];
			$options = $params['options'] + array('model' => $self) + compact('type', 'record');
			return $self::invokeMethod('_connection')->delete(new $query($options), $options);
		});
	}

	/**
	 * Gets just the class name portion of a fully-name-spaced class name, i.e.
	 * `app\models\Post::_name()` returns `'Post'`.
	 *
	 * @return string
	 */
	protected static function _name() {
		static $name;
		return $name ?: $name = join('', array_slice(explode("\\", get_called_class()), -1));
	}

	/**
	 * Gets the connection object to which this model is bound. Throws exceptions if a connection
	 * isn't set, or if the connection named isn't configured.
	 *
	 * @return object Returns an instance of `lithium\data\Source` from the connection configuration
	 *         to which this model is bound.
	 */
	protected static function &_connection() {
		$self = static::_instance();
		$connections = $self->_classes['connections'];
		$name = isset($self->_meta['connection']) ? $self->_meta['connection'] : null;

		if (!$name) {
			throw new UnexpectedValueException("Connection name not defined");
		}
		if ($conn = $connections::get($name)) {
			return $conn;
		}
		throw new RuntimeException("The data connection {$name} is not configured");
	}

	/**
	 * Wraps `StaticObject::applyFilter()` to account for object instances.
	 *
	 * @see lithium\core\StaticObject::applyFilter()
	 * @param string $method
	 * @param mixed $closure
	 */
	public static function applyFilter($method, $closure = null) {
		$instance = static::_instance();
		$methods = (array) $method;

		foreach ($methods as $method) {
			if (!isset($instance->_instanceFilters[$method])) {
				$instance->_instanceFilters[$method] = array();
			}
			$instance->_instanceFilters[$method][] = $closure;
		}
	}

	/**
	 * Wraps `StaticObject::_filter()` to account for object instances.
	 *
	 * @see lithium\core\StaticObject::_filter()
	 * @param string $method
	 * @param array $params
	 * @param mixed $callback
	 * @param array $filters Defaults to empty array.
	 * @return object
	 */
	protected static function _filter($method, $params, $callback, $filters = array()) {
		if (!strpos($method, '::')) {
			$method = get_called_class() . '::' . $method;
		}
		list($class, $method) = explode('::', $method, 2);
		$instance = static::_instance();

		if (isset($instance->_instanceFilters[$method])) {
			$filters = array_merge($instance->_instanceFilters[$method], $filters);
		}
		return parent::_filter($method, $params, $callback, $filters);
	}

	protected static function &_instance() {
		$class = get_called_class();

		if (!isset(static::$_instances[$class])) {
			static::$_instances[$class] = new $class();
		}
		return static::$_instances[$class];
	}

	/**
	 * Iterates through relationship types to construct relation map.
	 *
	 * @return void
	 * @todo See if this can be rewritten to be lazy.
	 */
	protected static function _relations() {
		$relations = array();
		$self = static::_instance();

		if (!$self->_meta['connection']) {
			return array();
		}

		$class = get_called_class();
		$connection = $self->_connection();

		foreach ($self->_relationTypes as $type => $keys) {
			foreach (Set::normalize($self->{$type}) as $name => $config) {
				$config = $connection->relationship($class, $type, $name, (array) $config);
				$relations[$name] = $config + compact('type');
			}
		}
		return $relations;
	}

	/**
	 * Helper function for setting/getting base class settings.
	 *
	 * @param string $class Classname.
	 * @param boolean $set If `true`, then the `$class` will be set.
	 * @return boolean Success.
	 */
	protected static function _isBase($class = null, $set = false) {
		if ($set) {
			static::$_baseClasses[$class] = true;
		}
		return isset(static::$_baseClasses[$class]);
	}
}

?>