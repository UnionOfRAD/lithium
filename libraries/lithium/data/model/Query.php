<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

use lithium\data\Source;
use lithium\data\model\QueryException;

/**
 * The `Query` class acts as a container for all information necessary to perform a particular
 * database operation. Each `Query` object instance has a type, which is usually one of `'create'`,
 * `'read'`, `'update'` or `'delete'`.
 *
 * Because of this, `Query` objects are the primary method of communication between `Model` classes
 * and backend data sources. This helps to keep APIs abstract and flexible, since a model is only
 * required to call a single method against its backend. Since the `Query` object simply acts as a
 * structured data container, each backend can choose how to operate on the data the `Query`
 * contains. See each class method for more details on what data this class supports.
 *
 * @see lithium\data\Model
 * @see lithium\data\Source
 */
class Query extends \lithium\core\Object {

	/**
	 * The 'type' of query to be performed. This is either `'create'`, `'read'`, `'update'` or
	 * `'delete'`, and corresponds to the method to be executed.
	 *
	 * @var string
	 */
	protected $_type = null;

	/**
	 * Array containing mappings of relationship and field names, which allow database results to
	 * be mapped to the correct objects.
	 *
	 * @var array
	 */
	protected $_map = array();

	/**
	 * If a `Query` is bound to a `Record` or `Document` object (i.e. for a `'create'` or
	 * `'update'` query).
	 *
	 * @var object
	 */
	protected $_entity = null;

	/**
	 * An array of data used in a write context. Only used if no binding object is present in the
	 * `$_entity` property.
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('type', 'map');

	/**
	 * Class constructor, which initializes the default values this object supports. Even though
	 * only a specific list of configuration parameters is available by default, the `Query` object
	 * uses the `__call()` method to implement automatic getters and setters for any arbitrary piece
	 * of data.
	 *
	 * This means that any information may be passed into the constructor may be used by the backend
	 * data source executing the query (or ignored, if support is not implemented). This is useful
	 * if, for example, you wish to extend a core data source and implement custom fucntionality.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'calculate'  => null,
			'conditions' => array(),
			'fields'     => array(),
			'model'      => null,
			'alias'      => null,
			'source'     => null,
			'order'      => null,
			'offset'     => null,
			'limit'      => null,
			'page'       => null,
			'group'      => null,
			'comment'    => null,
			'joins'      => array(),
			'with'       => array(),
			'map'        => array(),
			'whitelist'  => array(),
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		unset($this->_config['type']);

		foreach ($this->_config as $key => $val) {
			if (method_exists($this, $key) && $val !== null) {
				$this->_config[$key] = is_array($this->_config[$key]) ? array() : null;
				$this->{$key}($val);
			}
		}
		if ($list = $this->_config['whitelist']) {
			$this->_config['whitelist'] = array_combine($list, $list);
		}
		if ($this->_config['with']) {
			$this->_associate($this->_config['with']);
		}
		if ($this->_entity && !$this->_config['model']) {
			$this->model($this->_entity->model());
		}
		unset($this->_config['entity'], $this->_config['init'], $this->_config['with']);
	}

	/**
	 * Get method of type, i.e. 'read', 'update', 'create', 'delete'.
	 *
	 * @return string
	 */
	public function type() {
		return $this->_type;
	}

	/**
	 * Generates a schema map of the query's result set, where the keys are fully-namespaced model
	 * class names, and the values are arrays of field names.
	 *
	 * @return array
	 */
	public function map($map = null) {
		if ($map !== null) {
			$this->_map = $map;
			return $this;
		}
		return $this->_map;
	}

	/**
	 * Accessor method for `Query` calculate values.
	 *
	 * @param string $calculate Value for calculate config setting.
	 * @return mixed Current calculate config value.
	 */
	public function calculate($calculate = null) {
		if ($calculate) {
			$this->_config['calculate'] = $calculate;
			return $this;
		}
		return $this->_config['calculate'];
	}

	/**
	 * Set and get method for the model associated with the `Query`.
	 * Will also set the source table, i.e. `$this->_config['source']`.
	 *
	 * @param string $model
	 * @return string
	 */
	public function model($model = null) {
		if ($model) {
			$this->_config['model'] = $model;
			$this->_config['source'] = $model::meta('source');
			$this->_config['name'] = $model::meta('name');
			return $this;
		}
		return $this->_config['model'];
	}

