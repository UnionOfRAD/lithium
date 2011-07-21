<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

use lithium\util\Collection;

class DocumentArray extends \lithium\data\Collection {

	/**
	 * Indicates whether this array was part of a document loaded from a data source, or is part of
	 * a new document, or is in newly-added field of an existing document.
	 *
	 * @var boolean
	 */
	protected $_exists = false;

	/**
	 * Contains the original database value of the array. This value will be compared with the
	 * current value (`$_data`) to calculate the changes that should be sent to the database.
	 *
	 * @var array
	 */
	protected $_original = array();

	/**
	 * Holds an array of values that should be processed on initialization.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'data', 'model', 'result', 'query', 'parent', 'stats', 'pathKey', 'exists'
	);

	protected function _init() {
		parent::_init();
		$this->_original = $this->_data;
	}

	public function exists() {
		return $this->_exists;
	}

	public function sync($id = null, array $data = array()) {
		$this->_exists = true;
		$this->_original = $this->_data;
	}

	/**
	 * Adds conversions checks to ensure certain class types and embedded values are properly cast.
	 *
	 * @param string $format Currently only `array` is supported.
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
		$defaults = array('handlers' => array(
			'MongoId' => function($value) { return (string) $value; },
			'MongoDate' => function($value) { return $value->sec; }
		));

		if ($format == 'array') {
			$options += $defaults;
			return Collection::toArray($this->_data, $options);
		}
		return parent::to($format, $options);
	}

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
			$options = array('first' => true, 'schema' => $model::schema());
			$data = $model::connection()->cast($this, array($this->_pathKey => $data), $options);
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

	public function export() {
		return array(
			'exists' => $this->_exists,
			'key'  => $this->_pathKey,
			'data' => $this->_original,
			'update' => $this->_data
		);
	}

	protected function _populate($data = null, $key = null) {
	}
}

?>