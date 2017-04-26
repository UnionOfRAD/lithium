<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source\mongo_db;

class MockResultResource extends \lithium\core\Object {

	protected $_data = array();

	protected $_autoConfig = array('data', 'name');

	protected $_name = '';

	public $query = array();

	public function hasNext() {
		return (boolean) $this->_data;
	}

	public function getNext() {
		return array_shift($this->_data);
	}

	public function getName() {
		return $this->_name;
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