	/**
	 * Set and get method for conditions.
	 *
	 * If no conditions are set in query, it will ask the bound entity for condition array.
	 *
	 * @param mixed $conditions String or array to append to existing conditions.
	 * @return array Returns an array of all conditions applied to this query.
	 */
	public function conditions($conditions = null) {
		if ($conditions) {
			$conditions = (array) $conditions;
			$this->_config['conditions'] = (array) $this->_config['conditions'];
			$this->_config['conditions'] = array_merge($this->_config['conditions'], $conditions);
			return $this;
		}
		return $this->_config['conditions'] ?: $this->_entityConditions();
	}

	/**
	 * Set, get or reset fields option for query.
	 *
	 * Usage:
	 * {{{
	 *	// to add a field
	 *   $query->fields('created');
	 * }}}
	 * {{{
	 *	// to add several fields
	 *   $query->fields(array('title','body','modified'));
	 * }}}
	 * {{{
	 *	// to reset fields to none
	 *   $query->fields(false);
	 *   // should be followed by a 2nd call to fields with required fields
	 * }}}
	 *
	 * @param mixed $fields string, array or `false`
	 * @param boolean $overwrite If `true`, existing fields will be removed before adding `$fields`.
	 * @return array Returns an array containing all fields added to the query.
	 */
	public function fields($fields = null, $overwrite = false) {
		if ($fields === false || $overwrite) {
			$this->_config['fields'] = array();
		}
		$this->_config['fields'] = (array) $this->_config['fields'];

		if (is_array($fields)) {
			$this->_config['fields'] = array_merge($this->_config['fields'], $fields);
		} elseif ($fields && !isset($this->_config['fields'][$fields])) {
			$this->_config['fields'][] = $fields;
		}
		if ($fields !== null) {
			return $this;
		}
		return $this->_config['fields'];
	}

	/**
	 * Set and get method for query's limit of amount of records to return
	 *
	 * @param integer $limit
	 * @return integer
	 */
	public function limit($limit = null) {
		if ($limit) {
			$this->_config['limit'] = intval($limit);
			return $this;
		}
		return $this->_config['limit'];
	}

	/**
	 * Set and get method for query's offset, i.e. which records to get
	 *
	 * @param integer $offset
	 * @return integer
	 */
	public function offset($offset = null) {
		if ($offset !== null) {
			$this->_config['offset'] = intval($offset);
			return $this;
		}
		return $this->_config['offset'];
	}

	/**
	 * Set and get method for page, in relation to limit, of which records to get
	 *
	 * @param integer $page
	 * @return integer
	 */
	public function page($page = null) {
		if ($page) {
			$this->_config['page'] = $page = (intval($page) ?: 1);
			$this->offset(($page - 1) * $this->_config['limit']);
			return $this;
		}
		return $this->_config['page'];
	}

	/**
	 * Set and get method for the query's order specification.
	 *
	 * @param array|string $order
	 * @return mixed
	 */
	public function order($order = null) {
		if ($order) {
			$this->_config['order'] = $order;
			return $this;
		}
		return $this->_config['order'];
	}

	/**
	 * Set and get method for the `Query` group config setting.
	 *
	 * @param string $group New group config setting.
	 * @return mixed Current group config setting.
	 */
	public function group($group = null) {
		if ($group) {
			$this->_config['group'] = $group;
			return $this;
		}
		return $this->_config['group'];
	}

	/**
	 * Set and get method for current query's comment.
	 *
	 * Comment will have no effect on query, but will be passed along so data source can log it.
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment = null) {
		if ($comment) {
			$this->_config['comment'] = $comment;
			return $this;
		}
		return $this->_config['comment'];
	}

	/**
	 * Set and get method for the query's entity instance.
	 *
	 * @param object $entity Reference to the query's current entity object.
	 * @return object Reference to the query's current entity object.
	 */
	public function &entity(&$entity = null) {
		if ($entity) {
			$this->_entity = $entity;
			return $this;
		}
		return $this->_entity;
	}

