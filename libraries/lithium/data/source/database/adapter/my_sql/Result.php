<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\my_sql;

class Result extends \lithium\data\source\database\Result {

	protected function _next() {
		return mysql_fetch_row($this->_resource);
	}

	protected function _close() {
		if ($this->_resource) {
			mysql_free_result($this->_resource);
		}
	}
}

?>