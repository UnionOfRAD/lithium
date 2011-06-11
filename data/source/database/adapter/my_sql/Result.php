<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\my_sql;

class Result extends \lithium\data\source\database\Result {

	public function prev() {
		if ($this->_current = $this->_prev()) {
			$this->_iterator--;
			return $this->_current;
		}
	}

	protected function _prev() {
		if ($this->_resource && $this->_iterator) {
			if (mysql_data_seek($this->_resource, $this->_iterator -1)) {
				return mysql_fetch_row($this->_resource);
			}
		}
	}

	protected function _next() {
		if ($this->_resource) {
			$inRange = $this->_iterator < mysql_num_rows($this->_resource);
			if ($inRange && mysql_data_seek($this->_resource, $this->_iterator)) {
				return mysql_fetch_row($this->_resource);
			}
		}
	}

	protected function _close() {
		if ($this->_resource) {
			mysql_free_result($this->_resource);
		}
	}
}

?>