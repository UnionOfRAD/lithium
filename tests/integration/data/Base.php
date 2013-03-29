<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\data\Connections;

class Base extends \lithium\test\Integration {

	protected $_connection = 'test';

	protected $_db = null;

	protected $_dbConfig = null;

	public function connect($connection, $options = array()) {
		$options += array('autoConnect' => true);
		$this->_dbConfig = Connections::get($connection, array('config' => true));
		$db = $this->_db = Connections::get($connection);

		$this->skipIf(!$db, "The `'{$connection}' connection is not correctly configured`.");
		$this->skipIf(!$db::enabled(), 'Extension is not loaded.');

		$this->skipIf(!$db->isConnected($options), "No {$connection} connection available.");
	}

	public function with($adapters) {
		$adapters = (array) $adapters;
		$type = $this->_dbConfig['adapter'];
		$type = $type ?: $this->_dbConfig['type'];
		foreach ($adapters as $adapter) {
			if ($type === $adapter) {
				return true;
			}
		}
		return false;
	}

	public function skipIf($condition, $message = false) {
		if ($message === false) {
			$type = $this->_dbConfig['adapter'];
			$type = $type ?: $this->_dbConfig['type'];
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