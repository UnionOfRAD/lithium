<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use \lithium\util\Set;
use \lithium\util\Inflector;

/**
 * Model class
 *
 * @package default
 * @todo Methods: bind(), and 'bind' option for find() et al., create(), save(), delete(),
 * validate()
 */
class Model extends \lithium\core\StaticObject {

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
		'query' => '\lithium\data\model\Query',
		'record' => '\lithium\data\model\Record',
		'validator' => '\lithium\util\Validator',
		'recordSet' => '\lithium\data\model\RecordSet',
		'connections' => '\lithium\data\Connections'
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

	protected $_meta = array(
		'key' => 'id',
		'name' => null,
		'class' => null,
		'title' => null,
		'source' => null,
		'prefix' => null,
		'connection' => 'default'
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
	 * @return void
	 * @todo Merge in inherited config from AppModel and other parent classes.
	 */
	public static function __init($options = array()) {
		if (($class = get_called_class()) == __CLASS__) {
			return;
		}
		$self = static::_instance();

		$base = get_class_vars(__CLASS__);
		$meta = $self->_meta + $base['_meta'];
		$classes = $self->_classes + $base['_classes'];

		$conn = $classes['connections'];
		$backendDefaults = array('classes' => array(), 'meta' => array(), 'finders' => array());
		$backendConfig = $conn::get($meta['connection'])->configureClass($class) + $backendDefaults;

		$self->_classes = ($self->_classes + $backendConfig['classes'] + $base['_classes']);
		$meta = ($self->_meta + $backendConfig['meta'] + $base['_meta']);
		$self->_meta = ($options + compact('class') + array('name' => static::_name()) + $meta);

		if ($self->_meta['source'] === null) {
			$self->_meta['source'] = Inflector::tableize($self->_meta['name']);
		}

		$titleKeys = array('title', 'name', $self->_meta['key']);
		$self->_meta['title'] = $self->_meta['title'] ?: static::hasField($titleKeys);

		$self->_finders += $backendConfig['finders'] + array(
			'first' => function($self, $params, $chain) {
				$params['options']['limit'] = 1;
				return $chain->next($self, $params, $chain)->rewind();
			}
		);
		static::_instance()->_relations = static::_relations();
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

		if (is_numeric($type) || $classes['validator']::isUuid($type)) {
			$options['conditions'] = array($self->_meta['key'] => $type);
			$type = 'first';
		}

		$options += ((array)$self->_query + (array)$defaults + compact('classes'));
		$meta = array('meta' => $self->_meta, 'name' => get_called_class());
		$params = compact('type', 'options');

		$filter = function($self, $params, $chain) use ($meta) {
			$options = $params['options'] + array('model' => $meta['name']);
			$connections = $options['classes']['connections'];
			$name = $meta['meta']['connection'];

			$query = new $options['classes']['query']($options);
			$connection = $connections::get($name);

			return new $options['classes']['recordSet'](array(
				'query'    => $query,
				'model'    => $options['model'],
				'handle'   => &$connection,
				'classes'  => $options['classes'],
				'result'   => $connection->read($query, $options)
			));
		};
		$finder = isset($self->_finders[$type]) ? array($self->_finders[$type]) : array();
		return static::_filter(__METHOD__, $params, $filter, $finder);
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
			return $self->_meta;
		}
		if (is_array($key)) {
			$self->_meta = $key + $self->_meta;
		}
		if (is_array($key) || empty($key)) {
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

		if (array_key_exists($name, $self->_relationTypes)) {
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
	 *        one field. Otherwise, an array with containing all fields is returned.
	 * @return array
	 */
	public static function schema($field = null) {
		$self = static::_instance();

		if (empty($self->_schema)) {
			$name = $self->_meta['connection'];
			$conn = $self->_classes['connections'];
			$self->_schema = $conn::get($name)->describe($self->_meta['source'], $self->_meta);
		}
		if (is_string($field) && !empty($field)) {
			return isset($self->_schema[$field]) ? $self->_schema[$field] : null;
		}
		return $self->_schema;
	}

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
	 *
	 * {{{$post = Post::create(array("title" => "New post"));
	 * echo $post->title; // echoes "New post"
	 * $post->save();}}}
	 *
	 * @param array $data Any data that this record should be populated with initially.
	 * @return object Returns a new, un-saved record object.
	 */
	public static function create($data = array()) {
		$class = static::_instance()->_classes['record'];
		$model = get_called_class();
		return new $class(compact('model', 'data'));
	}

	/**
	 * An instance method (called on record and document objects) to create or update the record or
	 * document in the database that corresponds to `$record`. For example:
	 *
	 * {{{$post = Post::create();
	 * $post->title = "My post";
	 * $post->save(array('validate' => false));}}}
	 *
	 * @param object $record The record or document object to be saved in the database.
	 * @param array $options Options:
	 *
	 *        -'force': If `true`, forces the record to write to the database, even if no fields are
	 *         reported as having been modified. Defaults to `false`.
	 *        -'validate': If `false`, validation will be skipped, and the record will be
	 *         immediately saved. Defaults to `true`.
	 *        -'whitelist': An array of fields that are allowed to be saved to this record.
	 *        -'callbacks': If `false`, all callbacks will be disabled before executing. Defaults to
	 *         `true`.
	 * @return boolean Returns `true` on a successful save operation, `false` on failure.
	 */
	public function save($record, $options = array()) {
		$self = static::_instance();
		$classes = $self->_classes;
		$meta = array('model' => get_called_class()) + $self->_meta;

		$defaults = array(
			'force' => false,
			'validate' => true,
			'whitelist' => null,
			'callbacks' => true
		);
		$options += $defaults + compact('classes');
		$params = compact('record', 'options');

		$filter = function($self, $params) use ($meta) {
			extract($params);

			if ($options['validate'] && !$record->validates()) {
			}

			$connections = $options['classes']['connections'];
			$query = new $options['classes']['query']($options + $meta + compact('record'));
			$name = $meta['connection'];

			if (!$record->exists()) {
				return $connections::get($name)->create($query, $options);
			}
			return $connections::get($name)->update($query, $options);
		};

		if (!$options['callbacks']) {
			return $filter->__invoke($record, $options);
		}
		return static::_filter(__METHOD__, $params, $filter);
	}

	public function validates($record, $options = array()) {
		return static::_filter(__METHOD__, compact('record', 'options'), function($self, $params) {
		});
	}

	public function delete($record, $options = array()) {
		$self = static::_instance();
		$classes = $self->_classes;

		$meta = array('model' => get_called_class()) + $self->_meta;
		$params = compact('record', 'options');

		return static::_filter(__METHOD__, $params, function($self, $params) use ($meta, $classes) {
			extract($params);
			$options += array('model' => $meta['model']) + compact('record');
			$connections = $classes['connections'];
			$name = $meta['connection'];

			$query = new $classes['query']($options);
			$connection = $connections::get($name);
			return $connection->delete($query, $options);
		});
	}

	protected static function _name() {
		static $name;
		return $name ?: $name = join('', array_slice(explode("\\", get_called_class()), -1));
	}

	/**
	 * Wraps `StaticObject::applyFilter()` to account for object instances.
	 *
	 * @see lithium\core\StaticObject::applyFilter()
	 */
	public static function applyFilter($method, $closure = null) {
		foreach ((array)$method as $m) {
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
	 */
	protected static function _filter($method, $params, $callback, $filters = array()) {
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
				$relations[$name] = (array)$options + $defaults;
			}
		}
		return $relations;
	}
}

?>