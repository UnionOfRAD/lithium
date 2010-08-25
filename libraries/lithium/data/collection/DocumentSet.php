<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

use \Iterator;
use \lithium\data\Source;
use \lithium\util\Collection;

class DocumentSet extends \lithium\data\Collection {

	/**
	 * The class dependencies for `Document`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'entity' => 'lithium\data\entity\Document',
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
		foreach ($values as $key => $val) {
			$this[$key] = $val;
		}
	}

	/**
	 * Allows document fields to be accessed as array keys, i.e. `$document['_id']`.
	 *
	 * @param mixed $offset String or integer indicating the offset or index of a document in a set,
	 *              or the name of a field in an individual document.
	 * @return mixed Returns either a sub-object in the document, or a scalar field value.
	 */
	public function offsetGet($offset) {
		$data = null;
		$null  = null;

		if (!isset($this->_data[$offset]) && !$data = $this->_populate(null, $offset)) {
			return $null;
		}
		$data = $data ?: $this->_data[$offset];

		if (is_a($data, $this->_classes['entity'])) {
			return $data;
		}

		if ($this->_isComplexType($data)) {
			$this->_data[$offset] = $this->_relation('entity', $offset, $this->_data[$offset]);
		}
		return $this->_data[$offset];
	}

	/**
	 * Rewinds the collection of sub-`Document`s to the beginning and returns the first one found.
	 *
	 * @return object Returns the first `Document` object instance in the collection.
	 */
	public function rewind() {
		$data = parent::rewind();
		$key = key($this->_data);

		if (is_a($data, $this->_classes['entity'])) {
			return $data;
		}

		if ($this->_isComplexType($data)) {
			$this->_data[$key] = $this->_relation('entity', $key, $this->_data[$key]);
		}
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}

	public function current() {
		return $this->offsetGet(key($this->_data));
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
		return $this->_valid ? $this->offsetGet(key($this->_data)) : null;
	}

	public function export(Source $dataSource, array $options = array()) {
		$map = function($doc) use ($dataSource, $options) {
			return is_array($doc) ? $doc : $doc->export($dataSource, $options);
		};
		return array_map($map, $this->_data);
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
	 * @param string $key The key name to which the related object is assigned.
	 * @param array $data The internal data of the related object.
	 * @param array $options Any other options to pass when instantiating the related object.
	 * @return object Returns a new `Document` object instance.
	 */
	protected function _relation($classType, $key, $data, $options = array()) {
		$parent = $this;
		$model = $this->_model;

		if (is_object($data) && $data instanceof Document) {
			$data->assignTo($this, compact('model', 'pathKey'));
			return $data;
		}
		$options += compact('model', 'data', 'parent');
		return new $this->_classes[$classType]($options);
	}
}

?>