	/**
	 * Set and get method for the query's record's data.
	 *
	 * @param array $data if set, will set given array.
	 * @return array Empty array if no data, array of data if the record has it.
	 */
	public function data($data = array()) {
		$bind =& $this->_entity;

		if ($data) {
			$bind ? $bind->set($data) : $this->_data = array_merge($this->_data, $data);
			return $this;
		}
		$data = $bind ? $bind->data() : $this->_data;
		return ($list = $this->_config['whitelist']) ? array_intersect_key($data, $list) : $data;
	}

	/**
	 * Set and get the join queries
	 *
	 * @param string $name Optional name of join. Unless two parameters are passed, this parameter
	 *               is regonized as `$join`.
	 * @param object|string $join A single query object or an array of query objects
	 * @return array of query objects
	 */
	public function join($name = null, $join = null) {
		if ($name && !$join) {
			$join = $name;
			$name = null;
		}
		if ($join) {
			$name ? $this->_config['joins'][$name] = $join : $this->_config['joins'][] = $join;
			return $this;
		}
		return $this->_config['joins'];
	}

	/**
	 * Convert the query's properties to the data sources' syntax and return it as an array.
	 *
	 * @param object $dataSource Instance of the data source to use for conversion.
	 * @param array $options Options to use when exporting the data.
	 * @return array Returns an array containing a data source-specific representation of a query.
	 */
	public function export(Source $dataSource, array $options = array()) {
		$defaults = array('data' => array());
		$options += $defaults;

		$keys = array_keys($this->_config);
		$methods = $dataSource->methods();
		$results = array();

		$apply = array_intersect($keys, $methods);
		$copy = array_diff($keys, $apply);

		foreach ($apply as $item) {
			$results[$item] = $dataSource->{$item}($this->{$item}(), $this);
		}
		foreach ($copy as $item) {
			$results[$item] = $this->_config[$item];
		}
		$entity =& $this->_entity;
		$data = $entity ? $entity->export($dataSource, $options['data']) : $this->_data;
		$data = ($list = $this->_config['whitelist']) ? array_intersect_key($data, $list) : $data;
		$results = compact('data') + $results;

		$results['type'] = $this->_type;
		$results['source'] = $dataSource->name($this->_config['source']);
		$created = array('fields', 'values');

		if (is_array($results['fields']) && array_keys($results['fields']) == $created) {
			$results = $results['fields'] + $results;
		}
		return $results;
	}

	public function schema($field = null) {
		if (is_array($field)) {
			$this->_config['schema'] = $field;
			return $this;
		}

		if (isset($this->_config['schema'])) {
			$schema = $this->_config['schema'];

			if ($field) {
				return isset($schema[$field]) ? $schema[$field] : null;
			}
			return $schema;
		}

		if ($model = $this->model()) {
			return $model::schema($field);
		}
	}

	public function alias($alias = null) {
		if ($alias) {
			$this->_config['alias'] = $alias;
			return $this;
		}
		if (!$this->_config['alias'] && ($model = $this->_config['model'])) {
			$this->_config['alias'] = $model::meta('name');
		}
		return $this->_config['alias'];
	}

	/**
	 * Gets a custom query field which does not have an accessor method.
	 *
	 * @param string $method Query part.
	 * @param string $params Query parameters.
	 * @return mixed Returns the value as set in the `Query` object's constructor.
	 */
	public function __call($method, $params = array()) {
		if ($params) {
			$this->_config[$method] = current($params);
			return $this;
		}
		return isset($this->_config[$method]) ? $this->_config[$method] : null;
	}

	/**
	 * Will return a find first condition on the associated model if a record is connected.
	 * Called by conditions when it is called as a get and no condition is set.
	 *
	 * @return array ([model's primary key'] => [that key set in the record]).
	 */
	protected function _entityConditions() {
		if (!$this->_entity || !($model = $this->_config['model'])) {
			return;
		}
		if (is_array($key = $model::key($this->_entity->data()))) {
			return $key;
		}
		$key = $model::meta('key');
		$val = $this->_entity->{$key};
		return $val ? array($key => $val) : array();
	}

	protected function _associate($related) {
		if (!$model = $this->model()) {
			return;
		}
		$queryClass = get_class($this);

		foreach ((array) $related as $name => $config) {
			if (is_int($name)) {
				$name = $config;
				$config = array();
			}
			if (!$relation = $model::relations($name)) {
				throw new QueryException("Related model not found");
			}
			$config += $relation->data();
		}
	}
}

?>