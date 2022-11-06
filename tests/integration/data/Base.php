<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2013, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\data;

use lithium\data\Connections;

class Base extends \lithium\test\Integration {

	protected $_connection = 'test';

	protected $_db = null;

	protected $_dbConfig = null;

	public function connect($connection, $options = []) {
		$options += ['autoConnect' => true];
		$this->_dbConfig = Connections::get($connection, ['config' => true]);
		$db = $this->_db = Connections::get($connection);

		$this->skipIf(!$db, "The `{$connection}` connection is not correctly configured.");
		$this->skipIf(!$db::enabled(), 'Extension is not loaded.');

		$this->skipIf(!$db->isConnected($options), "No `{$connection}` connection available.");
	}

	public function with($adapters) {
		$type = ($this->_dbConfig['adapter'] ?? $this->_dbConfig['type']) ?? null;

		foreach ((array) $adapters as $adapter) {
			if ($type === $adapter) {
				return true;
			}
		}
		return false;
	}

	public function skipIf($condition, $message = false) {
		if ($message === false) {
			$type = isset($this->_dbConfig['adapter']) ? $this->_dbConfig['adapter'] : null;
			$type = $type ?: (isset($this->_dbConfig['type']) ? $this->_dbConfig['type'] : null);
			$class = basename(str_replace('\\', '/', get_called_class()));
			$callers = debug_backtrace();
			$caller = $callers[1]['function'];
			$method = $caller !== 'skip' ? "{$class}::" . $caller : $class;
			$message = "`{$method}` Not supported by the `'{$type}'` adapter";
		}
		return parent::skipIf($condition, $message);
	}
}

?>