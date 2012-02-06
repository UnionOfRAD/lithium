<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\mongo_db;

class MockResult extends \lithium\data\source\mongo_db\Result {

	public $query = array();

	protected $_autoConfig = array('data', 'name');

	protected $_name = '';

	protected $_data = array(
		array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar'),
		array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo'),
		array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib')
	);

	public function getName() {
		return $this->_name;
	}

	public function hasNext() {
		if (!is_array($this->_data)) {
			return false;
		}
		return key($this->_data) !== null && key($this->_data) < count($this->_data);
	}

	public function getNext() {
		$result = current($this->_data);
		next($this->_data);
		return $result;
	}

	public function next() {
		return $this->_next();
	}

	public function __call($method, array $params) {
		return $this;
	}

	protected function _close() {
	}

	protected function _next() {
		$result = current($this->_data) ?: null;
		next($this->_data);
		return $result;
	}

	public function fields(array $fields = array()) {
		$this->query[__FUNCTION__] = $fields;
		return $this;
	}

	public function limit($num) {
		$this->query[__FUNCTION__] = $num;
		return $this;
	}

	public function skip($num) {
		$this->query[__FUNCTION__] = $num;
		return $this;
	}

	public function sort(array $fields = array()) {
		$this->query[__FUNCTION__] = $fields;
		return $this;
	}

	public function count() {
		return reset($this->_data);
	}
}

?>