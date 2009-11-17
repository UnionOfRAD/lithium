<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

class Query extends \lithium\core\Object {

	protected $_type = null;

	protected $_model = null;

	protected $_table = null;

	protected $_record = null;

	/**
	 * The set of conditions that define the query's scope.
	 *
	 * @var array
	 * @see lithium\data\model\Query::conditions()
	 */
	protected $_conditions = array();

	protected $_fields = array();

	protected $_order = null;

	protected $_limit = null;

	protected $_offset = null;

	protected $_page = null;

	protected $_joins = array();

	protected $_comment = null;

	protected $_autoCondig = array("type");

	protected function _init() {
		foreach ($this->_config as $key => $val) {
			if (method_exists($this, $key)) {
				$this->{$key}($val);
			}
		}
	}

	/**
	* Get method of type, ie 'read', 'update', 'create', 'delete'
	*
	* @return string
	*/
	public function type() {
		return $this->_type;
	}

	/**
	* Set and get method for the model associated with the Query.
	* Will also set the source table, ie : $this->_table
	*
	* @param string $model
	* @return string
	*/
	public function model($model = null) {
		if (empty($model)) {
			return $this->_model;
		}
		$this->_model = $model;
		$this->_table = $model::meta('source');
	}

	/**
	* Set and get method for conditions
	* If no conditions is set in query, it will ask the record for findById condition array
	*
	* @param array $conditions
	* @return array
	*/
	public function conditions($conditions = null) {
		if (empty($conditions)) {
			return $this->_conditions ?: $this->_recordConditions();
		}
		$this->_conditions = array_merge($this->_conditions, (array)$conditions);
	}

	/**
	* Set, get or reset fields option for query.
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
	*   // should be follwed by a 2nd call to fields with required fields
	* }}}
	*
	* @param mixed $limit string, array or `false`
	* @return array
	*/
	public function fields($fields = null) {
		if ($fields === false) {
			$this->_fields = array();
			return;
		}
		if (empty($fields)) {
			return $this->_fields;
		}

		if (is_array($fields)) {
			$this->_fields = array_merge($this->_fields, $fields);
		} else {
			if (!isset($this->_fields[$fields])) {
				$this->_fields[] = $fields;
			}
		}
	}

	/**
	* Set and get method for query's limit of amount of records to return
	*
	* @param int $limit
	* @return int
	*/
	public function limit($limit = null) {
		if (empty($limit)) {
			return $this->_limit;
		}
		$this->_limit = intval($limit);
	}

	/**
	* Set and get method for query's offset, ie which records to get
	*
	* @param int $offset
	* @return int
	*/
	public function offset($offset = null) {
		if (empty($offset)) {
			return $this->_offset;
		}
		$this->_offset = intval($offset);
	}

	/**
	* Set and get method for page, in relation to limit, of which records to get
	*
	* @param int $page
	* @return int
	*/
	public function page($page = null) {
		if (empty($page)) {
			return $this->_page;
		}
		$this->_page = intval($page) ?: 1;
		$this->offset(($this->_page - 1) * $this->_limit);
	}

	/**
	* Set and get method for the query's order specification
	*
	* @param mixed $order array or string
	* $return mixed
	*/
	public function order($order = null) {
		if (empty($order)) {
			return $this->_order;
		}
		$this->_order = $order;
	}

	/**
	* Set and get method for current query's comment
	* Comment will have no effect on query, but will be passed along so datasource
	* can log it.
	*
	* @param string
	* @return string
	*/
	public function comment($comment = null) {
		if (empty($comment)) {
			preg_match('/^\s*\/\*\s(.+)\s\*\/$/', $this->_comment, $match);
			return isset($match[1]) ? $match[1] : null;
		}
		$this->_comment = " /* {$comment} */";
	}

	/**
	* Set and get method for the query's record instance
	*
	* @param object reference to the query's current record
	* @return object reference to the query's current record
	*/
	public function &record(&$record = null) {
		if (empty($record)) {
			return $this->_record;
		}
		$this->_record = $record;
		return $this->_record;
	}

	/**
	* Set and get method for the query's record's data.
	*
	* @param array $data if set, will set given array
	* @return empty array if no data, array of data if the record has it
	*/
	public function data($data = array()) {
		if ($data) {
			return $this->_record ? $this->_record->set($data) : null;
		}
		return $this->_record ? $this->_record->data() : array();
	}

	/**
	* Convert the query's properties to the datasources syntax and return it as an array
	*
	* @param object instance of the datasource to use for conversion
	* @return array of converted properties
	*/
	public function export($dataSource) {
		$results = array();

		foreach (array('conditions', 'fields', 'order', 'limit') as $item) {
			$results[$item] = $dataSource->{$item}($this->{$item}(), $this);
		}
		$results['table'] = $dataSource->name($this->_table);

		foreach (array('comment', 'model', 'page') as $item) {
			$results[$item] = $this->{'_' . $item};
		}
		return $results;
	}

	/**
	* Will retrun a find first condition on the associated model if a record is connected.
	* Called by conditions when it is called as a get and no condition is set.
	*
	* @return array([model's primary key'] => [that key set in the record])
	*/
	protected function _recordConditions() {
		if (!$this->_record) {
			return;
		}
		$model = $this->_model;
		$key = $model::meta('key');
		return array($key => $this->_record->{$key});
	}
}

?>