<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use lithium\util\Set;
use lithium\util\Inflector;
use lithium\core\ConfigException;
use BadMethodCallException;

/**
 * The `Model` class is the starting point for the domain logic of your application.
 * Models are tasked with providing meaning to otherwise raw and unprocessed data (e.g.
 * user profile).
 *
 * Models expose a consistent and unified API to interact with an underlying datasource (e.g.
 * MongoDB, CouchDB, MySQL) for operations such as querying, saving, updating and deleting data
 * from the persistent storage.
 *
 * Models allow you to interact with your data in two fundamentally different ways: querying, and
 * data mutation (saving/updating/deleting). All query-related operations may be done through the
 * static `find()` method, along with some additional utility methods provided for convenience.
 *
 * Classes extending this one should, conventionally, be named as Singular, CamelCase and be
 * placed in the app/models directory. i.e. a posts model would be app/model/Post.php.
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
 * Post::count(); // This is equivalent to the above.
 *
 * // With conditions
 * Post::find('count', array('conditions' => array('published' => true)));
 * Post::count(array('published' => true));
 * }}}
 *
 * The actual objects returned from `find()` calls will depend on the type of datasource in use.
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
 * @see lithium\data\entity\Record
 * @see lithium\data\entity\Document
 * @see lithium\data\collection\RecordSet
 * @see lithium\data\collection\DocumentSet
 * @see lithium\data\Connections
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
	protected static $_classes = array(
		'connections' => 'lithium\data\Connections',
		'query'       => 'lithium\data\model\Query',
		'validator'   => 'lithium\util\Validator'
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
		'locked' => true,
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
	 * For schemaless persistent storage (e.g. MongoDB), this is never populated automatically - if
	 * you desire a fixed schema to interact with in those cases, you will be required to define it
	 * yourself.
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
	 * For MongoDB specifically, you can also implement a callback in your database connection
	 * configuration that fetches and returns the schema data, as in the following:
	 *
	 * {{{
	 * Connections::add('default', array(
	 * 	'type' => 'MongoDb',
	 * 	'host' => 'localhost',
	 * 	'database' => 'app_name',
	 * 	'schema' => function($db, $collection, $meta) {
	 * 		$result = $db->connection->schemas->findOne(compact('collection'));
	 * 		return $result ? $result['data'] : array();
	 * 	}
	 * ));
	 * }}}
	 *
	 * This example defines an optional MongoDB convention in which the schema for each individual
	 * collection is stored in a "schemas" collection, where each document contains the name of
	 * a collection, along with a `'data'` key, which contains the schema for that collection.
	 *
	 * @see lithium\data\source\MongoDb::$_schema
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
	 * public static function __init() {
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
	 * This method will set the `Model::$_schema`, `Model::$_meta`, `Model::$_finders` class
	 * attributes, as well as obtain a handle to the configured persistent storage connection.
	 *
	 * @param array $options Possible options are:
	 * - `meta`: Meta-information for this model, such as the connection.
	 * - `finders`: Custom finders for this model.
	 * @return void
	 */
	public static function config(array $options = array()) {
		if (static::_isBase($class = get_called_class())) {
			return;
		}
		$self = static::_object();
		$base = get_class_vars(__CLASS__);

		$meta    = array();
		$schema  = array();
		$source  = array();
		$classes = static::$_classes;

		foreach (static::_parents() as $parent) {
			$parentConfig = get_class_vars($parent);

			foreach (array('meta', 'schema', 'classes') as $key) {
				if (isset($parentConfig["_{$key}"])) {
					${$key} += $parentConfig["_{$key}"];
				}
			}
			if ($parent == __CLASS__) {
				break;
			}
		}
		$tmp = $options + $self->_meta + $meta;

		if ($tmp['connection']) {
			$conn = $classes['connections']::get($tmp['connection']);
			$source = ($conn) ? $conn->configureClass($class) : array();
		}
		$source += array('meta' => array(), 'finders' => array(), 'schema' => array());
		static::$_classes = $classes;
		$name = static::_name();

		$local = compact('class', 'name') + $options + array_diff($self->_meta, $base['_meta']);
		$self->_meta = ($local + $source['meta'] + $meta);
		$self->_meta['initialized'] = false;
		$self->_schema += $schema + $source['schema'];

		$self->_finders += $source['finders'] + $self->_findFilters();
		static::_relations();
	}

	/**
	 * Allows the use of syntactic-sugar like `Model::all()` instead of `Model::find('all')`.
	 *
	 * @see lithium\data\Model::find()
	 * @see lithium\data\Model::$_meta
	 * @link http://php.net/manual/en/language.oop5.overloading.php PHP Manual: Overloading
	 *
	 * @throws BadMethodCallException On unhandled call, will throw an exception.
	 * @param string $method Method name caught by `__callStatic`.
	 * @param array $params Arguments given to the above `$method` call.
	 * @return mixed Results of dispatched `Model::find()` call.
	 */
	public static function __callStatic($method, $params) {
		$self = static::_object();
		$isFinder = isset($self->_finders[$method]);

		if ($isFinder && count($params) === 2 && is_array($params[1])) {
			$params = array($params[1] + array($method => $params[0]));
		}

		if ($method == 'all' || $isFinder) {
			if ($params && is_scalar($params[0])) {
				$params[0] = array('conditions' => array($self->_meta['key'] => $params[0]));
			}
			return $self::find($method, $params ? $params[0] : array());
		}
		preg_match('/^findBy(?P<field>\w+)$|^find(?P<type>\w+)By(?P<fields>\w+)$/', $method, $args);

		if (!$args) {
			$message = "Method %s not defined or handled in class %s";
			throw new BadMethodCallException(sprintf($message, $method, get_class($self)));
		}
		$field = Inflector::underscore($args['field'] ? $args['field'] : $args['fields']);
		$type = isset($args['type']) ? $args['type'] : 'first';
		$type[0] = strtolower($type[0]);

		$conditions = array($field => array_shift($params));
		return $self::find($type, compact('conditions') + $params);
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
	 * Model::find('all', array(
	 *     'conditions' => array('author' => "Lithium"), 'limit' => 10
	 * ));
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
		$self = static::_object();
		$finder = array();

		$defaults = array(
			'conditions' => null, 'fields' => null, 'order' => null, 'limit' => null, 'page' => 1
		);

		if ($type === null) {
			return null;
		}

		if ($type != 'all' && is_scalar($type) && !isset($self->_finders[$type])) {
			$options['conditions'] = array($self->_meta['key'] => $type);
			$type = 'first';
		}

		$options += ((array) $self->_query + (array) $defaults);
		$meta = array('meta' => $self->_meta, 'name' => get_called_class());
		$params = compact('type', 'options');

		$filter = function($self, $params) use ($meta) {
			$options = $params['options'] + array('type' => 'read', 'model' => $meta['name']);
			$query = $self::invokeMethod('_instance', array('query', $options));
			return $self::connection()->read($query, $options);
		};
		if (is_string($type) && isset($self->_finders[$type])) {
			$finder = is_callable($self->_finders[$type]) ? array($self->_finders[$type]) : array();
		}
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
		$self = static::_object();

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
		$self = static::_object();

		if ($value) {
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
		$key = static::_object()->_meta['key'];

		if (is_object($values) && method_exists($values, 'to')) {
			$values = $values->to('array');
		} elseif (is_object($values) && is_string($key) && isset($values->{$key})) {
			return $values->{$key};
		}

		if (!$values) {
			return $key;
		}
		if (!is_array($values) && !is_array($key)) {
			return array($key => $values);
		}
		$key = (array) $key;
		return array_intersect_key($values, array_combine($key, $key));
	}

	/**
	 * Returns a list of models related to `Model`, or a list of models related
	 * to this model, but of a certain type.
	 *
	 * @param string $name A type of model relation.
	 * @return array An array of relation types.
	 */
	public static function relations($name = null) {
		$self = static::_object();

		if (!$name) {
			return $self->_relations;
		}
		if (isset($self->_relationTypes[$name])) {
			return array_keys(array_filter($self->_relations, function($i) use ($name) {
				return $i->data('type') == $name;
			}));
		}
		return isset($self->_relations[$name]) ? $self->_relations[$name] : null;
	}

	/**
	 * Creates a relationship binding between this model and another.
	 *
	 * @see lithium\data\model\Relationship
	 * @param string $type The type of relationship to create. Must be one of `'hasOne'`,
	 *               `'hasMany'` or `'belongsTo'`.
	 * @param string $name The name of the relationship. If this is also the name of the model,
	 *               the model must be in the same namespace as this model. Otherwise, the
	 *               fully-namespaced path to the model class must be specified in `$config`.
	 * @param array $config Any other configuration that should be specified in the relationship.
	 *              See the `Relationship` class for more information.
	 * @return object Returns an instance of the `Relationship` class that defines the connection.
	 */
	public static function bind($type, $name, array $config = array()) {
		$self = static::_object();

		if (!isset($self->_relationTypes[$type])) {
			throw new ConfigException("Invalid relationship type '{$type}' specified.");
		}
		$rel = static::connection()->relationship(get_called_class(), $type, $name, $config);
		return static::_object()->_relations[$name] = $rel;
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
		$self = static::_object();

		if (!$self->_schema) {
			$self->_schema = static::connection()->describe($self::meta('source'), $self->_meta);
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
	 * $success = $post->save();
	 * }}}
	 *
	 * @param array $data Any data that this record should be populated with initially.
	 * @param array $options Options to be passed to item.
	 * @return object Returns a new, **un-saved** record object.
	 */
	public static function create(array $data = array(), array $options = array()) {
		$self = static::_object();
		$params = compact('data', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$data = $params['data'];
			$options = $params['options'];

			if ($schema = $self::schema()) {
				foreach ($schema as $field => $config) {
					if (!isset($data[$field]) && isset($config['default'])) {
						$data[$field] = $config['default'];
					}
				}
			}
			return $self::connection()->item($self, $data, $options);
		});
	}

	/**
	 * An instance method (called on record and document objects) to create or update the record or
	 * document in the database that corresponds to `$entity`.
	 *
	 * For example:
	 * {{{
	 * $post = Post::create();
	 * $post->title = "My post";
	 * $post->save(null, array('validate' => false));
	 * }}}
	 *
	 * @see lithium\data\Model::$validates
	 * @see lithium\data\Model::validates()
	 * @param object $entity The record or document object to be saved in the database. This
	 *               parameter is implicit and should not be passed under normal circumstances.
	 *               In the above example, the call to `save()` on the `$post` object is
	 *               transparently proxied through to the `Post` model class, and `$post` is passed
	 *               in as the `$entity` parameter.
	 * @param array $data Any data that should be assigned to the record before it is saved.
	 * @param array $options Options:
	 *        - `'callbacks'` _boolean_: If `false`, all callbacks will be disabled before
	 *           executing. Defaults to `true`.
	 *        - `'validate'` _mixed_: If `false`, validation will be skipped, and the record will
	 *          be immediately saved. Defaults to `true`. May also be specified as an array, in
	 *          which case it will replace the default validation rules specified in the
	 *         `$validates` property of the model.
	 *        - `'whitelist'` _array_: An array of fields that are allowed to be saved to this
	 *          record.
	 *
	 * @return boolean Returns `true` on a successful save operation, `false` on failure.
	 */
	public function save($entity, $data = null, array $options = array()) {
		$self = static::_object();
		$_meta = array('model' => get_called_class()) + $self->_meta;
		$_schema = $self->_schema;

		$defaults = array(
			'validate' => true,
			'whitelist' => null,
			'callbacks' => true,
			'locked' => $self->_meta['locked'],
		);
		$options += $defaults;
		$params = compact('entity', 'data', 'options');

		$filter = function($self, $params) use ($_meta, $_schema) {
			$entity = $params['entity'];
			$options = $params['options'];

			if ($params['data']) {
				$entity->set($params['data']);
			}
			if ($rules = $options['validate']) {
				if (!$entity->validates(is_array($rules) ? compact('rules') : array())) {
					return false;
				}
			}
			if ($options['whitelist'] || $options['locked']) {
				$whitelist = $options['whitelist'] ?: array_keys($_schema);
			}

			$type = $entity->exists() ? 'update' : 'create';
			$queryOpts = compact('type', 'whitelist', 'entity') + $options + $_meta;
			$query = $self::invokeMethod('_instance', array('query', $queryOpts));
			return $self::connection()->{$type}($query, $options);
		};

		if (!$options['callbacks']) {
			return $filter($entity, $options);
		}
		return static::_filter(__FUNCTION__, $params, $filter);
	}

	/**
	 * Indicates whether the `Model`'s current data validates, given the
	 * current rules setup.
	 *
	 * @param string $entity Model record to validate.
	 * @param array $options Options.
	 * @return boolean Success.
	 */
	public function validates($entity, array $options = array()) {
		$defaults = array('rules' => $this->validates);
		$options += $defaults;
		$self = static::_object();
		$validator = static::$_classes['validator'];
		$params = compact('entity', 'options');

		$filter = function($parent, $params) use (&$self, $validator) {
			$entity = $params['entity'];
			$options = $params['options'];
			$rules = $options['rules'];
			unset($options['rules']);

			if ($errors = $validator::check($entity->data(), $rules, $options)) {
				$entity->errors($errors);
			}
			return empty($errors);
		};
		return static::_filter(__FUNCTION__, $params, $filter);
	}

	/**
	 * Deletes the data associated with the current `Model`.
	 *
	 * @param string $entity Entity to delete.
	 * @param array $options Options.
	 * @return boolean Success.
	 */
	public function delete($entity, array $options = array()) {
		$self = static::_object();
		$params = compact('entity', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$options = $params + $params['options'] + array('model' => $self, 'type' => 'delete');
			unset($options['options']);

			$query = $self::invokeMethod('_instance', array('query', $options));
			return $self::connection()->delete($query, $options);
		});
	}

	/**
	 * Update multiple records or documents with the given data, restricted by the given set of
	 * criteria (optional).
	 *
	 * @param mixed $data Typically an array of key/value pairs that specify the new data with which
	 *              the records will be updated. For SQL databases, this can optionally be an SQL
	 *              fragment representing the `SET` clause of an `UPDATE` query.
	 * @param mixed $conditions An array of key/value pairs representing the scope of the records
	 *              to be updated.
	 * @param array $options Any database-specific options to use when performing the operation. See
	 *              the `delete()` method of the corresponding backend database for available
	 *              options.
	 * @return boolean Returns `true` if the update operation succeeded, otherwise `false`.
	 */
	public static function update($data, $conditions = array(), array $options = array()) {
		$self = static::_object();
		$params = compact('data', 'conditions', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$options = $params + $params['options'] + array('model' => $self, 'type' => 'update');
			unset($options['options']);

			$query = $self::invokeMethod('_instance', array('query', $options));
			return $self::connection()->update($query, $options);
		});
	}

	/**
	 * Remove multiple documents or records based on a given set of criteria. **WARNING**: If no
	 * criteria are specified, or if the criteria (`$conditions`) is an empty value (i.e. an empty
	 * array or `null`), all the data in the backend data source (i.e. table or collection) _will_
	 * be deleted.
	 *
	 * @param mixed $conditions An array of key/value pairs representing the scope of the records or
	 *              documents to be deleted.
	 * @param array $options Any database-specific options to use when performing the operation. See
	 *              the `delete()` method of the corresponding backend database for available
	 *              options.
	 * @return boolean Returns `true` if the remove operation succeeded, otherwise `false`.
	 */
	public static function remove($conditions = array(), array $options = array()) {
		$self = static::_object();
		$params = compact('conditions', 'options');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
			$options = $params['options'] + $params + array('model' => $self, 'type' => 'delete');
			unset($options['options']);

			$query = $self::invokeMethod('_instance', array('query', $options));
			return $self::connection()->delete($query, $options);
		});
	}

	/**
	 * Gets the connection object to which this model is bound. Throws exceptions if a connection
	 * isn't set, or if the connection named isn't configured.
	 *
	 * @return object Returns an instance of `lithium\data\Source` from the connection configuration
	 *         to which this model is bound.
	 */
	public static function &connection() {
		$self = static::_object();
		$connections = static::$_classes['connections'];
		$name = isset($self->_meta['connection']) ? $self->_meta['connection'] : null;

		if ($conn = $connections::get($name)) {
			return $conn;
		}
		throw new ConfigException("The data connection {$name} is not configured");
	}

	/**
	 * Gets just the class name portion of a fully-name-spaced class name, i.e.
	 * `app\models\Post::_name()` returns `'Post'`.
	 *
	 * @return string
	 */
	protected static function _name() {
		return basename(str_replace('\\', '/', get_called_class()));
	}

	/**
	 * Wraps `StaticObject::applyFilter()` to account for object instances.
	 *
	 * @see lithium\core\StaticObject::applyFilter()
	 * @param string $method
	 * @param mixed $closure
	 */
	public static function applyFilter($method, $closure = null) {
		$instance = static::_object();
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
		$instance = static::_object();

		if (isset($instance->_instanceFilters[$method])) {
			$filters = array_merge($instance->_instanceFilters[$method], $filters);
		}
		return parent::_filter($method, $params, $callback, $filters);
	}

	protected static function &_object() {
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
		$self = static::_object();

		if (!$self->_meta['connection']) {
			return;
		}

		foreach ($self->_relationTypes as $type => $keys) {
			foreach (Set::normalize($self->{$type}) as $name => $config) {
				static::bind($type, $name, (array) $config);
			}
		}
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

	/**
	 * Exports an array of custom finders which use the filter system to wrap around `find()`.
	 *
	 * @return void
	 */
	protected static function _findFilters() {
		$self = static::_object();
		$_query =& $self->_query;

		return array(
			'first' => function($self, $params, $chain) {
				$params['options']['limit'] = 1;
				$data = $chain->next($self, $params, $chain);
				$data = is_object($data) ? $data->rewind() : $data;
				return $data ?: null;
			},
			'list' => function($self, $params, $chain) {
				$result = array();
				$meta = $self::meta();
				$name = $meta['key'];

				foreach ($chain->next($self, $params, $chain) as $entity) {
					$key = $entity->{$name};
					$result[is_scalar($key) ? $key : (string) $key] = $entity->{$meta['title']};
				}
				return $result;
			},
			'count' => function($self, $params, $chain) use ($_query) {
				$model = $self;
				$type = $params['type'];
				$options = array_diff_key($params['options'], $_query);

				if ($options && !isset($params['options']['conditions'])) {
					$options = array('conditions' => $options);
				} else {
					$options = $params['options'];
				}
				$options += array('type' => 'read') + compact('model');
				$query = $self::invokeMethod('_instance', array('query', $options));
				return $self::connection()->calculation('count', $query, $options);
			}
		);
	}

	/**
	 * @deprecated
	 * @see lithium\data\Model::connection()
	 */
	protected static function &_connection() {
		return static::connection();
	}
}

?>