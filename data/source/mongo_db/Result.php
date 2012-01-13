<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongo_db;

use MongoGridFSFile;

class Result extends \lithium\core\Object implements \Iterator {

	protected $_iterator = 0;

	protected $_resource = null;

	protected $_data = array();
		
	protected $_autoConfig = array('resource');

	public function __construct(array $config = array()) {
		$defaults = array('resource' => null);
		parent::__construct($config + $defaults);
	}

	public function resource() {
		return $this->_resource;
	}

	public function rewind() {
		if (isset($this->_data[1])) {
			$this->_iterator = 1;
			$this->_current = $this->_data[1];
		}
	}

	public function valid() {
		return !empty($this->_resource);
	}

	public function current() {
		return $this->_current;
	}

	public function key() {
		return $this->_iterator;
	}

	public function prev() {
		if (isset($this->_data[$this->_iterator-1])) {
			$this->_iterator--;
			$this->_current = $this->_data[$this->_iterator];
			return $this->_current;
		}
	}

	public function next() {
		if (isset($this->_data[$this->_iterator + 1])) {
			$this->_iterator++;
			$this->_current = $this->_data[$this->_iterator];
			return $this->_current;
		}
		if ($this->_resource->hasNext()) {
			$this->_iterator++;
			$result = $this->_resource->getNext();
			$isFile = ($result instanceof MongoGridFSFile);
			$this->_current = $isFile ? array('file' => $result) + $result->file : $result;
			$this->_data[$this->_iterator] = $this->_current;
			return $this->_current;
		}
		unset($this->_resource);
		$this->_resource = null;
	}

	public function __destruct() {
		unset($this->_resource);
		$this->_resource = null;
	}
}

?>