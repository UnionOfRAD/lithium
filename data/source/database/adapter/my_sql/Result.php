<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\my_sql;

use PDO;
use PDOStatement;

/**
 * This class is a wrapper around the MySQL result returned and can be used to iterate over it.
 *
 * It also provides a simple caching mechanism which stores the result after the first load.
 * You are then free to iterate over the result back and forth through the provided methods
 * and don't have to think about hitting the database too often.
 *
 * On initialization, it needs a `PDOStatement` to operate on. You are then free to use all
 * methods provided by the `Iterator` interface.
 *
 * @link http://php.net/manual/de/class.pdostatement.php The PDOStatement class.
 * @link http://php.net/manual/de/class.iterator.php The Iterator interface.
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
		$this->_current = isset($this->_cache[1]) ? $this->_cache[1] : null;
	}

	/**
	 * Checks wheter the result is valid or not.
	 *
	 * @return boolean Returns whether the result is valid or not.
	 */
	public function valid() {
		if ($this->_resource) {
			$rowCount = $this->_resource->rowCount();
			return $rowCount > 0 && $this->_iterator <= $rowCount;
		}

		return false;
	}

	/**
	 * Contains the current result.
	 *
	 * @return array The current result (or `null` if there is none).
	 */
	public function current() {

		if (!$this->_current) {
			$this->next();
		}

		return $this->_current;
	}

	/**
	 * Returns the current key position on the result.
	 *
	 * @return integer The current iterator position.
	 */
	public function key() {
		return $this->_iterator;
	}

	/**
	 * Fetches the previous element from the cache.
	 *
	 * @return array The previous result (or `null` if there is none).
	 */
	public function prev() {
		if (!empty($this->_cache)) {
			if (isset($this->_cache[--$this->_iterator])) {
				return $this->_current = $this->_cache[$this->_iterator];
			}
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
	 *
	 * @return array The cached result (or `false` if it has not been cached yet).
	 */
	protected function _fetchFromCache() {
		if ($this->_iterator < count($this->_cache)) {
			return $this->_cache[++$this->_iterator];
		}

		return false;
	}

	/**
	 * Fetches the result from the resource and caches it.
	 *
	 * @return array the fetched result (or `false` if it is not valid).
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