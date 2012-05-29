<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

use lithium\util\Collection;

class DocumentArray extends \lithium\data\Collection {

	/**
	 * Contains the original database value of the array. This value will be compared with the
	 * current value (`$_data`) to calculate the changes that should be sent to the database.
	 *
	 * @var array
	 */
	protected $_original = array();

	protected function _init() {
		parent::_init();
		$pathKey = $this->_pathKey;
		$model = $this->_model;

		if (is_object($schema = $this->schema())) {
			foreach ($this->_data as &$data) {
				$data = $schema->cast($this, $data, compact('pathKey', 'model'));
			}
		}
		$this->_original = $this->_data;
	}

	public function sync($id = null, array $data = array(), array $options = array()) {
		$defaults = array('materialize' => true);
		$options += $defaults;

		if ($options['materialize']) {
			$this->_exists = true;
		}

		while (!$this->closed()) {
			$this->_populate();
		}
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

	public function export(array $options = array()) {
		while (!$this->closed()) {
			$this->_populate();
		}
		return array(
			'exists' => $this->_exists,
			'key'  => $this->_pathKey,
			'data' => $this->_original,
			'update' => $this->_data
		);
	}

	/**
	 * Lazy-loads a document from a query using a reference to a database adapter and a query
	 * result resource.
	 *
	 * @param mixed $offset
	 * @return array
	 */
	protected function _populate($offset = null) {
		if ($this->closed() || !($model = $this->_model)) {
			return;
		}

		do {
			if(!$this->_result->valid()){
				return $this->close();
			}
			$data = $this->_result->current();
			$result = $this->_set($data, null, array('exists' => true));
			$this->_original[] = $result;
			$this->_result->next();
		} while ($offset !== null && !array_key_exists($offset, $this->_original));

		return $result;
	}
}

?>
