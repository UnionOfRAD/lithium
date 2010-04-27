<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

/**
 * `Record` class. Represents data such as a row from a database. Records have fields (often known
 * as columns in databases).
 */
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

	/**
	 * Auto configuration.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('model', 'exists', 'data' => 'merge');

	protected $_hasValidated = false;

	/**
	 * Creates a new record object with default values.
	 *
	 * Options defined:
	 * - 'data' _array_: Data to enter into the record. Defaults to an empty array.
	 * - 'model' _string_: Class name that provides the data-source for this record.
	 *   Defaults to `null`.
	 *
	 * @param array $config
	 * @return object Record object.
	 */
	public function __construct(array $config = array()) {
		$defaults = array('model' => null, 'data' => array());
		parent::__construct($config + $defaults);
	}

	/**
	 * Overloading for reading inaccessible properties.
	 *
	 * @param string $name Property name.
	 * @return mixed Result.
	 */
	public function __get($name) {
		return isset($this->_data[$name]) ? $this->_data[$name] : null;
	}

	/**
	 * Overloading for writing to inaccessible properties.
	 *
	 * @param string $name Property name.
	 * @param string $value Property value.
	 * @return mixed Result.
	 */
	public function __set($name, $value) {
		$this->_modified[$name] = true;
		$this->_data[$name] = $value;
	}

	/**
	 * Overloading for calling isset() or empty() on inaccessible properties.
	 *
	 * @param string $name Property name.
	 * @return mixed Result.
	 */
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
	* Access the data fields of the record. Can also access a $named field.
	*
	* @param string $name Optionally included field name.
	* @return array|string Entire data array if $name is empty, otherwise the value from the named
	*         field.
	*/
	public function data($name = null) {
		return empty($name) ? $this->_data : $this->__get($name);
	}

	/**
	* Access the errors of the record.
	*
	* @param array|string $field If an array, overwrites `$this->_errors`. If a string, and $value
	*        is not null, sets the corresponding key in $this->_errors to $value
	* @param string $value Value to set.
	* @return array|string Either the $this->_errors array, or single value from it.
	*/
	public function errors($field = null, $value = null) {
		if ($field === null) {
			return $this->_errors;
		}
		if (is_array($field)) {
			$this->_errors = $field;
			return $this->_errors;
		}
		if ($value === null && isset($this->_errors[$field])) {
			return $this->_errors[$field];
		}
		if ($value !== null) {
			return $this->_errors[$field] = $value;
		}
		return $value;
	}

	/**
	* Magic method that allows calling of model methods on this record instance, i.e.:
	* {{{
	* $record->validates();
	* }}}
	*
	* @param string $method
	* @param array $params
	* @return mixed
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
	* @return boolean `True` if the record was `read` from the data-source, or has been `create`d
	*         and `save`d. Otherwise `false`.
	*/
	public function exists() {
		return $this->_exists;
	}

	/**
	 * Called after a `Record` is saved. Updates the object's internal state to reflect the
	 * corresponding database record, and sets the `Record`'s primary key, if this is a
	 * newly-created object.
	 *
	 * @param $id The ID to assign, where applicable.
	 * @return void
	 */
	public function update($id = null) {
		if ($id) {
			$id = (array) $id;
			$model = $this->_model;
			foreach ((array) $model::meta('key') as $i => $key) {
				$this->__set($key, $id[$i]);
			}
		}
		$this->_exists = true;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array.
	 *
	 * @param string $format currently only `array`
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
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