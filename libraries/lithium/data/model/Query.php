<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

/**
 * Query class
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
	 * If a `Query` is bound to a `Record` or `Document` object (i.e. for a `'create'` or
	 * `'update'` query).
	 *
	 * @var object
	 */
	protected $_binding = null;

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('type');

	/**
	 * Class constructor
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
			'table'      => null,
			'order'      => null,
			'limit'      => null,
			'page'       => null,
			'offset'     => null,
			'group'      => null,
			'comment'    => null,
			'joins'      => array()
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		unset($this->_config['type']);

		foreach ($this->_config as $key => $val) {
			if (method_exists($this, $key)) {
				$this->_config[$key] = is_array($this->_config[$key]) ? array() : null;
				$this->{$key}($val);
			}
		}
		unset($this->_config['record'], $this->_config['init']);
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
	 * Accessor method for `Query` calculate values.
	 *
	 * @param string $calculate Value for calculate config setting.
	 * @return mixed Current calculate config value.
	 */
	public function calculate($calculate = null) {
		if ($calculate) {
			$this->_config['calculate'] = $calculate;
		}
		return $this->_config['calculate'];
	}

	/**
	 * Set and get method for the model associated with the `Query`.
	 * Will also set the source table, i.e. `$this->_table`.
	 *
	 * @param string $model
	 * @return string
	 */
	public function model($model = null) {
		if ($model) {
			$this->_config['model'] = $model;
			$this->_config['table'] = $model::meta('source');
		}
		return $this->_config['model'];
	}

	/**
	 * Set and get method for conditions.
	 *
	 * If no conditions are set in query, it will ask the record for findById condition array.
	 *
	 * @param array $conditions
	 * @return array
	 */
	public function conditions($conditions = null) {
		if ($conditions) {
			$conditions = (array) $conditions;
			$this->_config['conditions'] = (array) $this->_config['conditions'];
			$this->_config['conditions'] = array_merge($this->_config['conditions'], $conditions);
		}
		return $this->_config['conditions'] ?: $this->_recordConditions();
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
	 * @return array|void
	 */
	public function fields($fields = null) {
		if ($fields === false) {
			$this->_config['fields'] = array();
		}
		$this->_config['fields'] = (array) $this->_config['fields'];

		if (is_array($fields)) {
			$this->_config['fields'] = array_merge($this->_config['fields'], $fields);
		} elseif ($fields && !isset($this->_config['fields'][$fields])) {
			$this->_config['fields'][] = $fields;
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
		if ($offset) {
			$this->_config['offset'] = intval($offset);
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
			$this->_config['page'] = intval($page) ?: 1;
			$this->offset(($this->_config['page'] - 1) * $this->_config['limit']);
		}
		return $this->_config['page'];
	}

	/**
	 * Set and get method for the query's order specification
	 *
	 * @param array|string $order
	 * @return mixed
	 */
	public function order($order = null) {
		if ($order) {
			$this->_config['order'] = $order;
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
		}
		return $this->_config['comment'];
	}

	/**
	 * Set and get method for the query's record instance
	 *
	 * @param object $binding reference to the query's current record
	 * @return object reference to the query's current record
	 */
	public function &record(&$binding = null) {
		if ($binding) {
			$this->_binding = $binding;
		}
		return $this->_binding;
	}

	/**
	 * Set and get method for the query's record's data.
	 *
	 * @param array $data if set, will set given array.
	 * @return array Empty array if no data, array of data if the record has it.
	 */
	public function data($data = array()) {
		if ($data) {
			return $this->_binding ? $this->_binding->set($data) : null;
		}
		return $this->_binding ? $this->_binding->data() : array();
	}

	/**
	 * Set and get the join queries
	 *
	 * @param query|array $joins a single query object or an array of query objects
	 * @return array of query objects
	 */
	public function join($joins = null) {
		if ($joins) {
			$this->_config['joins'] = array_merge($this->_config['joins'], (array) $joins);
		}
		return $this->_config['joins'];
	}

	/**
	 * Convert the query's properties to the data-sources' syntax and return it as an array.
	 *
	 * @param object $dataSource Instance of the data-source to use for conversion.
	 * @return array Converted properties.
	 */
	public function export($dataSource) {
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
		$results['table'] = $dataSource->name($this->_config['table']);
		return $results;
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
		}
		return isset($this->_config[$method]) ? $this->_config[$method] : null;
	}

	/**
	 * Will return a find first condition on the associated model if a record is connected.
	 * Called by conditions when it is called as a get and no condition is set.
	 *
	 * @return array ([model's primary key'] => [that key set in the record]).
	 */
	protected function _recordConditions() {
		if (!$this->_binding) {
			return;
		}
		$model = $this->_config['model'];

		if (!$model) {
			return null;
		}
		if (is_array($key = $model::key($this->_binding))) {
			return $key;
		}
		$key = $model::meta('key');
		return array($key => $this->_binding->{$key});
	}
}

?>