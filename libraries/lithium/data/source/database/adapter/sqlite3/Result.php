<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\sqlite3;

use SQLite3Result;

class Result extends \lithium\data\source\database\Result {

	protected function _next() {
		if (!$this->_resource instanceof SQLite3Result) {
			return;
		}
		return $resource->fetchArray(SQLITE3_ASSOC);
	}

	protected function _close() {
		if (!$this->_resource instanceof SQLite3Result) {
			return;
		}
		$resource->finalize();
	}
}

?>