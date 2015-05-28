<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

abstract class Result extends \lithium\core\Object implements \Iterator {

	/**
	 * The current position of the iterator.
	 */
	protected $_iterator = 0;

	/**
	 * Contains the current element of the result set.
	 */
	protected $_current = false;

	/**
	 * Set to `true` when the collection has begun iterating.
	 *
	 * @var integer
	 */
	protected $_started = false;

	/**
	 * If the result resource has been initialized
	 */
	protected $_init = false;

	/**
	 * Indicates whether the current position is valid or not.
	 *
	 * @var boolean
	 * @see lithium\data\source\Result::valid()
	 */
	protected $_valid = false;

	/**
	 * If the result resource has been initialized
	 */
	protected $_key = null;

	/**
	 * The bound resource.
	 */
	protected $_resource = null;

	/**
	 * Autoconfig.
	 */
	protected $_autoConfig = array('resource');

	/**
	 * Buffer results of query before returning / iterating. Allows consumers to 'peek' at results.
	 *
	 * @var array
	 */
	protected $_buffer = array();

	/**
	 * Returns the used resource.
	 */
	public function resource() {
		return $this->_resource;
	}

	/**
	 * Checks if current position is valid.
	 *
	 * @return boolean `true` if valid, `false` otherwise.
	 */
	public function valid() {
		if (!$this->_init) {
			$this->current();
			$this->_init = true;
		}
		return $this->_valid;
	}

	/**
	 * Rewinds the result set to the first position.
	 */
	public function rewind() {
		$this->_iterator = 0;
		$this->_started = false;
		$this->_key = null;
		$this->_current = false;
		$this->_init = false;
	}

	/**
	 * Contains the current result.
	 *
	 * @return array The current result (or `null` if there is none).
	 */
	public function current() {
		if (!$this->_init) {
			if ($next = $this->_fetch()) {
				list($this->_key, $this->_current) = $next;
				$this->_valid = true;
			}
			$this->_init = true;
		}
		$this->_started = true;
		return $this->_current;
	}

	/**
	 * Returns the current key position on the result.
	 *
	 * @return integer The current iterator position.
	 */
	public function key() {
		if (!$this->_init) {
			$this->_fetch();
		}
		$this->_started = true;
		return $this->_key;
	}

	/**
	 * Fetches the next element from the resource.
	 *
	 * @return mixed The next result (or `false` if there is none).
	 */
	public function next() {
		if ($this->_started === false) {
			return $this->current();
		}
		$this->_init = true;
		$this->_valid = true;

		if ($this->_buffer) {
			list($this->_key, $this->_current) = array_shift($this->_buffer);
			return $this->current();
		}

		if (!$next = $this->_fetch()) {
			$this->_key = null;
			$this->_current = false;
			$this->_valid = false;
			return $this->current();
		} else {
			list($this->_key, $this->_current) = $next;
		}
		return $this->current();
	}

	/**
	 * Peeks at the next element in the resource without advancing `Result`'s cursor.
	 *
	 * @return mixed The next result (or `false` if there is none).
	 */
	public function peek() {
		if ($this->_buffer) {
			return reset($this->_buffer);
		}
		if ($next = $this->_fetch()) {
			$this->_buffer[] = $next;
			$first = reset($this->_buffer);
			return end($first);
		}
	}

	/**
	 * Fetches the current element from the resource.
	 *
	 * @return array Return a key/value pair for the next result in the iterator, or `null`.
	 */
	abstract protected function _fetch();

	/**
	 * Close the resource.
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
}

?>