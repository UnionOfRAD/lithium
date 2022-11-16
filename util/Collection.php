<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\util;

use ReturnTypeWillChange;
use lithium\core\AutoConfigurable;

/**
 * The parent class for all collection objects. Contains methods for collection iteration,
 * conversion, and filtering. Implements `ArrayAccess`, `Iterator`, and `Countable`.
 *
 * Collection objects can act very much like arrays. This is especially evident in creating new
 * objects, or by converting Collection into an actual array:
 *
 * ```
 * $coll = new Collection();
 * $coll[] = 'foo';
 * // $coll[0] --> 'foo'
 *
 * $coll = new Collection(['data' => ['foo']]);
 * // $coll[0] --> 'foo'
 *
 * $array = $coll->to('array');
 * ```
 *
 * Apart from array-like data access, Collections allow for filtering and iteration methods:
 * ```
 * $coll = new Collection(['data' => [0, 1, 2, 3, 4]]);
 *
 * $coll->first();   // 0
 * $coll->current(); // 0
 * $coll->next();    // 1
 * $coll->next();    // 2
 * $coll->next();    // 3
 * $coll->prev();    // 2
 * $coll->rewind();  // 0
 * ```
 *
 * The primary purpose of the `Collection` class is to enable simple, efficient access to groups
 * of similar objects, and to perform operations against these objects using anonymous functions.
 *
 * The `map()` and `each()` methods allow you to perform operations against the entire set of values
 * in a `Collection`, while `find()` and `first()` allow you to search through values and pick out
 * one or more.
 *
 * The `Collection` class also supports dispatching methods against a set of objects, if the method
 * is supported by all objects. For example:
 * ```
 * class Task {
 * 	public function run($when) {
 * 		// Do some work
 * 	}
 * }
 *
 * $data = [
 * 	new Task(['task' => 'task 1']),
 * 	new Task(['task' => 'task 2']),
 * 	new Task(['task' => 'task 3'])
 * ];
 * $tasks = new Collection(compact('data'));
 *
 * // $result will contain an array, and each element will be the return
 * // value of a run() method call:
 * $result = $tasks->invoke('run', ['now']);
 *
 * // Alternatively, the method can be called natively, with the same result:
 * $result = $tasks->run('now');
 * ```
 *
 * @link http://php.net/class.arrayaccess.php PHP Manual: ArrayAccess Interface
 * @link http://php.net/class.iterator.php PHP Manual: Iterator Interface
 * @link http://php.net/class.countable.php PHP Manual: Countable Interface
 */
class Collection implements \ArrayAccess, \Iterator, \Countable {

	use AutoConfigurable;

	/**
	 * A central registry of global format handlers for `Collection` objects and subclasses.
	 * Accessed via the `formats()` method.
	 *
	 * @see lithium\util\Collection::formats()
	 * @var array
	 */
	protected static $_formats = [
		'array' => 'lithium\util\Collection::toArray'
	];

	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $_data = [];

	/**
	 * Allows a collection's items to be automatically assigned from class construction options.
	 *
	 * @var array
	 */
	protected $_autoConfig = ['data'];

	/**
	 * Accessor method for adding format handlers to instances and subclasses of `Collection`.
	 * The values assigned are used by `Collection::to()` to convert `Collection` instances into
	 * different formats, i.e. JSON.
	 *
	 * This can be accomplished in two ways. First, format handlers may be registered on a
	 * case-by-case basis, as in the following:
	 *
	 * ```
	 * Collection::formats('json', function($collection, $options) {
	 * 	return json_encode($collection->to('array'));
	 * });
	 *
	 * // You can also implement the above as a static class method, and register it as follows:
	 * Collection::formats('json', '\my\custom\Formatter::toJson');
	 * ```
	 *
	 * Alternatively, you can implement a class that can handle several formats. This class must
	 * implement two static methods:
	 *
	 * - A `formats()` method, which returns an array indicating what formats it handles.
	 *
	 * - A `to()` method, which handles the actual conversion.
	 *
	 * Once a class implements these methods, it may be registered per the following:
	 * ```
	 * Collection::formats('\lithium\net\http\Media');
	 * ```
	 *
	 * For reference on how to implement these methods, see the `Media` class.
	 *
	 * Once a handler is registered, any instance of `Collection` or a subclass can be converted to
	 * the format(s) supported by the class or handler, using the `to()` method.
	 *
	 * @see lithium\net\http\Media::to()
	 * @see lithium\net\http\Media::formats()
	 * @see lithium\util\Collection::to()
	 * @param string $format A string representing the name of the format that a `Collection` can
	 *        be converted to. This corresponds to the `$format` parameter in the `to()` method.
	 *        Alternatively, the fully-namespaced class name of a format-handler class.
	 * @param mixed $handler If `$format` is the name of a format string, `$handler` should be the
	 *        function that handles the conversion, either an anonymous function, or a
	 *        reference to a method name in `"Class::method"` form. If `$format` is a class
	 *        name, can be `null`.
	 * @return mixed Returns the value of the format handler assigned.
	 */
	public static function formats($format, $handler = null) {
		if ($format === false) {
			return static::$_formats = ['array' => 'lithium\util\Collection::toArray'];
		}
		if ($handler === null && class_exists($format)) {
			return static::$_formats[] = $format;
		}
		return static::$_formats[$format] = $handler;
	}

