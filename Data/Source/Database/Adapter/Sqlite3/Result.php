<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Data\Source\Database\Adapter\Sqlite3;

use SQLite3Result;

class Result extends \Lithium\Data\Source\Database\Result {

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
		if ($this->_resource instanceof SQLite3Result) {
			return $this->_resource->fetchArray(SQLITE3_NUM);
		}
	}

	protected function _close() {
		if ($this->_resource instanceof SQLite3Result) {
			$this->_resource->finalize();
		}
	}

	public function __call($name, $arguments) {
		if (!$this->_resource instanceof SQLite3Result) {
			return;
		}

		if (is_callable(array($this->_resource, $name))) {
			return call_user_func_array(array(&$this->_resource, $name), $arguments);
		}
	}
}

?>