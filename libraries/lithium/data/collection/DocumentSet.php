<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

use \Iterator;

class DocumentSet extends \lithium\data\Collection {

	/**
	 * An array containing all related documents, keyed by relationship name, as defined in the
	 * bound model class.
	 *
	 * @var array
	 */
	protected $_relationships = array();

	/**
	 * The class dependencies for `Document`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'entity' => '\lithium\data\entity\Document',
		'set' => __CLASS__
	);

	/**
	 * PHP magic method used when setting properties on the `Document` instance, i.e.
	 * `$document->title = 'Lorem Ipsum'`. If `$value` is a complex data type (i.e. associative
	 * array), it is wrapped in a sub-`Document` object before being appended.
	 *
	 * @param $name The name of the field/property to write to, i.e. `title` in the above example.
	 * @param $value The value to write, i.e. `'Lorem Ipsum'`.
	 * @return void
	 */
	public function __set($name, $value = null) {
		if (is_array($name) && !$value) {
			foreach ($name as $key => $value) {
				$this->__set($key, $value);
			}
			return;
		}

		if (is_string($name) && strpos($name, '.')) {
			$current = $this;
			$path = explode('.', $name);
			$length = count($path) - 1;

			for ($i = 0; $i < $length; $i++) {
				$key = $path[$i];
				$next = $current->__get($key);

				if (!$next instanceof Document) {
					$next = $current->_data[$key] = $this->_relation('set', $key, array());
				}
				$current = $next;
			}
			$current->__set(end($path), $value);
		}

		if ($this->_isComplexType($value) && !$value instanceof Iterator) {
			$value = $this->_relation('set', $name, $value);
		}
		$this->_data[$name] = $value;
		$this->_modified[$name] = true;
	}

	/**
	 * PHP magic method used to check the presence of a field as document properties, i.e.
	 * `$document->_id`.
	 *
	 * @param $name The field name, as specified with an object property.
	 * @return boolean True if the field specified in `$name` exists, false otherwise.
	 */
	public function __isset($name) {
		return isset($this->_data[$name]);
	}

	/**
	 * PHP magic method used when unset() is called on a `Document` instance.
	 * Use case for this would be when you wish to edit a document and remove a field, ie. :
	 * {{{ $doc = Post::find($id); unset($doc->fieldName); $doc->save(); }}}
	 *
	 * @param unknown_type $name
	 * @return unknown_type
	 */
	public function __unset($name) {
		unset($this->_data[$name]);
	}

	/**
	 * Allows several properties to be assigned at once.
	 *
	 * For example:
	 * {{{
	 * $doc->set(array('title' => 'Lorem Ipsum', 'value' => 42));
	 * }}}
	 *
	 * @param $values An associative array of fields and values to assign to the `Document`.
	 * @return void
	 */
	public function set($values) {
		$this->__set($values);
	}

	/**
	 * Allows document fields to be accessed as array keys, i.e. `$document['_id']`.
	 *
	 * @param mixed $offset String or integer indicating the offset or index of a document in a set,
	 *              or the name of a field in an individual document.
	 * @return mixed Returns either a sub-object in the document, or a scalar field value.
	 */
	public function offsetGet($offset) {
		return $this->__get($offset);
	}

	/**
	 * Rewinds the collection of sub-`Document`s to the beginning and returns the first one found.
	 *
	 * @return object Returns the first `Document` object instance in the collection.
	 */
	public function rewind() {
		return ($entity = parent::rewind()) ? $entity : $this->__get(key($this->_data));
	}

	/**
	 * Returns the next document in the set, and advances the object's internal pointer. If the end
	 * of the set is reached, a new document will be fetched from the data source connection handle
	 * (`$_handle`). If no more documents can be fetched, returns `null`.
	 *
	 * @return object|null Returns the next document in the set, or `null`, if no more documents are
	 *         available.
	 */
	public function next() {
		$prev = key($this->_data);
		$this->_valid = (next($this->_data) !== false);
		$cur = key($this->_data);

		if (!$this->_valid && $cur !== $prev && $cur !== null) {
			$this->_valid = true;
		}
		$this->_valid = $this->_valid ?: !is_null($this->_populate());
		return $this->_valid ? $this->__get(key($this->_data)) : null;
	}

	/**
	 * PHP magic method used when accessing fields as document properties, i.e. `$document->_id`.
	 *
	 * @param $name The field name, as specified with an object property.
	 * @return mixed Returns the value of the field specified in `$name`, and wraps complex data
	 *         types in sub-`Document` objects.
	 */
	public function &__get($name) {
		$data = null;
		$null  = null;

		if (strpos($name, '.')) {
			$current = $this;
			$path = explode('.', $name);
			$length = count($path) - 1;

			foreach ($path as $i => $key) {
				$current =& $current->__get($key);
				if (!$current instanceof Document && $i < $length) {
					return $null;
				}
			}
			return $current;
		}

		if (!isset($this->_data[$name]) && !$data = $this->_populate(null, $name)) {
			return $null;
		}
		$data = $data ?: $this->_data[$name];

		if ($this->_isComplexType($data) && !$data instanceof \lithium\data\Entity) {
			$this->_data[$name] = $this->_relation('entity', $name, $this->_data[$name]);
		}
		return $this->_data[$name];
	}

	/**
	 * Used by getter and setter methods to determine whether the value of data is a complex type
	 * that should be given its own sub-object withih the `Document`.
	 *
	 * @param mixed $data The data to be tested. This test is used to determine if `$data` should be
	 *              wrapped in an instance of `Document`.
	 * @return boolean Returns `false` if the value of `$data` is a scalar type or a one-dimensional
	 *         array of scalar values, otherwise returns `true`.
	 */
	protected function _isComplexType($data) {
		if (is_object($data) && (array) $data === array()) {
			return false;
		}
		if (is_scalar($data) || !$data) {
			return false;
		}
		if (is_array($data)) {
			if (array_keys($data) === range(0, count($data) - 1)) {
				if (array_filter($data, 'is_scalar') == array_filter($data)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Lazy-loads a document from a query using a reference to a database adapter and a query
	 * result resource.
	 *
	 * @param array $data
	 * @param mixed $key
	 * @return array
	 */
	protected function _populate($data = null, $key = null) {
		if ($this->closed()) {
			return;
		}
		if (($data = $data ?: $this->_handle->result('next', $this->_result, $this)) === null) {
			return $this->close();
		}
		return $this->_data[] = $this->_relation('entity', $key, $data, array('exists' => true));
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
				if ($relation && ($key === $relation->data('fieldName'))) {
					$model = $relation->data('to');
					break;
				}
			}
		}

		if (is_object($data) && $data instanceof Document) {
			$data->assignTo($this, compact('model', 'pathKey'));
			return $data;
		}
		$options += compact('model', 'data', 'parent', 'pathKey');
		return new $this->_classes[$classType]($options);
	}
}

?>