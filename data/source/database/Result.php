<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database;

abstract class Result extends \lithium\core\Object implements \Iterator {

	protected $_iterator = 0;

	protected $_current = null;

	protected $_resource = null;

	protected $_autoConfig = array('resource');

	public function __construct(array $config = array()) {
		$defaults = array('resource' => null);
		parent::__construct($config + $defaults);
	}

	public function resource() {
		return $this->_resource;
	}

	public function rewind() {
		return null;
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

	public function next() {
		if ($this->_current = $this->_next()) {
			$this->_iterator++;
			return $this->_current;
		}
		$this->_close();
		$this->_resource = null;
	}

	public function __destruct() {
		$this->_close();
		$this->_resource = null;
	}

	abstract protected function _next();

	abstract protected function _close();
}

?>