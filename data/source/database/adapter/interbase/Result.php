<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\interbase;

class Result extends \lithium\data\source\database\Result {

	public function prev() {
		if ($this->_current = $this->_prev()) {
			$this->_iterator--;
			return $this->_current;
		}
	}

  protected function _prev() {
    if ($this->_resource->reset()) {
      for ($i = 0; $i < $this->_iterator - 1; $i++) {
        $ret = $this->_next();
        $this->_iterator -= 1;
      }
      return $ret;
    }
  }

	protected function _next() {
		if ($this->_resource) {
				return ibase_fetch_row($this->_resource);
		}
	}

	protected function _close() {
		if ($this->_resource) {
			ibase_free_result($this->_resource);
		}
	}
}

?>