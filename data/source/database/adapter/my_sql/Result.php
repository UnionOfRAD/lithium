<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\my_sql;

use \PDO;
use \PDOStatement;

/**
 *
 */
class Result extends \lithium\core\Object implements \Iterator {

	/**
	 * Contains the cached result set.
	 */
	protected $_cache = null;

	/**
	 * The current position of the iterator.
	 */
	protected $_iterator = 0;

	/**
	 * Contains the current element of the result set.
	 */
	protected $_current = null;

	/**
	 * The bound resource.
	 */
	protected $_resource = null;

	/**
	 * Autoconfig.
	 */
	protected $_autoConfig = array('resource');

	/**
	 * Returns the used resource.
	 */
	public function resource() {
		return $this->_resource;
	}

	/**
	 * Rewinds the result set to the first position.
	 */
	public function rewind() {
		$this->_iterator = 0;
		$this->_current = null;
	}

	/**
	 * Checks wheter the result is valid or not.
	 */
	public function valid() {
		return $this->_resource || !empty($this->_cache);
	}

	/**
	 * Contains the current result.
	 */
	public function current() {
		return $this->_current;
	}

	/**
	 *
	 */
	public function key() {
		return $this->_iterator;
	}

	/**
	 * Fetches the previous element from the cache.
	 */
	public function prev() {
		if (!$this->_resource) {
			return;
		}

		if (isset($this->_cache[--$this->_iterator])) {
			return $this->_current = $this->_cache[$this->_iterator];
		}
	}

	/**
	 * Fetches the next element from the resource.
	 */
	public function next() {
		if ($this->valid()) {
			if (($result = $this->_fetchFromCache()) || ($result = $this->_fetchFromResource())) {
				return $this->_current = $result;
			}

			$this->_resource = null;
		}
	}

	/**
	 * Returns the result from the primed cache.
	 */
	protected function _fetchFromCache() {
		if ($this->_iterator < count($this->_cache)) {
			return $this->_cache[++$this->_iterator];
		}

		return false;
	}

	/**
	 * Fetches the result from the resource and caches it. 
	 */	
	protected function _fetchFromResource() {
		if ($this->_resource instanceof PDOStatement
				&& $this->_iterator < $this->_resource->rowCount()
				&& $result = $this->_resource->fetch(PDO::FETCH_NUM)) {
			return $this->_cache[++$this->_iterator] = $result;
		}

		return false;
	}
}

?>