<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use BadMethodCallException;
use UnexpectedValueException;
use lithium\data\Collection;

/**
 * `Entity` is a smart data object which represents data such as a row or document in a
 * database. Entities have fields (often known as columns in databases), and track changes to its
 * fields, as well as associated validation errors, etc.
 *
 * The `Entity` class can also be used as a base class for your own custom data objects, and is the
 * basis for generating forms with the `Form` helper.
 *
 * @see lithium\template\helper\Form
 */
class Entity extends \lithium\core\Object {

	/**
	 * Fully-namespaced class name of model that this record is bound to. Instance methods declared
	 * in the model may be called on the entity. See the `Model` class documentation for more
	 * information.
	 *
	 * @see lithium\data\Model
	 * @see lithium\data\Entity::__call()
	 * @var string
	 */
	protected $_model = null;

	/**
	 * Associative array of the entity's fields and values.
	 *
	 * @var array
	 */
	protected $_data = array();

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
	 * The list of validation errors associated with this object, where keys are field names, and
	 * values are arrays containing one or more validation error messages.
	 *
	 * @see lithium\data\Entity::errors()
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * Contains the values of updated fields. These values will be persisted to the backend data
	 * store when the document is saved.
	 *
	 * @var array
	 */
	protected $_updated = array();

	/**
	 * Contains the callables for recognized virtual fields. These values will not be persisted.
	 * Virtual fields are derived fields that are made available via functions in the model.
	 *
	 * @see lithium\data\Entity::_initVirtual();
	 * @var mixed - array when holding data
	 *            - null when not yet initialized
	 */
	protected $_virtual = null;

	/**
	 * An array of key/value pairs corresponding to fields that should be updated using atomic
	 * incrementing / decrementing operations. Keys match field names, and values indicate the value
	 * each field should be incremented or decremented by.
	 *
	 * @see lithium\data\Entity::increment()
	 * @see lithium\data\Entity::decrement()
	 * @var array
	 */
	protected $_increment = array();

	/**
	 * A flag indicating whether or not this entity exists. Set to `false` if this is a
	 * newly-created entity, or if this entity has been loaded and subsequently deleted. Set to
	 * `true` if the entity has been loaded from the database, or has been created and subsequently
	 * saved.
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
		'classes' => 'merge', 'parent', 'schema', 'data',
		'model', 'exists', 'pathKey', 'relationships'
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
		$defaults = array('model' => null, 'data' => array(), 'relationships' => array());
		parent::__construct($config + $defaults);
	}

	/**
	 * Initializes the virtual field access.
	 *
	 * Only called when fields are accessed that are not yet in _updated or 
	 * _relationships.
	 * Information is retrieved the static property `_properties` defined in the model
	 * where the Entity is bound too.
	 * {{{
	 * class Examples extends \lithium\data\Model {
	 *
	 *    public static $_properties = array(
	 *       'field_x' => 'MyFieldX',
	 *       'field_y' => array('SomeOtherField', 'set' => false),
	 *       'field_z' => array('get' => 'mySpecialFunction')
	 *    );
	 *
	 * // rest of the class ...
	 * }}}
	 *
	 * The example above will introduce the fields 'field_x', 'field_z' that can be used
	 * like normal fields, eg `echo $entity->field_x;`.
	 * When a field is read, set, or isset, methods defined in the Model will be called.
	 * For 'field_x', those will be 'getMyFieldX()', 'setMyFieldX()', 'issetMyFieldX()'; 
	 * the value given prefixed with 'get', 'set' and 'isset'. As first paramater they
	 * should accept $entity, and only 'set' needs a second parameter for the $value to 
	 * be set.
	 * For 'field_y', no setter method is called as indicated by setting 'set' to false. 
	 * The entity will set the value just to _updated[] like any other set action.
	 * For 'field_z', the getter method is named 'mySpecialFunction', for 'set' and 'isset'
	 * the method base name will be 'field_z'.
	 */
	protected function _initVirtual() {
		$this->_virtual = array('get' => array(), 'set' => array(), 'isset' => array());
		if (!$model = $this->_model) {
			return;
		}
		if (!class_exists($model) || !isset($model::$_properties) || !is_array($model::$_properties)) {
			return;
		}
		foreach ($model::$_properties as $property => $options) {
			if (!is_string($property)) {
				continue;
			}
			$defaults = array(
				0 => $property, 'get' => true, 'set' => true, 'isset' => true
			);
			$options = (array)$options + $defaults;
			$baseName = $options[0];
			foreach (array('get', 'set', 'isset') as $key) {
				$name = $options[$key];
					if (!$name) {
						continue;
					}
					if ($name === true) {
						$name = $key . $baseName;
					}
					$this->_virtual[$key][$property] = $name;
			}                                    
		}
	}

