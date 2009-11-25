<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util;

class Collection extends \lithium\core\Object implements \ArrayAccess, \Iterator, \Countable {

	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $_items = array();

	/**
	 * Indicates whether the current position is valid or not.
	 *
	 * @var boolean
	 * @see lithium\util\Collection::valid()
	 */
	protected $_valid = false;

	protected $_classes = array(
		'media' => '\lithium\http\Media'
	);

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
	 *              - `'merge'`: Used primarily if the method being invoked returns an array.  If
	 *                set to `true`, merges all results arrays into one.
	 *              - `'collect'`: If `true`, the results of this method call will be returned
	 *              wrapped in a new Collection object or subclass.
	 * @todo Implement filtering.
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
	 * @param string $format Currently only `'array'` is supported.
	 * @param $options Options for converting this collection:
	 *        - 'internal': Boolean indicating whether the current internal representation of the
	 *          collection should be exported. Defaults to `false`, which uses the standard iterator
	 *          interfaces. This is useful for exporting record sets, where records are lazy-loaded,
	 *          and the collection must be iterated in order to fetch all objects.
	 * @return mixed The converted object.
	 */
	public function to($format, $options = array()) {
		$defaults = array('internal' => false);
		$options += $defaults;
		$state = $options['internal'] ? $this->_items : $this;
		$result = null;

		switch ($format) {
			case 'array':
				$result = array();

				foreach ($state as $key => $value) {
					if (is_object($value)) {
						switch (true) {
							case method_exists($value, 'to'):
								$value = $value->to('array');
							break;
							case (is_object($value) && $vars = get_object_vars($value)):
								$value = $vars;
							break;
							case method_exists($value, '__toString'):
								$value = $value->__toString();
							break;
						}
					}
					$result[$key] = $value;
				}
				return $result;
			default:
				$media = $this->_classes['media'];

				if (in_array($format, $media::types())) {
					return $media::encode($format, $this->to('array', $options));
				}
			break;
		}
	}

	/**
	 * Filters a copy of the items in the collection.
	 *
	 * @param callback $filter Callback to use for filtering.
	 * @param array $options The available options are:
	 *              - `'collect'`: If `true`, the results will be returned wrapped
	 *              in a new Collection object or subclass.
	 * @return array|object The filtered items.
	 */
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
	 * Returns the first non-empty value in the collection after a filter is applied, or rewinds the
	 * collection and returns the first value.
	 *
	 * @param callback $filter A closure through which collection values will be
	 *                 passed. If the return value of this function is non-empty,
	 *                 it will be returned as the result of the method call. If `null`, the
	 *                 collection is rewound (see `rewind()`) and the first item is returned.
	 * @return mixed Returns the first non-empty collection value returned from `$filter`.
	 * @see lithium\util\Collection::rewind()
	 */
	public function first($filter = null) {
		if (empty($filter)) {
			return $this->rewind();
		}

		foreach ($this->_items as $item) {
			if ($value = $filter($item)) {
				return $value;
			}
		}
	}

	/**
	 * Applies a callback to all items in the collection.
	 *
	 * @param callback $filter The filter to apply.
	 * @return object This collection instance.
	 */
	public function each($filter) {
		$this->_items = array_map($filter, $this->_items);
		return $this;
	}

	/**
	 * Applies a callback to a copy of all items in the collection
	 * and returns the result.
	 *
	 * @param callback $filter The filter to apply.
	 * @param array $options The available options are:
	 *              - `'collect'`: If `true`, the results will be returned wrapped
	 *              in a new Collection object or subclass.
	 * @return array|object The filtered items.
	 */
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

	/**
	 * Checks whether or not an offset exists.
	 *
	 * @param string $offset An offset to check for.
	 * @return boolean `true` if offset exists, `false` otherwise.
	 */
	public function offsetExists($offset) {
		return isset($this->_items[$offset]);
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param string $offset The offset to retrieve.
	 * @return mixed Value at offset.
	 */
	public function offsetGet($offset) {
		return $this->_items[$offset];
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @param string $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 * @return mixed The value which was set.
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			return $this->_items[] = $value;
		}
		return $this->_items[$offset] = $value;
	}

	/**
	 * Unsets an offset.
	 *
	 * @param string $offset The offset to unset.
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->_items[$offset]);
	}

	/**
	 * Rewinds to the first item.
	 *
	 * @return mixed The current item after rewinding.
	 */
	public function rewind() {
		$this->_valid = (reset($this->_items) !== false);
		return current($this->_items);
	}

	/**
	 * Moves forward to the last item.
	 *
	 * @return mixed The current item after moving.
	 */
	public function end() {
		$this->_valid = (end($this->_items) !== false);
		return current($this->_items);
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean `true` if valid, `false` otherwise.
	 */
	public function valid() {
		return $this->_valid;
	}

	/**
	 * Returns the current item.
	 *
	 * @return mixed The current item.
	 */
	public function current() {
		return current($this->_items);
	}

	/**
	 * Returns the key of the current item.
	 *
	 * @return scalar Scalar on success `0` on failure.
	 */
	public function key() {
		return key($this->_items);
	}

	/**
	 * Moves backward to the previous item.  If already at the first item,
	 * moves to the last one.
	 *
	 * @return mixed The current item after moving.
	 */
	public function prev() {
		if (!prev($this->_items)) {
			end($this->_items);
		}
		return current($this->_items);
	}

	/**
	 * Move forwards to the next item.
	 *
	 * @return The current item after moving.
	 */
	public function next() {
		$this->_valid = (next($this->_items) !== false);
		return current($this->_items);
	}

	/**
	 * Appends an item.
	 *
	 * @param mixed $value The item to append.
	 * @return void
	 */
	public function append($value) {
		is_object($value) ? $this->_items[] =& $value : $this->_items[] = $value;
	}

	/**
	 * Counts the items of the object.
	 *
	 * @return integer Number of items.
	 */
	public function count() {
		return count($this->_items);
	}

	/**
	 * Returns the item keys.
	 *
	 * @return array The keys of the items.
	 */
	public function keys() {
		return array_keys($this->_items);
	}
}

?>