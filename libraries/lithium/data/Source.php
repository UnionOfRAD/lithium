<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

abstract class Source extends \lithium\core\Object {

	protected $_connection = null;

	protected $_isConnected = false;

	public function __construct($config = array()) {
		$defaults = array('autoConnect' => true);
		parent::__construct((array)$config + $defaults);
	}

	public function __destruct() {
		if ($this->_isConnected) {
			$this->disconnect();
		}
	}

	protected function _init() {
		if ($this->_config['autoConnect']) {
			$this->connect();
		}
	}

	abstract public function connect();

	abstract public function disconnect();

	abstract public function entities($class = null);

	abstract public function describe($entity, $meta = array());

	abstract public function create($record, $options);

	abstract public function read($query, $options);

	/**
	 * Updates a set of records in a concrete data store.
	 *
	 * @param mixed $query An object which defines the update operation(s) that should be performed
	 *               against the data store.  This can be a `Query`, a `RecordSet`, a `Record`, or a
	 *               subclass of one of the three. Alternatively, `$query` can be an
	 *               adapter-specific query string.
	 * @param array $options Options to execute, which are defined by the concrete implementation.
	 * @return boolean Returns true if the update operation was a success, otherwise false.
	 */
	abstract public function update($query, $options);

	abstract public function delete($query, $options);
}

?>