	/**
	 * Initializes the collection object by merging in collection items and removing redundant
	 * object properties.
	 *
	 * @return void
	 */
	protected function _init() {
		unset($this->_config['data']);
	}

	/**
	 * Handles dispatching of methods against all items in the collection.
	 *
	 * @param string $method The name of the method to call on each instance in the collection.
	 * @param array $params The parameters to pass on each method call.
	 * @param array $options Specifies options for how to run the given method against the object
	 *        collection. The available options are:
	 *        - `'collect'`: If `true`, the results of this method call will be returned
	 *          wrapped in a new `Collection` object or subclass.
	 *        - `'merge'`: Used primarily if the method being invoked returns an array.  If
	 *          set to `true`, merges all results arrays into one.
	 * @todo Implement filtering.
	 * @return mixed Returns either an array of the return values of the methods, or the return
	 *         values wrapped in a `Collection` instance.
	 */
	public function invoke($method, array $params = [], array $options = []) {
		$class = get_class($this);
		$defaults = ['merge' => false, 'collect' => false];
		$options += $defaults;
		$data = [];

		foreach ($this as $object) {
			$value = call_user_func_array([&$object, $method], $params);
			($options['merge']) ? $data = array_merge($data, $value) : $data[$this->key()] = $value;
		}
		return ($options['collect']) ? new $class(compact('data')) : $data;
	}

	/**
	 * Hook to handle dispatching of methods against all items in the collection.
	 *
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters = []) {
		return $this->invoke($method, $parameters);
	}

	/**
	 * Converts a `Collection` object to another type of object, or a simple type such as an array.
	 * The supported values of `$format` depend on the format handlers registered in the static
	 * property `Collection::$_formats`. The `Collection` class comes with built-in support for
	 * array conversion, but other formats may be registered.
	 *
	 * Once the appropriate handlers are registered, a `Collection` instance can be
	 * converted into any handler-supported format, i.e.:
	 * ```
	 * $collection->to('json'); // returns a JSON string
	 * $collection->to('xml'); // returns an XML string
	 * ```
	 *
	 *  _Please note that Lithium does not ship with a default XML handler, but one can be
	 * configured easily._
	 *
	 * @see lithium\util\Collection::formats()
	 * @see lithium\util\Collection::$_formats
	 * @param string $format By default the only supported value is `'array'`. However, additional
	 *        format handlers can be registered using the `formats()` method.
	 * @param array $options Options for converting this collection:
	 *        - `'internal'` _boolean_: Indicates whether the current internal representation of the
	 *          collection should be exported. Defaults to `false`, which uses the standard iterator
	 *          interfaces. This is useful for exporting record sets, where records are lazy-loaded,
	 *          and the collection must be iterated in order to fetch all objects.
	 * @return mixed The object converted to the value specified in `$format`; usually an array or
	 *         string.
	 */
	public function to($format, array $options = []) {
		$defaults = ['internal' => false];
		$options += $defaults;
		$data = $options['internal'] ? $this->_data : $this;
		return $this->_to($format, $data, $options);
	}

	protected function _to($format, &$data, &$options) {
		if (is_object($format) && is_callable($format)) {
			return $format($data, $options);
		}

		if (isset(static::$_formats[$format]) && is_callable(static::$_formats[$format])) {
			$handler = static::$_formats[$format];
			$handler = is_string($handler) ? explode('::', $handler, 2) : $handler;

			if (is_array($handler)) {
				list($class, $method) = $handler;
				return $class::$method($data, $options);
			}
			return $handler($data, $options);
		}

		foreach (static::$_formats as $key => $handler) {
			if (!is_int($key)) {
				continue;
			}
			if (in_array($format, $handler::formats($format, $data, $options))) {
				return $handler::to($format, $data, $options);
			}
		}
	}

