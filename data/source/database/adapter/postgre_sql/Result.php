<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\postgre_sql;

class Result extends \lithium\data\source\database\Result {

	public function prev() {
		if ($this->_current = $this->_prev()) {
			$this->_iterator--;
			return $this->_current;
		}
	}

	protected function _prev() {
		if ($this->_resource && $this->_iterator) {
			if (pg_result_seek($this->_resource, $this->_iterator -1)) {
				return pg_fetch_row($this->_resource);
			}
		}
	}

	protected function _next() {
		if ($this->_resource) {
			$inRange = $this->_iterator < pg_num_rows($this->_resource);
			if ($inRange && pg_result_seek($this->_resource, $this->_iterator)) {
				return pg_fetch_row($this->_resource);
			}
		}
	}

	protected function _close() {
		if ($this->_resource) {
			pg_free_result($this->_resource);
		}
	}
}

?>