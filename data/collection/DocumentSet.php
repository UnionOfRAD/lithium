<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

class DocumentSet extends \lithium\data\Collection {

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

				if (!is_object($next) && ($model = $this->_model)) {
					$next = $model::connection()->cast($this, $next);
					$current->_data[$key] = $next;
				}
				$current = $next;
			}
			$current->__set(end($path), $value);
		}

		if (is_array($value)) {
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
		$model = $this->_model;

		if (!isset($this->_data[$offset]) && !$data = $this->_populate(null, $offset)) {
			return $null;
		}
		if (is_array($data = $this->_data[$offset]) && $model) {
			$this->_data[$offset] = $model::connection()->cast($this, $data);
		}
		if (isset($this->_data[$offset])) {
			return $this->_data[$offset];
		}
		return $null;
	}

	/**
	 * Rewinds the collection of sub-`Document`s to the beginning and returns the first one found.
	 *
	 * @return object Returns the first `Document` object instance in the collection.
	 */
	public function rewind() {
		$data = parent::rewind() ?: $this->_populate();
		$key = key($this->_data);

		if (is_object($data)) {
			return $data;
		}

		if (isset($this->_data[$key])) {
			return $this->offsetGet($key);
		}
	}

	public function current() {
		return $this->offsetGet(key($this->_data));
	}

	/**
	 * Returns the next document in the set, and advances the object's internal pointer. If the end
	 * of the set is reached, a new document will be fetched from the data source connection handle
	 * If no more documents can be fetched, returns `null`.
	 *
	 * @return mixed Returns the next document in the set, or `null`, if no more documents are
	 *         available.
	 */
	public function next() {
		$prev = key($this->_data);
		$this->_valid = !(next($this->_data) === false && key($this->_data) === null);
		$cur = key($this->_data);

		if (!$this->_valid && $cur !== $prev && $cur !== null) {
			$this->_valid = true;
		}
		$this->_valid = $this->_valid ?: !is_null($this->_populate());
		return $this->_valid ? $this->offsetGet(key($this->_data)) : null;
	}

	public function export(array $options = array()) {
		$map = function($doc) use ($options) {
			return is_array($doc) ? $doc : $doc->export();
		};
		return array_map($map, $this->_data);
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
		if ($this->closed() || !($model = $this->_model)) {
			return;
		}
		$conn = $model::connection();

		if (($data = $data ?: $this->_result->next()) === null) {
			return $this->close();
		}
		$options = array('exists' => true, 'first' => true, 'pathKey' => $this->_pathKey);
		return $this->_data[] = $conn->cast($this, array($key => $data), $options);
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