	/**
	 * Filters a copy of the items in the collection.
	 *
	 * @param callable $filter Callback to use for filtering.
	 * @param array $options The available options are:
	 *        - `'collect'`: If `true`, the results will be returned wrapped in a new
	 *          `Collection` object or subclass. Defaults to `true`.
	 * @return mixed The filtered items. Will be an array unless `'collect'` is defined in the
	 *         `$options` argument, then an instance of this class will be returned.
	 */
	public function find($filter, array $options = []) {
		$defaults = ['collect' => true];
		$options += $defaults;
		$data = array_filter($this->_data, $filter);

		if ($options['collect']) {
			$class = get_class($this);
			$data = new $class(compact('data'));
		}
		return $data;
	}

	/**
	 * Rewinds the collection and returns the first item or when a filter is used,
	 * returns the first non-empty item after the filter is applied.
	 *
	 * @see lithium\util\Collection::rewind()
	 * @param callable $filter An optional callable through which items  will be passed.
	 *        If the return value of this function is trueish, it will be returned as
	 *        the result of the method call. An example filter function may look like:
	 *        `function($item) { return $item->year < 2005; }`.
	 * @return mixed Returns the first item in the collection or when `$filter` is used,
	 *         the first item where `$filter` returned a trueish result.
	 */
	public function first($filter = null) {
		if (!$filter) {
			return $this->rewind();
		}
		foreach ($this as $item) {
			if ($filter($item)) {
				return $item;
			}
		}
	}

	/**
	 * Applies a callback to all items in the collection.
	 *
	 * @link http://php.net/array_map
	 * @param callable $filter The filter to apply.
	 * @return Collection This collection instance.
	 */
	public function each($filter) {
		$this->_data = array_map($filter, $this->_data);
		return $this;
	}

	/**
	 * Applies a callback to a copy of all data in the collection
	 * and returns the result.
	 *
	 * @link http://php.net/array_map
	 * @param callable $filter The filter to apply.
	 * @param array $options The available options are:
	 *        - `'collect'`: If `true`, the results will be returned wrapped
	 *        in a new `Collection` object or subclass.
	 * @return mixed The filtered items. Will be an array unless `'collect'` is defined in the
	 *         `$options` argument, then an instance of this class will be returned.
	 */
	public function map($filter, array $options = []) {
		$defaults = ['collect' => true];
		$options += $defaults;
		$data = array_map($filter, $this->_data);

		if ($options['collect']) {
			$class = get_class($this);
			return new $class(compact('data'));
		}
		return $data;
	}

	/**
	 * Reduce, or fold, a collection down to a single value
	 *
	 * @link http://php.net/array_reduce
	 * @param callable $reducer The reduce function, i.e. `function($carry, $item) { return ... }`
	 * @param mixed $initial Initial value passed to the reduce function as `$carry`,
	 *        defaults to `false`.
	 * @return mixed A single reduced value.
	 */
	public function reduce($reducer, $initial = false) {
		return array_reduce($this->_data, $reducer, $initial);
	}

	/**
	 * Sorts the objects in the collection.
	 *
	 * @link http://php.net/sort
	 * @link http://php.net/usort
	 * @link http://php.net/strcmp
	 * @param string|callable $sorter The sorter for the data. Either a callable to use
	 *        as the sort function or a string with the name of a well-known sort function like
	 *        `'natsort'` or a compare function like `'strcmp'`. Defaults to `'sort'`.
	 * @param array $options Reserved for future use.
	 * @return Collection Returns itself.
	 */
	public function sort($sorter = 'sort', array $options = []) {
		if (is_string($sorter) && strpos($sorter, 'sort') !== false && is_callable($sorter)) {
			call_user_func_array($sorter, [&$this->_data]);
		} elseif (is_callable($sorter)) {
			usort($this->_data, $sorter);
		}
		return $this;
	}

