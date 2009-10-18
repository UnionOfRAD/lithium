<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util;

class Collection extends \lithium\core\Object implements \ArrayAccess, \Iterator, \Countable {

	protected $_items = array();

	protected $_valid = false;

	protected $_autoConfig = array('items');

	protected function _init() {
		parent::_init();
		unset($this->_config['items']);
	}

	/**
	 * Handles dispatching of methods against all items in the collection.
	 *
	 * @param string $method 
	 * @param array $parameters 
	 * @param array $options Specifies options for how to run the given method against the object
	 *              collection. The available options are:
	 *              - 'merge': Used primarily if the method being invoked returns an array.  If
	 *                set to true, merges all results arrays into one.
	 *              - 'collect': If true, the results of this method call will be returned wrapped
	 *                in a new Collection object or subclass.
	 * @todo Implement filtering
	 * @return mixed
	 */
	public function invoke($method, $parameters = array(), $options = array()) {
		$defaults = array('merge' => false, 'collect' => false);
		$options += $defaults;
		$results = array();
		$isCore = null;

		foreach ($this->_items as $key => $value) {
			if (is_null($isCore)) {
				$isCore = (method_exists(current($this->_items), 'invokeMethod'));
			}

			if ($isCore) {
				$result = $this->_items[$key]->invokeMethod($method, $parameters);
			} else {
				$result = call_user_func_array(array(&$this->_items[$key], $method), $parameters);
			}

			if (!empty($options['merge'])) {
				$results = array_merge($results, $result);
			} else {
				$results[$key] = $result;
			}
		}

		if ($options['collect']) {
			$class = get_class($this);
			$results = new $class(array('items' => $results));
		}
		return $results;
	}

	/**
	 * Hook to handle dispatching of methods against all items in the collection.
	 *
	 * @param string $method 
	 * @param array $parameters 
	 * @return mixed
	 */
	public function __call($method, $parameters = array()) {
		return $this->invoke($method, $parameters);
	}

	/**
	 * Converts the Collection object to another type of object, or a simple type such as an array.
	 *
	 * @param string $format 
	 * @return mixed
	 */
	public function to($format, $options = array()) {
		switch ($format) {
			case 'array':
				$result = array();

				foreach ($this->_items as $key => $value) {
					if (is_object($value)) {
						if (method_exists($value, 'to')) {
							$value = $value->to('array');
						}
						if (!is_array($value)) {
							$value = get_object_vars($value);
						}
					}
					$result[$key] = $value;
				}
			break;
		}
		return $result;
	}

	public function find($filter, $options = array()) {
		$defaults = array('collect' => true);
		$options += $defaults;
		$items = array_filter($this->_items, $filter);

		if ($options['collect']) {
			$class = get_class($this);
			$items = new $class(compact('items'));
		}
		return $items;
	}

	/**
	 * Returns the first non-empty value in a collection after a filter is applied.
	 *
	 * @param closure $filter The filter through which collection values will be passed. If the
	 *        return value of this function is non-empty, it will be returned as the result of the
	 *        method call.
	 * @return mixed Returns the first non-empty collection value returned from `$filter`.
	 */
	public function first($filter) {
		foreach ($this->_items as $item) {
			if ($value = $filter($item)) {
				return $value;
			}
		}
	}

	public function each($filter) {
		$this->_items = array_map($filter, $this->_items);
		return $this;
	}

	public function map($filter, $options = array()) {
		$defaults = array('collect' => true);
		$options += $defaults;
		$items = array_map($filter, $this->_items);

		if ($options['collect']) {
			$class = get_class($this);
			return new $class(compact('items'));
		}
		return $items;
	}

	public function offsetExists($offset) {
		return isset($this->_items[$offset]);
	}

	public function offsetGet($offset) {
		return $this->_items[$offset];
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			return $this->_items[] = $value;
		}
		return $this->_items[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->_items[$offset]);
	}

	public function rewind() {
		$this->_valid = (reset($this->_items) !== false);
		return current($this->_items);
	}

	public function end() {
		$this->_valid = (end($this->_items) !== false);
		return current($this->_items);
	}

	public function valid() {
		return $this->_valid;
	}

	public function current() {
		return current($this->_items);
	}

	public function key() {
		return key($this->_items);
	}

	public function prev() {
		if (!prev($this->_items)) {
			end($this->_items);
		}
		return current($this->_items);
	}

	public function next() {
		$this->_valid = (next($this->_items) !== false);
		return current($this->_items);
	}

	public function append($value) {
		is_object($value) ? $this->_items[] =& $value : $this->_items[] = $value;
	}

	public function count() {
		return count($this->_items);
	}

	public function keys() {
		return array_keys($this->_items);
	}
}

?>