	protected function _init() {
		parent::_init();
		$this->_updated = $this->_data;
	}

	/**
	 * Overloading for reading inaccessible properties.
	 *
	 * Check '_relationships' and '_updated' first. When
	 * not found, make sure that '_virtual' is initialized and
	 * try to access it as a virtual property.
	 * If nothing is found, null will be returned.
	 *
	 * @param string $name Property name.
	 * @return mixed Result.
	 */
	public function &__get($name) {
		if (isset($this->_relationships[$name])) {
			return $this->_relationships[$name];
		}
		if (isset($this->_updated[$name])) {
			return $this->_updated[$name];
		}
		if (!isset($this->_virtual)) {
			$this->_initVirtual();
		}
		if (isset($this->_virtual['get'][$name])) {
			$getter = $this->_virtual['get'][$name];
			$result =  $this->$getter();
			return $result;
		}
		$null = null;
		return $null;
	}

	/**
	 * Overloading for writing to inaccessible properties.
	 *
	 * If called with only an array as first parameter, call __set
	 * on the individual entries.
	 *
	 * First checks if the property is already known. Otherwise try it via
	 * the virtual setter. Else treat is a new normal property.
	 *
	 * @param string $name Property name.
	 * @param string $value Property value.
	 * @return mixed Result.
	 */
	public function __set($name, $value = null) {
		if (is_array($name) && !$value) {
			return array_map(array(&$this, '__set'), array_keys($name), array_values($name));
		}
		if (isset($this->_updated[$name])) {
			return $this->_updated[$name] = $value;
		}
		if (!isset($this->_virtual)) {
			$this->_initVirtual();
		}
		if (isset($this->_virtual['set'][$name])) {
			$setter = $this->_virtual['set'][$name];
			return $this->$setter($value);
		}
		return $this->_updated[$name] = $value;
	}

	/**
	 * Overloading for calling `isset()` or `empty()` on inaccessible properties.
	 *
	 * Will check existing properties first before going virtual.
	 *
	 * @param string $name Property name.
	 * @return mixed Result.
	 */
	public function __isset($name) {
		if (isset($this->_updated[$name]) || isset($this->_relationships[$name])) {
			return true;
		}
		if (!isset($this->_virtual)) {
			$this->_initVirtual();
		}
		if (isset($this->_virtual['isset'][$name])) {
			$issetter = $this->_virtual['isset'][$name];
			return $this->$issetter();
		}
		return false;
	}

	/**
	 * Magic method that allows calling of model methods on this record instance, i.e.:
	 * {{{
	 * $record->validates();
	 * }}}
	 *
	 * @see lithium\data\Model::instanceMethods
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public function __call($method, $params) {
		if ($model = $this->_model) {
			$methods = $model::instanceMethods();
			array_unshift($params, $this);

			if (method_exists($model, $method)) {
				$class = $model::invokeMethod('_object');
				return call_user_func_array(array(&$class, $method), $params);
			}
			if (isset($methods[$method]) && is_callable($methods[$method])) {
				return call_user_func_array($methods[$method], $params);
			}
		}
		$message = "No model bound or unhandled method call `{$method}`.";
		throw new BadMethodCallException($message);
	}

	/**
	 * Allows several properties to be assigned at once, i.e.:
	 * {{{
	 * $record->set(array('title' => 'Lorem Ipsum', 'value' => 42));
	 * }}}
	 *
	 * @param array $data An associative array of fields and values to assign to this `Entity`
	 *        instance.
	 * @return void
	 */
	public function set(array $data) {
		foreach ($data as $name => $value) {
			$this->__set($name, $value);
		}
	}

	/**
	 * Access the data fields of the record. Can also access a $named field.
	 *
	 * @param string $name Optionally included field name.
	 * @return mixed Entire data array if $name is empty, otherwise the value from the named field.
	 */
	public function data($name = null) {
		if ($name) {
			return $this->__get($name);
		}
		return $this->to('array');
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
		$schema = array();

		switch (true) {
			case ($this->_schema):
				$schema = $this->_schema;
			break;
			case ($model = $this->_model):
				$schema = $model::schema();
			break;
		}
		if ($field) {
			return isset($schema[$field]) ? $schema[$field] : null;
		}
		return $schema;
	}