	/**
	 * Checks whether or not an offset exists.
	 *
	 * @param string $offset An offset to check for.
	 * @return boolean `true` if offset exists, `false` otherwise.
	 */
	public function offsetExists($offset): bool {
		return array_key_exists($offset, $this->_data);
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param string $offset The offset to retrieve.
	 * @return mixed Value at offset.
	 */
	public function offsetGet($offset): mixed {
		return $this->_data[$offset];
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @param string $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 * @return mixed The value which was set.
	 */
	#[ReturnTypeWillChange]
	public function offsetSet($offset, $value): mixed {
		if ($offset === null) {
			return $this->_data[] = $value;
		}
		return $this->_data[$offset] = $value;
	}

	/**
	 * Unsets an offset.
	 *
	 * @param string $offset The offset to unset.
	 */
	public function offsetUnset($offset): void {
		prev($this->_data);

		if (key($this->_data) === null) {
			$this->rewind();
		}
		unset($this->_data[$offset]);
	}

	/**
	 * Rewinds to the first item.
	 *
	 * @return mixed The current item after rewinding, or `false` if the collection is empty.
	 */
	#[ReturnTypeWillChange]
	public function rewind() {
		return reset($this->_data);
	}

	/**
	 * Moves forward to the last item.
	 *
	 * @return mixed The last item, or `false` if there is no last item or the collection is empty.
	 */
	public function end() {
		return end($this->_data);
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean `true` if valid, `false` otherwise.
	 */
	public function valid(): bool {
		return key($this->_data) !== null;
	}

	/**
	 * Returns the current item.
	 *
	 * @return mixed The current item or `false` on failure.
	 */
	public function current(): mixed {
		return current($this->_data);
	}

	/**
	 * Returns the key of the current item.
	 *
	 * @return scalar Scalar on success or `null` on failure.
	 */
	public function key(): mixed {
		return key($this->_data);
	}

	/**
	 * Moves backward to the previous item.
	 *
	 * Note: When already at the first item, moves to the last one.
	 *
	 * @return mixed The previous item or `false` if the collection is empty. Returns the
	 *         last item, when already at the first item.
	 */
	public function prev(): mixed {
		$value = prev($this->_data);
		return key($this->_data) !== null ? $value : end($this->_data);
	}

	/**
	 * Moves forward to the next item.
	 *
	 * @return mixed The next item or `false`, in case there's no next item or the collection
	 *         is empty.
	 */
	#[ReturnTypeWillChange]
	public function next(): mixed {
		$value = next($this->_data);
		return key($this->_data) !== null ? $value : false;
	}

	/**
	 * Appends an item.
	 *
	 * @param mixed $value The item to append.
	 */
	public function append($value) {
		is_object($value) ? $this->_data[] =& $value : $this->_data[] = $value;
	}

	/**
	 * Counts the items of the object.
	 *
	 * @return integer Returns the number of items in the collection.
	 */
	public function count(): int {
		$count = iterator_count($this);
		$this->rewind();
		return $count;
	}

	/**
	 * Returns the item keys.
	 *
	 * @return array The keys of the items.
	 */
	public function keys() {
		return array_keys($this->_data);
	}

	/**
	 * Exports a `Collection` instance to an array. Used by `Collection::to()`.
	 *
	 * @param mixed $data Either a `Collection` instance, or an array representing a `Collection`'s
	 *        internal state.
	 * @param array $options Options used when converting `$data` to an array:
	 *        - `'handlers'` _array_: An array where the keys are fully-namespaced class
	 *        names, and the values are closures that take an instance of the class as a
	 *        parameter, and return an array or scalar value that the instance represents.
	 * @return array Returns the value of `$data` as a pure PHP array, recursively converting all
	 *         sub-objects and other values to their closest array or scalar equivalents.
	 */
	public static function toArray($data, array $options = []) {
		$defaults = ['handlers' => []];
		$options += $defaults;
		$result = [];

		foreach ($data as $key => $item) {
			switch (true) {
				case is_array($item):
					$result[$key] = static::toArray($item, $options);
				break;
				case (!is_object($item)):
					$result[$key] = $item;
				break;
				case (isset($options['handlers'][$class = get_class($item)])):
					$result[$key] = $options['handlers'][$class]($item);
				break;
				case (method_exists($item, 'to')):
					$result[$key] = $item->to('array', $options);
				break;
				case ($vars = get_object_vars($item)):
					$result[$key] = static::toArray($vars, $options);
				break;
				case (method_exists($item, '__toString')):
					$result[$key] = (string) $item;
				break;
				default:
					$result[$key] = $item;
				break;
			}
		}
		return $result;
	}
}

?>