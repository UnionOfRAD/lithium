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
use lithium\aop\Filters;
use lithium\util\Set;
use lithium\util\Inflector;
use lithium\core\ConfigException;
use BadMethodCallException;

/**
 * The `Model` class is the starting point for the domain logic of your application.
 * Models are tasked with providing meaning to otherwise raw and unprocessed data (e.g.
 * user profile).
 *
 * Models expose a consistent and unified API to interact with an underlying data source (e.g.
 * MongoDB, CouchDB, MySQL) for operations such as querying, saving, updating and deleting data
 * from the persistent storage.
 *
 * Classes extending this one should, conventionally, be named as Plural, CamelCase and be
 * placed in the `models` directory. i.e. a posts model would be `model/Posts.php`.
 *
 * Models allow you to interact with your data in two fundamentally different ways: querying, and
 * data mutation (saving/updating/deleting). All **query-related** operations may be done through the
 * static `find()` method, along with some additional utility methods provided for convenience.
 *
 * Examples:
 * ```
 * // Return all 'post' records
 * Posts::find('all');
 * Posts::all(); // This is equivalent to the above.
 *
 * // With conditions and a limit
 * Posts::find('all', ['conditions' => ['published' => true], 'limit' => 10]);
 *
 * // Integer count of all 'post' records
 * Posts::find('count');
 *
 * // With conditions
 * Posts::find('count', ['conditions' => ['published' => true]]);
 * ```
 *
 * The actual objects returned from `find()` calls will depend on the type of data source in use.
 * MongoDB, for example, will return results as a `Document` (as will CouchDB), while MySQL will
 * return results as a `RecordSet`. Both of these classes extend a common `lithium\data\Collection`
 * class, and provide the necessary abstraction to make working with either type completely
 * transparent.
 *
 * For **data mutation** (saving/updating/deleting), the `Model` class acts as a broker to the proper
 * objects. When creating a new record or document, for example, a call to `Posts::create()` will
 * return an instance of `lithium\data\entity\Record` or `lithium\data\entity\Document`, which can
 * then be acted upon.
 *
 * Example:
 * ```
 * $post = Posts::create();
 * $post->author = 'Robert';
 * $post->title = 'Newest Post!';
 * $post->content = 'Lithium rocks. That is all.';
 *
 * $post->save();
 * ```
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
	 * ```
	 * public $validates = [
	 *     'title' => 'please enter a title',
	 *     'email' => [
	 *         ['notEmpty', 'message' => 'Email is empty.'],
	 *         ['email', 'message' => 'Email is not valid.'],
	 *     ]
	 * ];
	 * ```
	 *
	 * @var array
	 */
	public $validates = [];

	/**
	 * Model hasOne relations.
	 *
	 * @var array
	 */
	public $hasOne = [];

	/**
	 * Model hasMany relations.
	 *
	 * @var array
	 */
	public $hasMany = [];

	/**
	 * Model belongsTo relations.
	 *
	 * @var array
	 */
	public $belongsTo = [];

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
	protected static $_instances = [];

	/**
	 * List of initialized instances.
	 *
	 * @see lithium\data\Model::_initialize();
	 * @var array
	 */
	protected static $_initialized = [];

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = [
		'connections' => 'lithium\data\Connections'
	];

	/**
	 * A list of the current relation types for this `Model`.
	 *
	 * @var array
	 */
	protected $_relations = [];

	/**
	 * Matching between relation's fieldnames and their corresponding relation name.
	 *
	 * @var array
	 */
	protected $_relationFieldNames = [];

	/**
	 * List of relation types.
	 *
	 * Valid relation types are:
	 * - `belongsTo`
	 * - `hasOne`
	 * - `hasMany`
	 *
	 * @var array
	 */
	protected $_relationTypes = ['belongsTo', 'hasOne', 'hasMany'];

	/**
	 * Store available relation names for this model which still unloaded.
	 *
	 * @var array This array use the following notation : `relation_name => relation_type`.
	 */
	protected $_relationsToLoad = [];

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
	protected $_meta = [
		'name' => null,
		'title' => null,
		'class' => null,
		'source' => null,
		'connection' => 'default'
	];

	/**
	 * Array of closures used to lazily initialize metadata.
	 *
	 * @var array
	 */
	protected $_initializers = [];

	/**
	 * Defines the data schema in array notation or, after initialization, holds the
	 * schema object.
	 *
	 * `Model` subclasses can manually define a schema in array notation. The array
	 * notation will then be lazily converted to a schema object by the first call to
	 * `Model::schema()`.
	 *
	 * The schema should only be defined in subclasses for schemaless persistent data
	 * sources (e.g. MongoDB), for all other data sources this is done automatically. If
	 * you desire a fixed schema for a schemaless data source, the following example shows
	 * how you'd define one manually.
	 *
	 * For MongoDB specifically, you can also automate schema definition. Please see
	 * lithium\data\soure\MondoDb::$_schema for more information.
	 *
	 * ```
	 * protected $_schema = [
	 *     '_id'  => ['type' => 'id'],
	 *     'name' => ['type' => 'string', 'default' => 'Moe', 'null' => false],
	 *     'sign' => ['type' => 'string', 'default' => 'bar', 'null' => false],
	 *     'age'  => ['type' => 'integer', 'default' => 0, 'null' => false]
	 * ];
	 * ```
	 *
	 * @see lithium\data\source\MongoDb::$_schema
	 * @see lithium\data\Model::schema()
	 * @see lithium\data\Schema
	 * @var array|\lithium\data\Schema
	 */
	protected $_schema = [];

	/**
	 * Default query parameters for the model finders.
	 *
	 * Can be either redefined in a model subclass or changed during runtime
	 * using `Model::query()`.
	 *
	 * For a detailed description of the available query options below see
	 * the description of the `$options` parameter of `Model::find()`.
	 *
	 * @see lithium\data\Model::find()
	 * @see lithium\data\Model::query()
	 * @see lithium\data\model\Query::__construct()
	 * @var array
	 */
	protected $_query = [
		'fields'     => null,
		'conditions' => null,
		'having'     => null,
		'group'      => null,
		'order'      => null,
		'limit'      => null,
		'offset'     => null,
		'page'       => null,
		'with'       => [],
		'joins'      => []
	];

	/**
	 * Custom find query properties, indexed by name.
	 *
	 * @see lithium\data\Model::finder()
	 * @var array
	 */
	protected $_finders = [];

	/**
	 * Stores all custom instance methods created by `Model::instanceMethods`.
	 *
	 * @var array
	 */
	protected static $_instanceMethods = [];

	/**
	 * Holds an array of values that should be processed on `Model::config()`. Each value should
	 * have a matching protected property (prefixed with `_`) defined in the class. If the
	 * property is an array, the property name should be the key and the value should be `'merge'`.
	 *
	 * @see lithium\data\Model::config()
	 * @var array
	 */
	protected $_autoConfig = [
		'meta',
		'finders',
		'query',
		'schema',
		'classes',
		'initializers'
	];

	/**
	 * Holds an array of attributes to be inherited.
	 *
	 * @see lithium\data\Model::_inherited()
	 * @var array
	 */
	protected $_inherits = [];

	/**
	 * Configures the model for use. This method will set the `Model::$_schema`, `Model::$_meta`,
	 * `Model::$_finders` class attributes, as well as obtain a handle to the configured
	 * persistent storage connection.
	 *
	 * @param array $config Possible options are:
	 *        - `meta`: Meta-information for this model, such as the connection.
	 *        - `finders`: Custom finders for this model.
	 *        - `query`: Default query parameters.
	 *        - `schema`: A `Schema` instance for this model.
	 *        - `classes`: Classes used by this model.
	 */
	public static function config(array $config = []) {
		if (($class = get_called_class()) === __CLASS__) {
			return;
		}

		if (!isset(static::$_instances[$class])) {
			static::$_instances[$class] = new $class();
		}
		$self = static::$_instances[$class];

		foreach ($self->_autoConfig as $key) {
			if (isset($config[$key])) {
				$_key = "_{$key}";
				$val = $config[$key];
				$self->$_key = is_array($val) ? $val + $self->$_key : $val;
			}
		}

		static::$_initialized[$class] = false;
	}

	/**
	 * Init default connection options and connects default finders.
	 *
	 * This method will set the `Model::$_schema`, `Model::$_meta`, `Model::$_finders` class
	 * attributes, as well as obtain a handle to the configured persistent storage connection
	 *
	 * @param string $class The fully-namespaced class name to initialize.
	 * @return object Returns the initialized model instance.
	 */
	protected static function _initialize($class) {
		$self = static::$_instances[$class];

		if (isset(static::$_initialized[$class]) && static::$_initialized[$class]) {
			return $self;
		}
		static::$_initialized[$class] = true;

		$self->_inherit();

		$source = [
			'classes' => [], 'meta' => [], 'finders' => [], 'schema' => []
		];

		$meta = $self->_meta;
		if ($meta['connection']) {
			$classes = $self->_classes;
			$conn = $classes['connections']::get($meta['connection']);
			$source = (($conn) ? $conn->configureClass($class) : []) + $source;
		}

		$self->_classes += $source['classes'];
		$self->_meta = compact('class') + $self->_meta + $source['meta'];

		$self->_initializers += [
			'name' => function($self) {
				return basename(str_replace('\\', '/', $self));
			},
			'source' => function($self) {
				return Inflector::tableize($self::meta('name'));
			},
			'title' => function($self) {
				$titleKeys = ['title', 'name'];
				$titleKeys = array_merge($titleKeys, (array) $self::meta('key'));
				return $self::hasField($titleKeys);
			}
		];

		if (is_object($self->_schema)) {
			$self->_schema->append($source['schema']);
		} else {
			$self->_schema += $source['schema'];
		}

		$self->_finders += $source['finders'] + static::_finders();

		$self->_classes += [
			'query'       => 'lithium\data\model\Query',
			'validator'   => 'lithium\util\Validator',
			'entity'      => 'lithium\data\Entity'
		];

		static::_relationsToLoad();
		return $self;
	}

	/**
	 * Merge parent class attributes to the current instance.
	 */
	protected function _inherit() {

		$inherited = array_fill_keys($this->_inherited(), []);

		foreach (static::_parents() as $parent) {
			$parentConfig = get_class_vars($parent);

			foreach ($inherited as $key => $value) {
				if (isset($parentConfig["{$key}"])) {
					$val = $parentConfig["{$key}"];
					if (is_array($val)) {
						$inherited[$key] += $val;
					}
				}
			}

			if ($parent === __CLASS__) {
				break;
			}
		}

		foreach ($inherited as $key => $value) {
			if (is_array($this->{$key})) {
				$this->{$key} += $value;
			}
		}
	}

	/**
	 * Return inherited attributes.
	 *
	 * @param array
	 */
	protected function _inherited() {
		return array_merge($this->_inherits, [
			'validates',
			'belongsTo',
			'hasMany',
			'hasOne',
			'_meta',
			'_finders',
			'_query',
			'_schema',
			'_classes',
			'_initializers'
		]);
	}

	/**
	 * Returns an instance of a class with given `config`. The `name` could be a key from the
	 * `classes` array, a fully-namespaced class name, or an object. Typically this method is used
	 * in `_init` to create the dependencies used in the current class.
	 *
	 * @param string|object $name A `classes` alias or fully-namespaced class name.
	 * @param array $options The configuration passed to the constructor.
	 * @return object
	 */
	protected static function _instance($name, array $options = []) {
		$self = static::_object();
		if (is_string($name) && isset($self->_classes[$name])) {
			$name = $self->_classes[$name];
		}
		return Libraries::instance(null, $name, $options);
	}

	/**
	 * Enables magic finders. These provide some syntactic-sugar which allows
	 * to i.e. use `Model::all()` instead  of `Model::find('all')`.
	 *
	 * ```
	 * // Retrieves post with id `23` using the `'first'` finder.
	 * Posts::first(['conditions' => ['id' => 23]]);
	 * Posts::findById(23);
	 * Posts::findById(23);
	 *
	 * // All posts that have a trueish `is_published` field.
	 * Posts::all(['conditions' => ['is_published' => true]]);
	 * Posts::findAll(['conditions' => ['is_published' => true]]);
	 * Posts::findAllByIsPublshed(true)
	 *
	 * // Counts all posts.
	 * Posts::count()
	 * ```
	 *
	 * @see lithium\data\Model::find()
	 * @see lithium\data\Model::$_meta
	 * @link http://php.net/language.oop5.overloading.php PHP Manual: Overloading
	 * @throws BadMethodCallException On unhandled call, will throw an exception.
	 * @param string $method Method name caught by `__callStatic()`.
	 * @param array $params Arguments given to the above `$method` call.
	 * @return mixed Results of dispatched `Model::find()` call.
	 */
	public static function __callStatic($method, $params) {
		$self = static::_object();

		if (isset($self->_finders[$method])) {
			if (count($params) === 2 && is_array($params[1])) {
				$params = [$params[1] + [$method => $params[0]]];
			}
			if ($params && !is_array($params[0])) {
				$params[0] = ['conditions' => static::key($params[0])];
			}
			return $self::find($method, $params ? $params[0] : []);
		}
		preg_match('/^findBy(?P<field>\w+)$|^find(?P<type>\w+)By(?P<fields>\w+)$/', $method, $args);

		if (!$args) {
			$message = "Method `%s` not defined or handled in class `%s`.";
			throw new BadMethodCallException(sprintf($message, $method, get_class($self)));
		}

		$field = Inflector::underscore($args['field'] ? $args['field'] : $args['fields']);
		$type = isset($args['type']) ? $args['type'] : 'first';
		$type[0] = strtolower($type[0]);

		$conditions = [$field => array_shift($params)];
		$params = (isset($params[0]) && count($params) === 1) ? $params[0] : $params;
		return $self::find($type, compact('conditions') + $params);
	}

	/**
	 * Magic method that allows calling `Model::_instanceMethods`'s closure like normal methods
	 * on the model instance.
	 *
	 * @see lithium\data\Model::instanceMethods
	 * @param string $method Method name caught by `__call()`.
	 * @param array $params Arguments given to the above `$method` call.
	 * @return mixed
	 */
	public function __call($method, $params) {
		$methods = static::instanceMethods();
		if (isset($methods[$method]) && is_callable($methods[$method])) {
			return call_user_func_array($methods[$method], $params);
		}
		$message = "Unhandled method call `{$method}`.";
		throw new BadMethodCallException($message);
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
	public static function respondsTo($method, $internal = false) {
		$self = static::_object();
		$methods = static::instanceMethods();
		$isFinder = isset($self->_finders[$method]);
		preg_match('/^findBy(?P<field>\w+)$|^find(?P<type>\w+)By(?P<fields>\w+)$/', $method, $args);
		$staticRepondsTo = $isFinder || $method === 'all' || !!$args;
		$instanceRespondsTo = isset($methods[$method]);
		return $instanceRespondsTo || $staticRepondsTo || parent::respondsTo($method, $internal);
	}

	/**
	 * The `find` method allows you to retrieve data from the connected data source.
	 *
	 * Examples:
	 * ```
	 * Posts::find('all'); // returns all records
	 * Posts::find('count'); // returns a count of all records
	 *
	 * // The first ten records that have `'author'` set to `'Bob'`.
	 * Posts::find('all', [
	 *     'conditions' => ['author' => 'Bob'],
	 *     'limit' => 10
	 * ]);
	 *
	 * // First record where the id matches 23.
	 * Posts::find('first', [
	 *     'conditions' => ['id' => 23]
	 * ]);
	 * ```
	 *
	 * Shorthands:
	 * ```
	 * // Shorthand for find first by primary key.
	 * Posts::find(23);
	 *
	 * // Also works with objects.
	 * Posts::find(new MongoId(23));
	 * ```
	 *
	 * @see lithium\data\Model::finder()
	 * @param string|object|integer $type The name of the finder to use. By default the
	 *        following finders are available. Custom finders can be added via `Model::finder()`.
	 *        - `'all'`: Returns all records matching the conditions.
	 *        - `'first'`: Returns the first record matching the conditions.
	 *        - `'count'`: Returns an integer count of all records matching the conditions.
	 *          When using `Database` adapter, you can specify the field to count on
	 *          via `fields`, when multiple fields are given does a count on all fields (`'*'`).
	 *        - `'list'`: Returns a one dimensional array, where the key is the (primary)p
	 *          key and the value the title of the record (the record must have a `'title'`
	 *          field). A result may look like: `[1 => 'Foo', 2 => 'Bar']`.
	 *
	 *        Instead of the name of a finder, also supports shorthand usage with an object or
	 *        integer as the first parameter. When passed such a value it is equal to
	 *        `Model::find('first', ['conditions' => ['<key>' => <value>]])`.
	 *
	 *        Note: When an undefined finder is tried to be used, the method will not error out, but
	 *        fallback to the `'all'` finder.
	 * @param array $options Options for the query.
	 *        Common options accepted are:
	 *        - `'conditions'` _array_: The conditions for the query
	 *           i.e. `'array('is_published' => true)`.
	 *        - `'fields'` _array|null_: The fields that should be retrieved. When set to
	 *          `null` or `'*'` and by default, uses all fields. To optimize query performance,
	 *          limit the fields to just the ones actually needed.
	 *        - `'order'` _array|string_: The order in which the data will be returned,
	 *           i.e. `'created ASC'` sorts by created date in ascending order. To sort by
	 *           multiple fields use the array syntax `array('title' => 'ASC', 'id' => 'ASC)`.
	 *        - `'limit'` _integer_: The maximum number of records to return.
	 *        - `'page'` _integer_: Allows to paginate data sets. Specifies the page of the set
	 *          together with the limit option specifying the number of records per page. The first
	 *          page starts at `1`. Equals limit * offset.
	 *        - `'with'` _array_: Relationship names to be included in the query.
	 *        Also supported are:
	 *        - `'offset'` _integer_
	 *        - `'having'` _array|string_
	 *        - `'group'` _array|string_
	 *        - `'joins'` _array_
	 * @return mixed The result/s of the find. Actual result depends on the finder being used. Most
	 *         often this is an instance of `lithium\data\Collection` or `lithium\data\Entity`.
	 * @filter Allows to execute logic before querying (i.e. for rewriting of $options)
	 *         or after i.e. for caching results.
	 */
	public static function find($type, array $options = []) {
		$self = static::_object();

		if (is_object($type) || !isset($self->_finders[$type])) {
			$options['conditions'] = static::key($type);
			$type = 'first';
		}

		$options += (array) $self->_query;
		$meta = ['meta' => $self->_meta, 'name' => get_called_class()];
		$params = compact('type', 'options');

		$implementation = function($params) use ($meta) {
			$options = $params['options'] + ['type' => 'read', 'model' => $meta['name']];
			$query = static::_instance('query', $options);

			return static::connection()->read($query, $options);
		};
		if (isset($self->_finders[$type])) {
			$finder = $self->_finders[$type];

			$reflect = new \ReflectionFunction($finder);
			if ($reflect->getNumberOfParameters() > 2) {
				$message  = 'Old style finder function in file ' . $reflect->getFileName() . ' ';
				$message .= 'on line ' . $reflect->getStartLine() . '. ';
				$message .= 'The signature for finder functions has changed. It is now ';
				$message .= '`($params, $next)` instead of the old `($self, $params, $chain)`. ';
				$message .= 'Instead of `$self` use `$this` or `static`.';
				trigger_error($message, E_USER_DEPRECATED);

				return Filters::bcRun(
					get_called_class(), __FUNCTION__, $params, $implementation, [$finder]
				);
			}

			$implementation = function($params) use ($finder, $implementation) {
				return $finder($params, $implementation);
			};
		}
		return Filters::run(get_called_class(), __FUNCTION__, $params, $implementation);
	}

	/**
	 * Sets or gets a custom finder by name. The finder definition can be an array of
	 * default query options, or an anonymous functions that accepts an array of query
	 * options, and a function to continue. To get a finder specify just the name.
	 *
	 * In this example we define and use `'published'` finder to quickly
	 * retrieve all published posts.
	 * ```
	 * Posts::finder('published', [
	 *     'conditions' => ['is_published' => true]
	 * ]);
	 *
	 * Posts::find('published');
	 * ```
	 *
	 * Here we define the same finder using an anonymous function which
	 * gives us more control over query modification.
	 * ```
	 * Posts::finder('published', function($params, $next) {
	 *     $params['options']['conditions']['is_published'] = true;
	 *
	 *     // Perform modifications before executing the query...
	 *     $result = $next($params);
	 *     // ... or after it has executed.
	 *
	 *     return $result;
	 * });
	 * ```
	 *
	 * When using finder array definitions the option array is _recursivly_ merged
	 * (using `Set::merge()`) with additional options specified on the `Model::find()`
	 * call. Options specificed on the find will overwrite options specified in the
	 * finder.
	 *
	 * Array finder definitions are normalized here, so that it can be relied upon that defined
	 * finders are always anonymous functions.
	 *
	 * @see lithium\util\Set::merge()
	 * @param string $name The finder name, e.g. `'first'`.
	 * @param string|callable|null $finder The finder definition.
	 * @return callable|void Returns finder definition if querying, or void if setting.
	 */
	public static function finder($name, $finder = null) {
		$self = static::_object();

		if ($finder === null) {
			return isset($self->_finders[$name]) ? $self->_finders[$name] : null;
		}
		if (is_array($finder)) {
			$finder = function($params, $next) use ($finder) {
				$params['options'] = Set::merge($params['options'], $finder);
				return $next($params);
			};
		}
		$self->_finders[$name] = $finder;
	}

	/**
	 * Returns an array with the default finders.
	 *
	 * @see lithium\data\Model::_initialize()
	 * @return array
	 */
	protected static function _finders() {
		$self = static::_object();

		return [
			'all' => function($params, $next) {
				return $next($params);
			},
			'first' => function($params, $next) {
				$options =& $params['options'];
				$options['limit'] = 1;

				$data = $next($params);

				if (isset($options['return']) && $options['return'] === 'array') {
					$data = is_array($data) ? reset($data) : $data;
				} else {
					$data = is_object($data) ? $data->rewind() : $data;
				}
				return $data ?: null;
			},
			'list' => function($params, $next) use ($self) {
				$result = [];
				$meta = $self::meta();
				$name = $meta['key'];

				foreach ($next($params) as $entity) {
					$key = $entity->{$name};
					$result[is_scalar($key) ? $key : (string) $key] = $entity->title();
				}
				return $result;
			},
			'count' => function($params, $next) use ($self) {
				$options = array_diff_key($params['options'], $self->_query);

				if ($options && !isset($params['options']['conditions'])) {
					$options = ['conditions' => $options];
				} else {
					$options = $params['options'];
				}
				$options += ['type' => 'read', 'model' => $self];
				$query = $self::invokeMethod('_instance', ['query', $options]);
				return $self::connection()->calculation('count', $query, $options);
			}
		];
	}

	/**
	 * Gets or sets the default query for the model.
	 *
	 * @param array $query Possible options are:
	 *        - `'conditions'`: The conditional query elements, e.g.
	 *          `'conditions' => ['published' => true]`
	 *        - `'fields'`: The fields that should be retrieved. When set to `null`, defaults to
	 *          all fields.
	 *        - `'order'`: The order in which the data will be returned, e.g. `'order' => 'ASC'`.
	 *        - `'limit'`: The maximum number of records to return.
	 *        - `'page'`: For pagination of data.
	 *        - `'with'`: An array of relationship names to be included in the query.
	 * @return mixed Returns the query definition if querying, or `null` if setting.
	 */
	public static function query($query = null) {
		$self = static::_object();

		if (!$query) {
			return $self->_query;
		}
		$self->_query = $query + $self->_query;
	}

	/**
	 * Gets or sets Model's metadata.
	 *
	 * @see lithium\data\Model::$_meta
	 * @param string $key Model metadata key.
	 * @param string $value Model metadata value.
	 * @return mixed Metadata value for a given key.
	 */
	public static function meta($key = null, $value = null) {
		$self = static::_object();
		$isArray = is_array($key);

		if ($value || $isArray) {
			$value ? $self->_meta[$key] = $value : $self->_meta = $key + $self->_meta;
			return;
		}
		return $self->_getMetaKey($isArray ? null : $key);
	}

	/**
	 * Helper method used by `meta()` to generate and cache metadata values.
	 *
	 * @param string $key The name of the meta value to return, or `null`, to return all values.
	 * @return mixed Returns the value of the meta key specified by `$key`, or an array of all meta
	 *         values if `$key` is `null`.
	 */
	protected function _getMetaKey($key = null) {
		if (!$key) {
			$all = array_keys($this->_initializers);
			$call = [&$this, '_getMetaKey'];
			return $all ? array_combine($all, array_map($call, $all)) + $this->_meta : $this->_meta;
		}

		if (isset($this->_meta[$key])) {
			return $this->_meta[$key];
		}
		if (isset($this->_initializers[$key]) && $initializer = $this->_initializers[$key]) {
			unset($this->_initializers[$key]);
			return ($this->_meta[$key] = $initializer(get_called_class()));
		}
	}

	/**
	 * The `title()` method is invoked whenever an `Entity` object is cast or coerced
	 * to a string. This method can also be called on the entity directly, i.e. `$post->title()`.
	 *
	 * By default, when generating the title for an object, it uses the the field specified in
	 * the `'title'` key of the model's meta data definition. Override this method to generate
	 * custom titles for objects of this model's type.
	 *
	 * @see lithium\data\Model::$_meta
	 * @see lithium\data\Entity::__toString()
	 * @param object $entity The `Entity` instance on which the title method is called.
	 * @return string Returns the title representation of the entity on which this method is called.
	 */
	public function title($entity) {
		$field = static::meta('title');
		return $entity->{$field};
	}

	/**
	 * If no values supplied, returns the name of the `Model` key. If values
	 * are supplied, returns the key value.
	 *
	 * @param mixed $values An array of values or object with values. If `$values` is `null`,
	 *              the meta `'key'` of the model is returned.
	 * @return mixed Key value.
	 */
	public static function key($values = null) {
		$key = static::meta('key');

		if ($values === null) {
			return $key;
		}

		$self = static::_object();
		$entity = $self->_classes['entity'];
		if (is_object($values) && is_string($key)) {
			return static::_key($key, $values, $entity);
		} elseif ($values instanceof $entity) {
			$values = $values->to('array');
		}

		if (!is_array($values) && !is_array($key)) {
			return [$key => $values];
		}

		$key = (array) $key;
		$result = [];
		foreach ($key as $value) {
			if (!isset($values[$value])) {
				return null;
			}
			$result[$value] = $values[$value];
		}
		return $result;
	}

	/**
	 * Helper for the `Model::key()` function
	 *
	 * @see lithium\data\Model::key()
	 * @param string $key The key
	 * @param object $values Object with attributes.
	 * @param string $entity The fully-namespaced entity class name.
	 * @return mixed The key value array or `null` if the `$values` object has no attribute
	 *         named `$key`
	 */
	protected static function _key($key, $values, $entity) {
		if (isset($values->$key)) {
			return [$key => $values->$key];
		} elseif (!$values instanceof $entity) {
			return [$key => $values];
		}
		return null;
	}

	/**
	 * Returns a list of models related to `Model`, or a list of models related
	 * to this model, but of a certain type.
	 *
	 * @param string $type A type of model relation.
	 * @return array|object|void An array of relation instances or an instance of relation.
	 */
	public static function relations($type = null) {
		$self = static::_object();

		if ($type === null) {
			return static::_relations();
		}
		if (isset($self->_relationFieldNames[$type])) {
			$type = $self->_relationFieldNames[$type];
		}

		if (isset($self->_relations[$type])) {
			return $self->_relations[$type];
		}
		if (isset($self->_relationsToLoad[$type])) {
			return static::_relations(null, $type);
		}
		if (in_array($type, $self->_relationTypes, true)) {
			return array_keys(static::_relations($type));
		}
	}

	/**
	 * This method automagically bind in the fly unloaded relations.
	 *
	 * @see lithium\data\Model::relations()
	 * @param $type A type of model relation.
	 * @param $name A relation name.
	 * @return An array of relation instances or an instance of relation.
	 */
	protected static function _relations($type = null, $name = null) {
		$self = static::_object();

		if ($name) {
			if (isset($self->_relationsToLoad[$name])) {
				$t = $self->_relationsToLoad[$name];
				unset($self->_relationsToLoad[$name]);
				return static::bind($t, $name, (array) $self->{$t}[$name]);
			}
			return isset($self->_relations[$name]) ? $self->_relations[$name] : null;
		}
		if (!$type) {
			foreach ($self->_relationsToLoad as $name => $t) {
				static::bind($t, $name, (array) $self->{$t}[$name]);
			}
			$self->_relationsToLoad = [];
			return $self->_relations;
		}
		foreach ($self->_relationsToLoad as $name => $t) {
			if ($type === $t) {
				static::bind($t, $name, (array) $self->{$t}[$name]);
				unset($self->_relationsToLoad[$name]);
			}
		}
		return array_filter($self->_relations, function($i) use ($type) {
			return $i->data('type') === $type;
		});
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
	public static function bind($type, $name, array $config = []) {
		$self = static::_object();
		if (!isset($config['fieldName'])) {
			$config['fieldName'] = $self->_relationFieldName($type, $name);
		}

		if (!in_array($type, $self->_relationTypes)) {
			throw new ConfigException("Invalid relationship type `{$type}` specified.");
		}
		$self->_relationFieldNames[$config['fieldName']] = $name;
		$rel = static::connection()->relationship(get_called_class(), $type, $name, $config);
		return $self->_relations[$name] = $rel;
	}

	/**
	 * Lazy-initialize the schema for this Model object, if it is not already manually set in the
	 * object. You can declare `protected $_schema = [...]` to define the schema manually.
	 *
	 * @param mixed $field Optional. You may pass a field name to get schema information for just
	 *        one field. Otherwise, an array containing all fields is returned. If `false`, the
	 *        schema is reset to an empty value. If an array, field definitions contained are
	 *        appended to the schema.
	 * @return array|lithium\data\Schema
	 */
	public static function schema($field = null) {
		$self = static::_object();

		if (!is_object($self->_schema)) {
			$self->_schema = static::connection()->describe(
				$self::meta('source'), $self->_schema, $self->_meta
			);
			if (!is_object($self->_schema)) {
				$class = get_called_class();
				throw new ConfigException("Could not load schema object for model `{$class}`.");
			}
			$key = (array) $self::meta('key');
			if ($self->_schema && $self->_schema->fields() && !$self->_schema->has($key)) {
				$key = implode('`, `', $key);
				throw new ConfigException("Missing key `{$key}` from schema.");
			}
		}
		if ($field === false) {
			return $self->_schema->reset();
		}
		if (is_array($field)) {
			return $self->_schema->append($field);
		}
		return $field ? $self->_schema->fields($field) : $self->_schema;
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
		if (!is_array($field)) {
			return static::schema()->fields($field);
		}
		foreach ($field as $f) {
			if (static::hasField($f)) {
				return $f;
			}
		}
		return false;
	}

	/**
	 * Instantiates a new record or document object, initialized with any data passed in. For
	 * example:
	 *
	 * ```
	 * $post = Posts::create(['title' => 'New post']);
	 * echo $post->title; // echoes 'New post'
	 * $success = $post->save();
	 * ```
	 *
	 * Note that while this method creates a new object, there is no effect on the database until
	 * the `save()` method is called.
	 *
	 * In addition, this method can be used to simulate loading a pre-existing object from the
	 * database, without actually querying the database:
	 *
	 * ```
	 * $post = Posts::create(['id' => $id, 'moreData' => 'foo'], ['exists' => true]);
	 * $post->title = 'New title';
	 * $success = $post->save();
	 * ```
	 *
	 * This will create an update query against the object with an ID matching `$id`. Also note that
	 * only the `title` field will be updated.
	 *
	 * @param array $data Any data that this object should be populated with initially.
	 * @param array $options Options to be passed to item.
	 * @return object Returns a new, _un-saved_ record or document object. In addition to the values
	 *         passed to `$data`, the object will also contain any values assigned to the
	 *         `'default'` key of each field defined in `$_schema`.
	 * @filter
	 */
	public static function create(array $data = [], array $options = []) {
		$defaults = ['defaults' => true, 'class' => 'entity'];
		$options += $defaults;

		$params = compact('data', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$class = $params['options']['class'];
			unset($params['options']['class']);
			if ($class === 'entity' && $params['options']['defaults']) {
				$data = Set::merge(Set::expand(static::schema()->defaults()), $params['data']);
			} else {
				$data = $params['data'];
			}
			return static::_instance($class, [
				'model' => get_called_class(),
				'data' => $data
			] + $params['options']);
		});
	}

	/**
	 * Getter and setter for custom instance methods. This is used in `Entity::__call()`.
	 *
	 * ```
	 * Model::instanceMethods([
	 *     'methodName' => ['Class', 'method'],
	 *     'anotherMethod' => [$object, 'method'],
	 *     'closureCallback' => function($entity) {}
	 * ]);
	 * ```
	 *
	 * @see lithium\data\Entity::__call()
	 * @param array $methods
	 * @return array
	 */
	public static function instanceMethods(array $methods = null) {
		$class = get_called_class();

		if (!isset(static::$_instanceMethods[$class])) {
			static::$_instanceMethods[$class] = [];
		}
		if ($methods === []) {
			return static::$_instanceMethods[$class] = [];
		}
		if ($methods !== null) {
			static::$_instanceMethods[$class] = $methods + static::$_instanceMethods[$class];
		}
		return static::$_instanceMethods[$class];
	}

	/**
	 * An instance method (called on record and document objects) to create or update the record or
	 * document in the database that corresponds to `$entity`.
	 *
	 * For example, to create a new record or document:
	 * ```
	 * $post = Posts::create(); // Creates a new object, which doesn't exist in the database yet
	 * $post->title = "My post";
	 * $success = $post->save();
	 * ```
	 *
	 * It is also used to update existing database objects, as in the following:
	 * ```
	 * $post = Posts::first($id);
	 * $post->title = "Revised title";
	 * $success = $post->save();
	 * ```
	 *
	 * By default, an object's data will be checked against the validation rules of the model it is
	 * bound to. Any validation errors that result can then be accessed through the `errors()`
	 * method.
	 *
	 * ```
	 * if (!$post->save($someData)) {
	 *     return ['errors' => $post->errors()];
	 * }
	 * ```
	 *
	 * To override the validation checks and save anyway, you can pass the `'validate'` option:
	 *
	 * ```
	 * $post->title = "We Don't Need No Stinkin' Validation";
	 * $post->body = "I know what I'm doing.";
	 * $post->save(null, ['validate' => false]);
	 * ```
	 *
	 * By default only validates and saves fields from the schema (if available). This behavior
	 * can be controlled via the `'whitelist'` and `'locked'` options.
	 *
	 * @see lithium\data\Model::$validates
	 * @see lithium\data\Model::validates()
	 * @see lithium\data\Entity::errors()
	 * @param object $entity The record or document object to be saved in the database. This
	 *        parameter is implicit and should not be passed under normal circumstances.
	 *        In the above example, the call to `save()` on the `$post` object is
	 *        transparently proxied through to the `Posts` model class, and `$post` is passed
	 *        in as the `$entity` parameter.
	 * @param array $data Any data that should be assigned to the record before it is saved.
	 * @param array $options Options:
	 *        - `'callbacks'` _boolean_: If `false`, all callbacks will be disabled before
	 *           executing. Defaults to `true`.
	 *        - `'validate'` _boolean|array_: If `false`, validation will be skipped, and the
	 *          record will be immediately saved. Defaults to `true`. May also be specified as
	 *          an array, in which case it will replace the default validation rules specified
	 *          in the `$validates` property of the model.
	 *        - `'events'` _string|array_: A string or array defining one or more validation
	 *          _events_. Events are different contexts in which data events can occur, and
	 *          correspond to the optional `'on'` key in validation rules. They will be passed
	 *          to the validates() method if `'validate'` is not `false`.
	 *        - `'whitelist'` _array_: An array of fields that are allowed to be saved to this
	 *          record. When unprovided will - if available - default to fields of the current
	 *          schema and the `'locked'` option is not `false`.
	 *        - `'locked'` _boolean_: Whether to use schema for saving just fields from the
	 *          schema or not. Defaults to `true`.
	 * @return boolean Returns `true` on a successful save operation, `false` on failure.
	 * @filter
	 */
	public function save($entity, $data = null, array $options = []) {
		$self = static::_object();
		$_meta = ['model' => get_called_class()] + $self->_meta;
		$_schema = $self->schema();

		$defaults = [
			'validate' => true,
			'events' => $entity->exists() ? 'update' : 'create',
			'whitelist' => null,
			'callbacks' => true,
			'locked' => $self->_meta['locked']
		];
		$options += $defaults;
		$params = compact('entity', 'data', 'options');

		$filter = function($params) use ($_meta, $_schema) {
			$entity = $params['entity'];
			$options = $params['options'];

			if ($params['data']) {
				$entity->set($params['data']);
			}
			if (($whitelist = $options['whitelist']) || $options['locked']) {
				$whitelist = $whitelist ?: array_keys($_schema->fields());
			}
			if ($rules = $options['validate']) {
				$events = $options['events'];
				$validateOpts = compact('events', 'whitelist');
				if (is_array($rules)) {
					$validateOpts['rules'] = $rules;
				}

				if (!$entity->validates($validateOpts)) {
					return false;
				}
			}
			$type = $entity->exists() ? 'update' : 'create';

			$query = static::_instance('query',
				compact('type', 'whitelist', 'entity') + $options + $_meta
			);
			return static::connection()->{$type}($query, $options);
		};

		if (!$options['callbacks']) {
			return $filter($params);
		}
		return Filters::run(get_called_class(), __FUNCTION__, $params, $filter);
	}

	/**
	 * An important part of describing the business logic of a model class is defining the
	 * validation rules. In Lithium models, rules are defined through the `$validates` class
	 * property, and are used by this method before saving to verify the correctness of the data
	 * being sent to the backend data source.
	 *
	 * Note that these are application-level validation rules, and do not
	 * interact with any rules or constraints defined in your data source. If such constraints fail,
	 * an exception will be thrown by the database layer. The `validates()` method only checks
	 * against the rules defined in application code.
	 *
	 * This method uses the `Validator` class to perform data validation. An array representation of
	 * the entity object to be tested is passed to the `check()` method, along with the model's
	 * validation rules. Any rules defined in the `Validator` class can be used to validate fields.
	 * See the `Validator` class to add custom rules, or override built-in rules.
	 *
	 * @see lithium\data\Model::$validates
	 * @see lithium\util\Validator::check()
	 * @see lithium\data\Entity::errors()
	 * @param object $entity Model entity to validate. Typically either a `Record` or `Document`
	 *        object. In the following example:
	 *        ```
	 *            $post = Posts::create($data);
	 *            $success = $post->validates();
	 *        ```
	 *        The `$entity` parameter is equal to the `$post` object instance.
	 * @param array $options Available options:
	 *        - `'rules'` _array_: If specified, this array will _replace_ the default
	 *          validation rules defined in `$validates`.
	 *        - `'events'` _mixed_: A string or array defining one or more validation
	 *          _events_. Events are different contexts in which data events can occur, and
	 *          correspond to the optional `'on'` key in validation rules. For example, by
	 *          default, `'events'` is set to either `'create'` or `'update'`, depending on
	 *          whether `$entity` already exists. Then, individual rules can specify
	 *          `'on' => 'create'` or `'on' => 'update'` to only be applied at certain times.
	 *          Using this parameter, you can set up custom events in your rules as well, such
	 *          as `'on' => 'login'`. Note that when defining validation rules, the `'on'` key
	 *          can also be an array of multiple events.
	 *        - `'required`' _mixed_: Represents whether the value is required to be present
	 *          in `$values`. If `'required'` is set to `true`, the validation rule will be
	 *          checked. if `'required'` is set to 'false' , the validation rule will be skipped
	 *          if the corresponding key is not present. If don't set `'required'` or set this
	 *          to `null`, the validation rule will be skipped if the corresponding key is not
	 *          present in update and will be checked in insert. Defaults is set to `null`.
	 *        - `'whitelist'` _array_: If specified, only fields in this array will be validated
	 *          and others will be skipped.
	 * @return boolean Returns `true` if all validation rules on all fields succeed, otherwise
	 *         `false`. After validation, the messages for any validation failures are assigned to
	 *         the entity, and accessible through the `errors()` method of the entity object.
	 * @filter
	 */
	public function validates($entity, array $options = []) {
		$defaults = [
			'rules' => $this->validates,
			'events' => $entity->exists() ? 'update' : 'create',
			'model' => get_called_class(),
			'required' => null,
			'whitelist' => null
		];
		$options += $defaults;

		if ($options['required'] === null) {
			$options['required'] = !$entity->exists();
		}
		$self = static::_object();
		$validator = $self->_classes['validator'];
		$entity->errors(false);
		$params = compact('entity', 'options');

		$implementation = function($params) use ($validator) {
			$entity = $params['entity'];
			$options = $params['options'];
			$rules = $options['rules'];
			unset($options['rules']);
			if ($whitelist = $options['whitelist']) {
				$whitelist = array_combine($whitelist, $whitelist);
				$rules = array_intersect_key($rules, $whitelist);
			}

			if ($errors = $validator::check($entity->data(), $rules, $options)) {
				$entity->errors($errors);
			}
			return empty($errors);
		};
		return Filters::run(get_called_class(), __FUNCTION__, $params, $implementation);
	}

	/**
	 * Deletes the data associated with the current `Model`.
	 *
	 * @param object $entity Entity to delete.
	 * @param array $options Options.
	 * @return boolean Success.
	 * @filter Good for executing logic for i.e. invalidating cached results.
	 */
	public function delete($entity, array $options = []) {
		$params = compact('entity', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$options = $params + $params['options'] + [
				'model' => get_called_class(),
				'type' => 'delete'
			];
			unset($options['options']);

			$query = static::_instance('query', $options);
			return static::connection()->delete($query, $options);
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
	 * @filter
	 */
	public static function update($data, $conditions = [], array $options = []) {
		$params = compact('data', 'conditions', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$options = $params + $params['options'] + [
				'model' => get_called_class(),
				'type' => 'update'
			];
			unset($options['options']);

			$query = static::_instance('query', $options);
			return static::connection()->update($query, $options);
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
	 * @filter
	 */
	public static function remove($conditions = [], array $options = []) {
		$params = compact('conditions', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$options = $params['options'] + $params + [
				'model' => get_called_class(),
				'type' => 'delete'
			];
			unset($options['options']);

			$query = static::_instance('query', $options);
			return static::connection()->delete($query, $options);
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
		$connections = $self->_classes['connections'];
		$name = isset($self->_meta['connection']) ? $self->_meta['connection'] : null;

		if ($conn = $connections::get($name)) {
			return $conn;
		}
		$class = get_called_class();
		$msg = "The data connection `{$name}` is not configured for model `{$class}`.";
		throw new ConfigException($msg);
	}

	protected static function &_object() {
		$class = get_called_class();

		if (!isset(static::$_instances[$class])) {
			static::$_instances[$class] = new $class();
			static::config();
		}
		$object = static::_initialize($class);
		return $object;
	}

	/**
	 * Iterates through relationship types to construct relation map.
	 *
	 * @todo See if this can be rewritten to be lazy.
	 * @return void
	 */
	protected static function _relationsToLoad() {
		try {
			if (!$connection = static::connection()) {
				return;
			}
		} catch (ConfigException $e) {
			return;
		}

		if (!$connection::enabled('relationships')) {
			return;
		}

		$self = static::_object();

		foreach ($self->_relationTypes as $type) {
			$self->$type = Set::normalize($self->$type);
			foreach ($self->$type as $name => $config) {
				$self->_relationsToLoad[$name] = $type;
				$fieldName = $self->_relationFieldName($type, $name);
				$self->_relationFieldNames[$fieldName] = $name;
			}
		}
	}

	protected function _relationFieldName($type, $name) {
		if (!isset($this->{$type}[$name]['fieldName'])) {
			$fieldName = static::connection()->relationFieldName($type, $name);
			$this->{$type}[$name]['fieldName'] = $fieldName;
		}
		return $this->{$type}[$name]['fieldName'];
	}

	/**
	 * Resetting the model.
	 */
	public static function reset() {
		$class = get_called_class();
		unset(static::$_instances[$class]);
	}
}

?>