	/**
	 * Access the errors of the record.
	 *
	 * @see lithium\data\Entity::$_errors
	 * @param array|string $field If an array, overwrites `$this->_errors`. If a string, and
	 *        `$value` is not `null`, sets the corresponding key in `$this->_errors` to `$value`.
	 * @param string $value Value to set.
	 * @return mixed Either the `$this->_errors` array, or single value from it.
	 */
	public function errors($field = null, $value = null) {
		if ($field === null) {
			return $this->_errors;
		}
		if (is_array($field)) {
			return ($this->_errors = $field);
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
	 * Called after an `Entity` is saved. Updates the object's internal state to reflect the
	 * corresponding database entity, and sets the `Entity` object's key, if this is a newly-created
	 * object. **Do not** call this method if you intend to update the database's copy of the
	 * entity. Instead, see `Model::save()`.
	 *
	 * @see lithium\data\Model::save()
	 * @param mixed $id The ID to assign, where applicable.
	 * @param array $data Any additional generated data assigned to the object by the database.
	 * @param array $options Method options:
	 *              - `'materialize'` _boolean_: Determines whether or not the flag should be set
	 *                that indicates that this entity exists in the data store. Defaults to `true`.
	 * @return void
	 */
	public function sync($id = null, array $data = array(), array $options = array()) {
		$defaults = array('materialize' => true);
		$options += $defaults;
		$model = $this->_model;
		$key = array();

		if ($options['materialize']) {
			$this->_exists = true;
		}
		if ($id && $model) {
			$key = $model::meta('key');
			$key = is_array($key) ? array_combine($key, $id) : array($key => $id);
		}
		$this->_data = $this->_updated = ($key + $data + $this->_updated);
	}

	/**
	 * Safely (atomically) increments the value of the specified field by an arbitrary value.
	 * Defaults to `1` if no value is specified. Throws an exception if the specified field is
	 * non-numeric.
	 *
	 * @param string $field The name of the field to be incremented.
	 * @param string $value The value to increment the field by. Defaults to `1` if this parameter
	 *               is not specified.
	 * @return integer Returns the current value of `$field`, based on the value retrieved from the
	 *         data source when the entity was loaded, plus any increments applied. Note that it may
	 *         not reflect the most current value in the persistent backend data source.
	 * @throws UnexpectedValueException Throws an exception when `$field` is set to a non-numeric
	 *         type.
	 */
	public function increment($field, $value = 1) {
		if (!isset($this->_updated[$field])) {
			return $this->_updated[$field] = $value;
		}
		if (!is_numeric($this->_updated[$field])) {
			throw new UnexpectedValueException("Field '{$field}' cannot be incremented.");
		}
		return $this->_updated[$field] += $value;
	}

	/**
	 * Decrements a field by the specified value. Works identically to `increment()`, but in
	 * reverse.
	 *
	 * @see lithium\data\Entity::increment()
	 * @param string $field The name of the field to decrement.
	 * @param string $value The value by which to decrement the field. Defaults to `1`.
	 * @return integer Returns the new value of `$field`, after modification.
	 */
	public function decrement($field, $value = 1) {
		return $this->increment($field, $value * -1);
	}

	/**
	 * Gets the array of fields modified on this entity.
	 *
	 * @return array Returns an array where the keys are entity field names, and the values are
	 *         `true` for changed fields.
	 */
	public function modified() {
		$fields = array_fill_keys(array_keys($this->_data), false);
		foreach ($this->_updated as $field => $value) {
			if (is_object($value) && method_exists($value, 'modified')) {
				if (!isset($this->_data[$field])) {
					$fields[$field] = true;
					continue;
				}
				$modified = $value->modified();
				$fields[$field] = $modified === true || is_array($modified) && in_array(true, $modified, true);
			} else {
				$fields[$field] = !isset($fields[$field]) || $this->_data[$field] !== $this->_updated[$field];
			}
		}
		return $fields;
	}

	public function export(array $options=array()) {
		$options += array('virtual' => false);
		$export = array(
			'exists'    => $this->_exists,
			'data'      => $this->_data,
			'update'    => $this->_updated,
			'increment' => $this->_increment
		);
		if ($options['virtual']) {
			$export['virtual'] = $this->_exportVirtual();
		}
		return $export;
	}

	protected function _exportVirtual() {                
		if (!isset($this->_virtual)) {
			$this->_initVirtual();
		}
		$export = array();
		foreach ($this->_virtual['get'] as $name => $getter) {
			$export[$name] = $this->$getter();
		}
		return $export;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array.
	 *
	 * Optionally exports virtual fields.
	 *
	 * @param string $format currently only `array`
	 * @param array $options 'virtual' true will export virtual fields. Default false
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
		$options += array('virtual' => false);
		switch ($format) {
			case 'array':
				$data = $this->_updated;
				$rel = array_map(function($obj) { return $obj->data(); }, $this->_relationships);
				$extra = array();
				if ($options['virtual']) {
					$extra = $this->_exportVirtual();
				}
				$data = $rel + $data + $extra;
				$result = Collection::toArray($data, $options);
			break;
			default:
				$result = $this;
			break;
		}
		return $result;
	}
}

?>