<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data;

use BadMethodCallException;
use UnexpectedValueException;
use lithium\data\Collection;
use lithium\analysis\Inspector;
use lithium\core\AutoConfigurable;

/**
 * `Entity` is a smart data object which represents data such as a row or document in a
 * database. Entities have fields (often known as columns in databases), and track changes to its
 * fields, as well as associated validation errors, etc.
 *
 * The `Entity` class can also be used as a base class for your own custom data objects, and is the
 * basis for generating forms with the `Form` helper.
 *
 * Instances of `lithium\data\Entity` or any subclass of it may be serialized. This
 * operation however isn't lossless. The documentation of the `serialize()` method has
 * more information on the limitations.
 *
 * @see lithium\template\helper\Form
 * @see lithium\data\Entity::serialize()
 */
class Entity implements \Serializable {

	use AutoConfigurable;

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
	protected $_data = [];

	/**
	 * An array containing all related records and recordsets, keyed by relationship name, as
	 * defined in the bound model class.
	 *
	 * @var array
	 */
	protected $_relationships = [];

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
	protected $_errors = [];

	/**
	 * Contains the values of updated fields. These values will be persisted to the backend data
	 * store when the document is saved.
	 *
	 * @var array
	 */
	protected $_updated = [];

	/**
	 * An array of key/value pairs corresponding to fields that should be updated using atomic
	 * incrementing / decrementing operations. Keys match field names, and values indicate the value
	 * each field should be incremented or decremented by.
	 *
	 * @see lithium\data\Entity::increment()
	 * @see lithium\data\Entity::decrement()
	 * @var array
	 */
	protected $_increment = [];

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
	protected $_schema = [];

	/**
	 * Hold the "data export" handlers where the keys are fully-namespaced class
	 * names, and the values are closures that take an instance of the class as a
	 * parameter, and return an array or scalar value that the instance represents.
	 *
	 * @see lithium\data\Entity::to()
	 * @var array
	 */
	protected $_handlers = [];

	/**
	 * Auto configuration.
	 *
	 * @var array
	 */
	protected $_autoConfig = [
		'parent',
		'schema',
		'data',
		'model',
		'exists',
		'pathKey',
		'relationships',
		'handlers'
	];

	protected function _init() {
		$this->_updated = $this->_data;
	}

	/**
	 * Overloading for reading inaccessible properties.
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
		$null = null;
		return $null;
	}

	/**
	 * PHP magic method used when setting properties on the `Entity` instance, i.e.
	 * `$entity->title = 'Lorem Ipsum'`.
	 *
	 * @param string $name The name of the field/property to write to, i.e. `title` in the above example.
	 * @param mixed $value The value to write, i.e. `'Lorem Ipsum'`.
	 * @return void
	 */
	public function __set($name, $value) {
		unset($this->_increment[$name]);
		$this->_updated[$name] = $value;
	}

	/**
	 * Overloading for calling `isset()` or `empty()` on inaccessible properties.
	 *
	 * @param string $name Property name.
	 * @return mixed Result.
	 */
	public function __isset($name) {
		return isset($this->_updated[$name]) || isset($this->_relationships[$name]);
	}

	/**
	 * Magic method that allows calling of model methods on this record instance.
	 *
	 * ```
	 * $post->validates();
	 * ```
	 *
	 * @param string $method Method name caught by `__call()`.
	 * @param array $params Arguments given to the above `$method` call.
	 * @return mixed
	 */
	public function __call($method, $params) {
		if (($model = $this->_model) && method_exists($model, 'object')) {
			array_unshift($params, $this);
			return call_user_func_array([$model::object(), $method], $params);
		}
		$message = "No model bound to call `{$method}`.";
		throw new BadMethodCallException($message);
	}

	/**
	 * Allows several properties to be assigned at once, i.e.:
	 * ```
	 * $record->set(['title' => 'Lorem Ipsum', 'value' => 42]);
	 * ```
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

	/**
	 * Returns the parent object of this object, if any.
	 *
	 * @return object Returns the object that contains this object, or `null`.
	 */
	public function parent() {
		return $this->_parent;
	}

