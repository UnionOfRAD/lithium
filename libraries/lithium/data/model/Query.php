<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

class Query extends \lithium\core\Object {

	protected $_type = null;

	protected $_model = null;

	protected $_table = null;

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

	protected function _init() {
		foreach ($this->_config as $key => $val) {
			if (method_exists($this, $key)) {
				$this->{$key}($val);
			}
		}
	}

	public function type() {
		return $this->_type;
	}

	public function model($model = null) {
		if (empty($model)) {
			return $this->_model;
		}
		$this->_model = $model;
		$this->_table = $model::meta('source');
	}

	public function conditions($conditions = null) {
		if (empty($conditions)) {
			return $this->_conditions ?: $this->_recordConditions();
		}
		$this->_conditions = array_merge($this->_conditions, (array)$conditions);
	}

	public function fields($fields = null) {
		if (empty($fields)) {
			return $this->_fields;
		}

		if (is_array($fields)) {
			$this->_fields = array_merge($this->_fields, $fields);
		} else {
			$this->_fields[] = $fields;
		}
	}

	public function limit($limit = null) {
		if (empty($limit)) {
			return $this->_limit;
		}
		$this->_limit = intval($limit);
	}

	public function offset($offset = null) {
		if (empty($offset)) {
			return $this->_offset;
		}
		$this->_offset = intval($offset);
	}

	public function page($page = null) {
		if (empty($page)) {
			return $this->_page;
		}
		$this->_page = intval($page) ?: 1;
		$this->offset(($this->_page - 1) * $this->_limit);
	}

	public function order($order = null) {
		if (empty($order)) {
			return $this->_order;
		}
		$this->_order = $order;
	}

	public function comment($comment = null) {
		if (empty($comment)) {
			preg_match('/^\s*\/\*\s(.+)\s\*\/$/', $this->_comment, $match);
			return isset($match[1]) ? $match[1] : null;
		}
		$this->_comment = " /* {$comment} */";
	}

	public function &record(&$record = null) {
		if (empty($record)) {
			return $this->_record;
		}
		$this->_record = $record;
		return $this->_record;
	}

	public function data($data = array()) {
		if ($data) {
			return $this->_record ? $this->_record->set($data) : null;
		}
		return $this->_record ? $this->_record->data() : array();
	}

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