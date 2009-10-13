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

namespace lithium\data\model;

class Record extends \lithium\core\Object {

	protected $_model = null;

	protected $_data = array();

	protected $_autoConfig = array('model', 'data' => 'merge');

	public function __construct($config = array()) {
		$defaults = array('model' => null, 'data' => array());
		parent::__construct((array)$config + $defaults);
	}

	public function __get($name) {
		return isset($this->_data[$name]) ? $this->_data[$name] : null;
	}

	public function __isset($name) {
		return array_key_exists($name, $this->_data);
	}

	public function to($format, $options = array()) {
		switch ($format) {
			case 'array':
				$result = $this->_data;
			break;
			default:
				$result = $this;
			break;
		}
		return $result;
	}
}

?>