	public function schema($field = null) {
		$schema = null;

		switch (true) {
			case (is_object($this->_schema)):
				$schema = $this->_schema;
			break;
			case ($model = $this->_model):
				$schema = $model::schema();
			break;
		}
		if ($schema) {
			return $field ? $schema->fields($field) : $schema;
		}
		return [];
	}

	/**
	 * Access the errors of the record.
	 *
	 * @see lithium\data\Entity::$_errors
	 * @param array|string $field If an array, overwrites `$this->_errors` if it is empty,
	 *        if not, merges the errors with the current values. If a string, and `$value`
	 *        is not `null`, sets the corresponding key in `$this->_errors` to `$value`.
	 *        Setting `$field` to `false` will reset the current state.
	 * @param string $value Value to set.
	 * @return mixed Either the `$this->_errors` array, or single value from it.
	 */
	public function errors($field = null, $value = null) {
		if ($field === false) {
			return ($this->_errors = []);
		}
		if ($field === null) {
			return $this->_errors;
		}
		if (is_array($field)) {
			return ($this->_errors = array_merge_recursive($this->_errors, $field));
		}
		if ($value === null && isset($this->_errors[$field])) {
			return $this->_errors[$field];
		}
		if ($value !== null) {
			if (array_key_exists($field, $this->_errors)) {
				$current = $this->_errors[$field];
				return ($this->_errors[$field] = array_merge((array) $current, (array) $value));
			}
			return ($this->_errors[$field] = $value);
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
	 *        - `'materialize'` _boolean_: Determines whether or not the flag should be set
	 *          that indicates that this entity exists in the data store. Defaults to `true`.
	 *        - `'dematerialize'` _boolean_: If set to `true`, indicates that this entity has
	 *          been deleted from the data store and no longer exists. Defaults to `false`.
	 */
	public function sync($id = null, array $data = [], array $options = []) {
		$defaults = ['materialize' => true, 'dematerialize' => false];
		$options += $defaults;
		$model = $this->_model;
		$key = [];

		if ($options['materialize']) {
			$this->_exists = true;
		}
		if ($options['dematerialize']) {
			$this->_exists = false;
		}
		if ($id && $model) {
			$key = $model::meta('key');
			$key = is_array($key) ? array_combine($key, $id) : [$key => $id];
		}
		$this->_increment = [];
		$this->_data = $this->_updated = ($key + $data + $this->_updated);
	}

	/**
	 * Safely (atomically) increments the value of the specified field by an arbitrary value.
	 * Defaults to `1` if no value is specified. Throws an exception if the specified field is
	 * non-numeric.
	 *
	 * @param string $field The name of the field to be incremented.
	 * @param integer|string $value The value to increment the field by. Defaults to `1` if
	 *        this parameter is not specified.
	 * @return integer Returns the current value of `$field`, based on the value retrieved from the
	 *         data source when the entity was loaded, plus any increments applied. Note that it may
	 *         not reflect the most current value in the persistent backend data source.
	 * @throws UnexpectedValueException Throws an exception when `$field` is set to a non-numeric
	 *         type.
	 */
	public function increment($field, $value = 1) {
		if (!isset($this->_updated[$field])) {
			$this->_updated[$field] = 0;
		} elseif (!is_numeric($this->_updated[$field])) {
			throw new UnexpectedValueException("Field `'{$field}'` cannot be incremented.");
		}

		if (!isset($this->_increment[$field])) {
			$this->_increment[$field] = 0;
		}
		$this->_increment[$field] += $value;

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
	 * Gets the current state for a given field or, if no field is given, gets the array of
	 * fields modified on this entity.
	 *
	 * @param string The field name to check its state.
	 * @return mixed Returns `true` if a field is given and was updated, `false` otherwise and
	 *         `null` if the field was not set at all. If no field is given returns an arra
	 *         where the keys are entity field names, and the values are `true` for changed
	 *         fields.
	 */
	public function modified($field = null) {
		if ($field) {
			if (!isset($this->_updated[$field]) && !isset($this->_data[$field])) {
				return null;
			}

			if (!array_key_exists($field, $this->_updated)) {
				return false;
			}

			$value = $this->_updated[$field];
			if (is_object($value) && method_exists($value, 'modified')) {
				$modified = $value->modified();
				return $modified === true || is_array($modified) && in_array(true, $modified, true);
			}

			$isSet = isset($this->_data[$field]);
			return !$isSet || ($this->_data[$field] !== $this->_updated[$field]);
		}

		$fields = array_fill_keys(array_keys($this->_data), false);

		foreach ($this->_updated as $field => $value) {
			if (is_object($value) && method_exists($value, 'modified')) {
				if (!isset($this->_data[$field])) {
					$fields[$field] = true;
					continue;
				}
				$modified = $value->modified();

				$fields[$field] = (
					$modified === true || is_array($modified) && in_array(true, $modified, true)
				);
			} else {
				$fields[$field] = (
					!isset($fields[$field]) || $this->_data[$field] !== $this->_updated[$field]
				);
			}
		}
		return $fields;
	}

	public function export(array $options = []) {
		return [
			'exists'    => $this->_exists,
			'data'      => $this->_data,
			'update'    => $this->_updated,
			'increment' => $this->_increment
		];
	}

	/**
	 * Configures protected properties of an `Entity` so that it is parented to `$parent`.
	 *
	 * @param object $parent
	 * @param array $config
	 */
	public function assignTo($parent, array $config = []) {
		foreach ($config as $key => $val) {
			$this->{'_' . $key} = $val;
		}
		$this->_parent =& $parent;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array.
	 *
	 * @param string $format Currently only `array`.
	 * @param array $options Options for converting:
	 *        - `'indexed'` _boolean_: Allows to control how converted data of nested collections
	 *          is keyed. When set to `true` will force indexed conversion of nested collection
	 *          data. By default `false` which will only index the root level.
	 * @return mixed
	 */
	public function to($format, array $options = []) {
		$defaults = ['handlers' => []];
		$options += $defaults;

		$options['handlers'] += $this->_handlers;
		switch ($format) {
			case 'array':
				$data = $this->_updated;
				$rel = array_map(function($obj) { return $obj->data(); }, $this->_relationships);
				$data = $rel + $data;
				$options['indexed'] = isset($options['indexed']) ? $options['indexed'] : false;
				$result = Collection::toArray($data, $options);
			break;
			case 'string':
				$result = $this->__toString();
			break;
			default:
				$result = $this;
			break;
		}
		return $result;
	}

	/**
	 * Returns a string representation of the `Entity` instance, based on the result of the
	 * `'title'` meta value of the bound model class.
	 *
	 * @return string Returns the generated title of the object.
	 */
	public function __toString() {
		return (string) $this->__call('title', []);
	}

	/**
	 * Prepares, enables and executes serialization of the object.
	 *
	 * Note: because of the limitations outlined below custom handlers
	 * and schema are ignored with serialized objects.
	 *
	 * Properties that hold anonymous functions are also skipped. Some of these
	 * can almost be reconstructed (`_handlers`) others cannot (`schema`).
	 *
	 * @return string Serialized properties of the object.
	 */
	public function serialize() {
		return serialize($this->__serialize());
	}

	/**
	 * Prepares, enables and executes unserialization of the object.
	 *
	 * Restores state of the object including pulled results. Tries
	 * to restore `_handlers` by calling into `_init()`.
	 *
	 * @param string $data Serialized properties of the object.
	 * @return void
	 */
	public function unserialize($data) {
		$this->__unserialize(unserialize($data));
	}

	public function __serialize() {
		$vars = get_object_vars($this);
		unset($vars['_schema']);
		unset($vars['_config']['schema']);
		unset($vars['_handlers']);
		return $vars;
	}

	public function __unserialize($data) {
		static::_init();

		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}
	}
}

?>