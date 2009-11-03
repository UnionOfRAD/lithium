<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

class Record extends \lithium\core\Object {

	protected $_model = null;

	protected $_data = array();

	protected $_errors = array();

	/**
	 * An array of flags to track which fields in this record have been modified, where the keys
	 * are field names, and the values are always true. If, for example, a change to a field is
	 * reverted, that field's flag should be unset from the list.
	 *
	 * @var array
	 */
	protected $_modified = array();

	/**
	 * A flag indicating whether or not this record exists. Set to false if this is a newly-created
	 * record, or if this record has been loaded and subsequently deleted. True if the record has
	 * been loaded from the database, or has been created and subsequently saved.
	 *
	 * @var boolean
	 */
	protected $_exists = false;

	protected $_autoConfig = array('model', 'exists', 'data' => 'merge');

	protected $_hasValidated = false;

	public function __construct($config = array()) {
		$defaults = array('model' => null, 'data' => array());
		parent::__construct((array)$config + $defaults);
	}

	public function __get($name) {
		return isset($this->_data[$name]) ? $this->_data[$name] : null;
	}

	public function __set($name, $value) {
		$this->_modified[$name] = true;
		$this->_data[$name] = $value;
	}

	public function __isset($name) {
		return array_key_exists($name, $this->_data);
	}

	public function set($values) {
		foreach ($values as $name => $value) {
			$this->__set($name, $value);
		}
	}

	public function data($name = null) {
		return empty($name) ? $this->_data : $this->__get($name);
	}

	public function __call($method, $params) {
		$model = $this->_model;
		array_unshift($params, $this);
		$class = $model::invokeMethod('_instance');
		return call_user_func_array(array(&$class, $method), $params);
	}

	public function exists() {
		return $this->_exists;
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