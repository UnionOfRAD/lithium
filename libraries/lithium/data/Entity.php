<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use \RuntimeException;

/**
 * `Entity` class. Represents data such as a row or document in a database. Entities have fields
 * (often known as columns in databases).
 */
class Entity extends \lithium\core\Object {

	/**
	 * Namespaced name of model that this record is linked to.
	 */
	protected $_model = null;

	/**
	 * Associative array of the records fields with values
	 */
	protected $_data = array();

	/**
	 * If this object is chained off of an originally queried object, contains an instance of
	 * `Relationship` defining the relationship from the origin object to this one.
	 *
	 * @var object
	 */
	protected $_relationship = null;

	/**
	 * An array containing all related records and recordsets, keyed by relationship name, as
	 * defined in the bound model class.
	 *
	 * @var array
	 */
	protected $_relationships = array();

	/**
	 * If this record is chained off of another, contains the origin object.
	 *
	 * @var object
	 */
	protected $_parent = null;

	/**
	 * A reference to the object that originated this record set; usually an instance of
	 * `lithium\data\Source` or `lithium\data\source\Database`. Used to load column definitions and
	 * lazy-load records.
	 *
	 * @var object
	 */
	protected $_handle = null;

	/**
	 * Validation errors
	 */
	protected $_errors = array();

	/**
	 * An array of flags to track which fields in this record have been modified, where the keys
	 * are field names, and the values are always `true`. If, for example, a change to a field is
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
	 * A local copy of the schema definition. This is the same as `lithium\data\Model::$_schema`,
	 * but can be defined here if this is a one-off object or class used for a single purpose, i.e.
	 * to create a form.
	 *
	 * @var array
	 */
	protected $_schema = array();

	/**
	 * Auto configuration.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'classes' => 'merge', 'parent', 'relationship',
		'handle', 'schema', 'data', 'model', 'exists'
	);

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
	public function &__get($name) {
		$data = null;
		$null  = null;

		if (isset($this->_relationships[$name])) {
			return $this->_relationships[$name];
		}

		if (($model = $this->_model) && $this->_handle) {
			foreach ($model::relations() as $relation => $config) {
				$linkKey = $config->data('fieldName');
				$type = $config->data('type') == 'hasMany' ? 'set' : 'entity';
				$class = $this->_classes[$type];

				if ($linkKey === $name) {
					$data = isset($this->_data[$name]) ? $this->_data[$name] : array();
					$this->_relationships[$name] = new $class();
					return $this->_relationships[$name];
				}
			}
		}
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		return $null;
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
	 * Overloading for calling `isset()` or `empty()` on inaccessible properties.
	 *
	 * @param string $name Property name.
	 * @return mixed Result.
	 */
	public function __isset($name) {
		return array_key_exists($name, $this->_data);
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
		if (!($model = $this->_model) || !method_exists($model, $method)) {
			throw new RuntimeException("No model bound or unhandled method call '{$method}'.");
		}
		array_unshift($params, $this);
		$class = $model::invokeMethod('_instance');
		return call_user_func_array(array(&$class, $method), $params);
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
	 * Returns the model which this entity is bound to.
	 *
	 * @return string The fully qualified model class name.
	 */
	public function model() {
		return $this->_model;
	}

	public function schema($field = null) {
		switch (true) {
			case ($this->_schema):
				$schema = $this->_schema;
			break;
			case ($model = $this->_model):
				$schema = $model::schema();
			break;
			default:
				$schema = array();
			break;
		}
		if ($field) {
			return isset($self->_schema[$field]) ? $self->_schema[$field] : null;
		}
		return $schema;
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
	public function update($id = null, $data = array()) {
		if ($id) {
			$id = (array) $id;
			$model = $this->_model;
			foreach ((array) $model::meta('key') as $i => $key) {
				$this->_data[$key] = $id[$i];
			}
			foreach ($data as $key => $value) {
				$this->_data[$key] = $value;
			}
		}
		$this->_exists = true;
	}

	/**
	 * Configures protected properties of a `Record` so that it is parented to `$parent`.
	 *
	 * @param object $parent
	 * @param array $config
	 * @return void
	 */
	public function assignTo($parent, array $config = array()) {
		foreach ($config as $key => $val) {
			$this->{'_' . $key} = $val;
		}
		$this->_parent =& $parent;
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

	/**
	 * Instantiates a new `Document` object as a descendant of the current object, and sets all
	 * default values and internal state.
	 *
	 * @param string $classType The type of class to create, either `'entity'` or `'set'`.
	 * @param array $data
	 * @param array $options
	 * @return object Returns a new `Document` object instance.
	 */
	protected function _relation($classType, $key, $data, $options = array()) {
		$parent = $this;
		$key = ($key === null) ? count($this->_data) : $key;
		$pathKey = trim("{$this->_pathKey}.{$key}", '.');

		if (($key || $key === 0) && $model = $this->_model) {
			foreach ($model::relations() as $name => $relation) {
				if ($key === $relation->data('fieldName')) {
					$model = $relation->data('to');
					break;
				}
			}
		}

		if (is_object($data) && $data instanceof Entity) {
			$data->assignTo($this, compact('model', 'pathKey'));
			return $data;
		}

		$exists = $this->_exists;
		$options += compact('model', 'data', 'parent', 'exists', 'pathKey');
		return new $this->_classes[$classType]($options);
	}
}

?>