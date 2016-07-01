<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source\mongo_db;

class MockResultResource extends \lithium\core\Object {

	protected $_data = [];

	protected $_autoConfig = ['data', 'name'];

	protected $_name = '';

	public $query = [];

	public function hasNext() {
		return (boolean) $this->_data;
	}

	public function getNext() {
		return array_shift($this->_data);
	}

	public function getName() {
		return $this->_name;
	}

	public function fields(array $fields = []) {
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

	public function sort(array $fields = []) {
		$this->query[__FUNCTION__] = $fields;
		return $this;
	}

	public function count() {
		return reset($this->_data);
	}
}

?>