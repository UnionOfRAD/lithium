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

/**
 * Model class
 *
 * @todo Methods: bind(), and 'bind' option for find() et al., create(), save(), delete(),
 *       validate()
 */
class Model extends \lithium\core\StaticObject {

	public $validates = array();

	public $hasOne = array();

	public $hasMany = array();

	public $belongsTo = array();

	protected static $_instances = array();

	protected $_instanceFilters = array();

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'connections' => '\lithium\data\Connections',
		'query' => '\lithium\data\model\Query',
		'record' => '\lithium\data\model\Record',
		'recordSet' => '\lithium\data\model\RecordSet',
		'validator' => '\lithium\util\Validator',
	);

	protected $_relations = array();

	protected $_relationTypes = array(
		'belongsTo' => array('class', 'key', 'conditions', 'fields'),
		'hasOne' => array('class', 'key', 'conditions', 'fields', 'dependent'),
		'hasMany' => array(
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

	protected $_schema = array();

	/**
	 * Default query parameters.
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
	 */
	protected $_finders = array();

	/**
	 * Sets default connection options and connects default finders.
	 *
	 * @param array $options
	 * @return void
	 * @todo Merge in inherited config from AppModel and other parent classes.
	 */
	public static function __init($options = array()) {
		if (($class = get_called_class()) == __CLASS__) {
			return;
		}
		$name = static::_name();
		$self = static::_instance();
		$base = get_class_vars(__CLASS__);

		$meta =  $options + $self->_meta + $base['_meta'];
		$classes = $self->_classes + $base['_classes'];

		$conn = $classes['connections']::get($meta['connection']);
		$config = ($conn) ? $conn->configureClass($class) : array();
		$defaults = array('classes' => array(), 'meta' => array(), 'finders' => array());
		$config += $defaults;

		$self->_classes = ($config['classes'] + $classes);
		$self->_meta = (compact('class', 'name') + $config['meta'] + $meta);
		$self->_meta['initialized'] = false;

		$self->_finders += $config['finders'] + $self->_findFilters();
		static::_instance()->_relations = static::_relations();
	}

	protected static function _findFilters() {
		return array(
			'first' => function($self, $params, $chain) {
				$params['options']['limit'] = 1;
				return $chain->next($self, $params, $chain)->rewind();
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
			}
		);
	}

	public static function __callStatic($method, $params) {
		$self = static::_instance();

		if ($method == 'all' || isset($self->_finders[$method])) {
			if (isset($params[0]) && !is_array($params[0])) {
				$params[0] = array('conditions' => array($self->_meta['key'] => $params[0]));
			}
			return $self::find($method, $params ? $params[0] : array());
		}

		if (preg_match('/^find(?P<type>\w+)By(?P<fields>\w+)/', $method, $match)) {
			$match['type'][0] = strtolower($match['type'][0]);
			$type = $match['type'];
			$fields = Inflector::underscore($match['fields']);
		}
	}

	/**
	 * undocumented function
	 *
	 * @param string $type
	 * @param string $options
	 * @return void
	 * @filter This method can be filtered.
	 */
	public static function find($type, $options = array()) {
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
			$connections = $options['classes']['connections'];
			$name = $meta['meta']['connection'];

			$query = new $options['classes']['query'](array('type' => 'read') + $options);
			$connection = $connections::get($name);
			$result = $connection->read($query, $options);

			if ($result === null) {
				return null;
			}
			return new $options['classes']['recordSet'](array(
				'query'    => $query,
				'model'    => $options['model'],
				'handle'   => &$connection,
				'classes'  => $options['classes'],
				'result'   => &$result,
				'exists'   => true
			));
		};
		$finder = isset($self->_finders[$type]) ? array($self->_finders[$type]) : array();
		return static::_filter(__FUNCTION__, $params, $filter, $finder);
	}

	/**
	 * Gets or sets a finder by name.  This can be an array of default query options,
	 * or a closure that accepts an array of query options, and a closure to execute.
	 *
	 * @param string $name
	 * @param string $options
	 * @return void
	 */
	public static function finder($name, $options = null) {
		$self = static::_instance();

		if (empty($options)) {
			return isset($self->_finders[$name]) ? $self->_finders[$name] : null;
		}
		$self->_finders[$name] = $options;
	}

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

	public static function key($values = array()) {
		$key = static::_instance()->_meta['key'];
		$values = is_object($values) ? $values->to('array') : $values;

		if (empty($values)) {
			return $key;
		}
		if (is_array($key)) {
			$scope = array_combine($key, array_fill(0, count($key), null));
			return array_intersect_key($values, $scope);
		}
		return isset($values[$key]) ? $values[$key] : null;
	}

	public static function relations($name = null) {
		$self = static::_instance();

		if (empty($name)) {
			return array_keys($self->_relations);
		}

		if (isset($self->_relationTypes[$name])) {
			return $self->$name;
		}

		foreach (array_keys($self->_relationTypes) as $type) {
			if (isset($self->{$type}[$name])) {
				return $self->{$type}[$name];
			}
		}
		return null;
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
		if (empty($self->_schema)) {
			$self->_schema = $self->_connection()->describe($self::meta('source'), $self->_meta);
		}
		if (is_string($field) && !empty($field)) {
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
		return (!empty($schema) && isset($schema[$field]));
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
	 * @return object Returns a new, **un-saved** record object.
	 */
	public static function create($data = array()) {
		$schema = static::schema();
		if (!empty($schema)) {
			foreach ($schema as $field => $settings) {
				if (!isset($data[$field]) && array_key_exists('default', $settings)) {
					$data[$field] = $settings['default'];
				}
			}
		}
		$class = static::_instance()->_classes['record'];
		$model = get_called_class();
		return new $class(compact('model', 'data'));
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
	 *        - 'callbacks': If `false`, all callbacks will be disabled before executing. Defaults to
	 *        `true`.
	 *        - 'validate': If `false`, validation will be skipped, and the record will be immediately
	 *        saved. Defaults to `true`.
	 *        - 'whitelist': An array of fields that are allowed to be saved to this record.
	 *
	 * @return boolean Returns `true` on a successful save operation, `false` on failure.
	 */
	public function save($record, $data = null, $options = array()) {
		$self = static::_instance();
		$classes = $self->_classes;
		$meta = array('model' => get_called_class()) + $self->_meta;

		$defaults = array('validate' => true, 'whitelist' => null, 'callbacks' => true);
		$options += $defaults + compact('classes');
		$params = compact('record', 'data', 'options');

		$filter = function($class, $params) use (&$self, $meta) {
			extract($params);

			if ($data) {
				$record->set($data);
			}

			if ($options['validate'] && !$record->validates()) {
				return false;
			}

			$queryOptions = array('type' => 'read') + $options + $meta + compact('record');
			$query = new $options['classes']['query']($queryOptions);
			$method = $record->exists() ? 'update' : 'create';

			return $self->invokeMethod('_connection')->{$method}($query, $options);
		};

		if (!$options['callbacks']) {
			return $filter->__invoke($record, $options);
		}
		return static::_filter(__FUNCTION__, $params, $filter);
	}

	public function validates($record, $options = array()) {
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

	public function delete($record, $options = array()) {
		$self = static::_instance();
		$query = $self->_classes['query'];
		$model = get_called_class();
		$params = compact('record', 'options');
		$method = __FUNCTION__;

		return static::_filter($method, $params, function($self, $params) use ($model, $query) {
			extract($params);
			$options += compact('record', 'model');
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
		foreach ((array) $method as $m) {
			if (!isset(static::_instance()->_instanceFilters[$m])) {
				static::_instance()->_instanceFilters[$m] = array();
			}
			static::_instance()->_instanceFilters[$m][] = $closure;
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
		list($class, $m) = explode('::', $method, 2);

		if (isset(static::_instance()->_instanceFilters[$m])) {
			$filters = array_merge(static::_instance()->_instanceFilters[$m], $filters);
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

		foreach ($self->_relationTypes as $type => $keys) {
			foreach (Set::normalize($self->{$type}) as $name => $options) {
				$key = Inflector::underscore($type == 'belongsTo' ? $name : $self->_meta['name']);
				$defaults = array(
					'type' => $type,
					'class' => $name,
					'fields' => true,
					'key' => $key . '_id'
				);
				$relations[$name] = (array) $options + $defaults;
			}
		}
		return $relations;
	}
}

?>