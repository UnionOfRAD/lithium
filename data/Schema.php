<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

class Schema extends \lithium\core\Object implements \ArrayAccess {

	protected $_fields = array();

	protected $_meta = array();

	protected $_locked = false;

	protected $_autoConfig = array('fields', 'meta', 'locked');

	public function fields($name = null, $key = null) {
		if (!$name) {
			return $this->_fields;
		}
		$field = isset($this->_fields[$name]) ? $this->_fields[$name] : null;

		if ($field && $key) {
			return isset($field[$key]) ? $field[$key] : null;
		}
		return $field;
	}

	public function meta($name = null) {
		if (!$name) {
			return $this->_meta;
		}
		return isset($this->_meta[$name]) ? $this->_meta[$name] : null;
	}

	public function has($field) {
		if (is_string($field)) {
			return isset($this->_fields[$field]);
		}
		if (is_array($field)) {
			return array_intersect($field, array_keys($this->_fields)) == $field;
		}
	}

	/**
	 * Detects properties of a field, i.e. if it supports arrays.
	 *
	 * @param string $condition 
	 * @param string $field 
	 * @return void
	 */
	public function is($condition, $field) {
		if (!isset($this->_fields[$field])) {
			return null;
		}
		return isset($this->_fields[$field][$condition]) && $this->_fields[$field][$condition];
	}

	public function type($field) {
		if (!isset($this->_fields[$field]['type'])) {
			return null;
		}
		return $this->_fields[$field]['type'];
	}

	public function cast($object, $data) {
		return $data;
	}

	/**
	 * Appends additional fields to the schema. Will not overwrite existing fields if any conflicts
	 * arise.
	 *
	 * @param array $schema New schema data.
	 * @return void
	 */
	public function append(array $fields) {
		if ($this->_locked) {
			throw new Exception("Schema cannot be modified.");
		}
		$this->_fields += $fields;
	}

	public function offsetGet($key) {
		return $this->fields($key);
	}

	public function offsetSet($key, $value) {
		if ($this->_locked) {
			throw new Exception("Schema cannot be modified.");
		}
		$this->_fields[$key] = $value;
	}

	public function offsetExists($key) {
		return isset($this->_fields[$key]);
	}

	public function offsetUnset($key) {
		unset($this->_fields[$key]);
	}
}

?>