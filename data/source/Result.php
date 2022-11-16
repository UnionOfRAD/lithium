<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2012, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source;

use ReturnTypeWillChange;
use lithium\core\AutoConfigurable;

/**
 * The `Result` class is a wrapper around a forward-only data soure result cursor and can be
 * used to iterate over it.
 *
 * Just a forward-only cursor is required, not necessarily a rolling cursor. Not all
 * data sources can support rolling cursors, that's why forward-only cursors have been
 * chosen here as the least common denominator for the abstraction.
 *
 * Rolling cursors cannot be emulated from an underlying forward-only cursor in a
 * (memory) efficient way. `Result` does not try to and will deliberately not keep
 * already yielded results in an internal cache.
 *
 * To allow full (back and forth) iteration over the yielded results, a wrapping class
 * (i.e. `Collection`) may keep such a cache while draining the `Result` from outside.
 *
 * Because of these characteristics an instance of `Result` may be used only once.
 * After draining the result it cannot be rewinded or reset. This is similar to the
 * behavior of the `NoRewindIterator`, where rewind calls have no effect.
 *
 * The first result will be eager loaded from the cursor, as it is expected the
 * result will be used at least once.
 *
 * The class also provides a mechanism which buffers results when peeking for next ones. The
 * buffered results will then be used when continuing to iterate over the result object.
 *
 * @link https://en.wikipedia.org/wiki/Cursor_(databases)
 * @link http://php.net/manual/class.iterator.php The Iterator interface.
 * @link http://php.net/manual/norewinditerator.rewind.php
 */
abstract class Result implements \Iterator {

	use AutoConfigurable;

	/**
	 * The current position of the iterator.
	 *
	 * @var integer
	 */
	protected $_iterator = 0;

	/**
	 * Contains the current element of the result set.
	 *
	 * @var mixed
	 */
	protected $_current = null;

	/**
	 * Indicates whether the current position is valid or not.
	 *
	 * @see lithium\data\source\Result::valid()
	 * @var boolean
	 */
	protected $_valid = false;

	/**
	 * Key of the current result.
	 *
	 * @var integer|null
	 */
	protected $_key = null;

	/**
	 * The bound resource.
	 *
	 * @var object|resource|null
	 */
	protected $_resource = null;

	/**
	 * Autoconfig.
	 *
	 * @var array
	 */
	protected $_autoConfig = ['resource'];

	/**
	 * Buffer results of query before returning / iterating. Allows consumers to 'peek' at results.
	 *
	 * @var array
	 */
	protected $_buffer = [];

	/**
	 * Returns the used resource.
	 *
	 * @return object|resource
	 */
	public function resource() {
		return $this->_resource;
	}

	/**
	 * Initializer. Eager loads the first result.
	 *
	 * @return void
	 */
	protected function _init() {
		$this->next();
	}

	/**
	 * Contains the current result.
	 *
	 * @return array The current result (or `null` if there is none).
	 */
	public function current(): mixed {
		return $this->_current;
	}

	/**
	 * Returns the current key position on the result.
	 *
	 * @return integer|null The current key position or `null` if there is none.
	 */
	public function key(): int | null {
		return $this->_key;
	}

	/**
	 * Fetches the next element from the resource.
	 *
	 * @return mixed The next result (or `null` if there is none).
	 */
	#[ReturnTypeWillChange]
	public function next(): mixed {
		if ($this->_buffer) {
			list($this->_key, $this->_current) = array_shift($this->_buffer);
			return $this->_current;
		}

		if (!$next = $this->_fetch()) {
			$this->_key = null;
			$this->_current = null;
			$this->_valid = false;

			return null;
		} else {
			list($this->_key, $this->_current) = $next;
			$this->_valid = true;
		}
		return $this->_current;
	}

	/**
	 * Peeks at the next element in the resource without advancing `Result`'s cursor.
	 *
	 * @return mixed The next result (or `null` if there is none).
	 */
	public function peek() {
		if ($this->_buffer) {
			return reset($this->_buffer);
		}
		if (!$next = $this->_fetch()) {
			return null;
		}
		$this->_buffer[] = $next;
		$first = reset($this->_buffer);
		return end($first);
	}

	/**
	 * Noop to fulfill the `Iterator` interface. `Result` is forward-only.
	 *
	 * @return void
	 */
	public function rewind(): void {}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean `true` if valid, `false` otherwise.
	 */
	public function valid(): bool {
		return $this->_valid;
	}

	/**
	 * Close the resource.
	 *
	 * @return void
	 */
	public function close() {
		unset($this->_resource);
		$this->_resource = null;
	}

	/**
	 * Destructor.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * Fetches the next result from the resource.
	 *
	 * @return array|boolean|null Returns a key/value pair for the next result,
	 *         `null` if there is none, `false` if something bad happened.
	 */
	abstract protected function _fetch();
}

?>