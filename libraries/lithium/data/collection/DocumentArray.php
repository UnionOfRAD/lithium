<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

use lithium\data\Source;

class DocumentArray extends \lithium\data\Collection {

	/**
	 * PHP magic method used to check the presence of a field as document properties, i.e.
	 * `$document->_id`.
	 *
	 * @param $name The field name, as specified with an object property.
	 * @return boolean Returns `true` if the field specified in `$name` exists, otherwise `false`.
	 */
	public function __isset($name) {
		return isset($this->_data[$name]);
	}

	/**
	 * PHP magic method used when unset() is called on a `Document` instance.
	 * Use case for this would be when you wish to edit a document and remove a field, i.e.:
	 * {{{
	 * 	$doc = Post::find($id);
	 * 	unset($doc->fieldName);
	 * 	$doc->save();
	 * }}}
	 *
	 * @param unknown_type $name
	 * @return void
	 */
	public function __unset($name) {
		unset($this->_data[$name]);
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @param string $offset The offset to retrieve.
	 * @return mixed Value at offset.
	 */
	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	public function offsetSet($offset, $data) {
		if ($model = $this->_model) {
			$data = $model::connection()->cast($model, array($this->_pathKey => $data), array(
				'first' => true
			));
		}
		if ($offset) {
			return $this->_data[$offset] = $data;
		}
		return $this->_data[] = $data;
	}

	/**
	 * Rewinds the collection of sub-`Document`s to the beginning and returns the first one found.
	 *
	 * @return object Returns the first `Document` object instance in the collection.
	 */
	public function rewind() {
		$data = parent::rewind();
		$key = key($this->_data);
		return $this->offsetGet($key);
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
		return $this->_valid ? $this->offsetGet(key($this->_data)) : null;
	}

	public function export(Source $dataSource, array $options = array()) {
		$result = array();

		foreach ($this->_data as $key => $doc) {
			if (is_object($doc) && method_exists($doc, 'export')) {
				$result[$key] = $doc->export($dataSource, $options);
				continue;
			}
			$result[$key] = $doc;
		}
		return $result;
	}

	protected function _populate($data = null, $key = null) {}
}

?>