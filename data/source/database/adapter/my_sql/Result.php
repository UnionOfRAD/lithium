<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\my_sql;

class Result extends \lithium\core\Object implements \Iterator {
	protected $_previousResultsCache = null;

	protected $_iterator = 0;
	
	protected $_maxIteration = 0;

	protected $_current = null;

	/**
	 * @var \PDOStatement
	 */
	protected $_resource = null;

	protected $_autoConfig = array('resource');
	
	public function resource() {
		return $this->_resource;
	}

	public function rewind() {
		$this->_iterator = 0;
		$this->_current = null;
		return null;
	}

	public function valid() {
		return $this->_current;
	}

	public function current() {
		return $this->_current;
	}

	public function key() {
		return $this->_iterator;
	}

	public function prev() {
		if (!$this->_resource) {
			return;
		}
		
		// Turn the current iterator back
		$this->_iterator--;
		
		// Return the previous result from the previous results cache
		if (isset($this->_previousResultsCache[$this->_iterator])) {
			$this->_current = $this->_previousResultsCache[$this->_iterator];
			return $this->_current;
		} else {
			return;
		}
	}

	public function next() {
		if (!$this->_resource && empty($this->_previousResultsCache)) {
			return;
		}
		
		// If we are calling ->next() after calling ->prev() 
		// we must return from the cache till we catch up
		if ($this->_iterator < $this->_maxIteration) {
			$this->_iterator++;
			return $this->_previousResultsCache[$this->_iterator];
		}
		
		// We are current in the iteration, fetch the next row
		if ($this->_resource instanceof \PDOStatement && $this->_iterator < $this->_resource->rowCount() && $result = $this->_resource->fetch(\PDO::FETCH_ASSOC)) {
			$this->_iterator++;
			$this->_maxIteration = $this->_iterator;
			$this->_previousResultsCache[$this->_iterator] = $result;
			
			return $result;
		}
		unset($this->_resource);
		$this->_resource = null;
		
		return;
	}
}

?>