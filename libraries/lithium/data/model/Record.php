<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

class Record extends \lithium\core\Object {

	/**
	* Namespaced name of model that this record is linked to.
	*/
	protected $_model = null;

	/**
	* Associative array of the records fields with values
	*/
	protected $_data = array();

	/**
	* Validation errors
	*/
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

	/**
	 * Allows several properties to be assigned at once, i.e.:
	 * {{{
	 * $record->set(array('title' => 'Lorem Ipsum', 'value' => 42));
	 * }}}
	 *
	 * @param $values An associative array of fields and values to assign to the `Record`.
	 * @return void
	 */
	public function set($values) {
		foreach ($values as $name => $value) {
			$this->__set($name, $value);
		}
	}

	/**
	* Access the data fields of the record. Can also access a $named field;
	*
	* @param string $name optionally include field name, returns entire data array if left empty
	* @return array
	*/
	public function data($name = null) {
		return empty($name) ? $this->_data : $this->__get($name);
	}

	/**
	* Magic method that allows calling of model methods on this record instance, i.e.:
	* {{{
	* $record->validates();
	* }}}
	*
	* @param string $method
	* @param array $params
	* return mixed
	*/
	public function __call($method, $params) {
		$model = $this->_model;

		if (!$model) {
			return null;
		}
		array_unshift($params, $this);
		$class = $model::invokeMethod('_instance');
		return call_user_func_array(array(&$class, $method), $params);
	}

	/**
	* A flag indicating whether or not this record exists.
	*
	* @return boolean True if record is from db, or after a save.
	*/
	public function exists() {
		return $this->_exists;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array.
	 *
	 * @param string $format currently only `array`
	 * @param array $options
	 * @return mixed
	 */
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