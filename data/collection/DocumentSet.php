<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

use lithium\util\Collection;

class DocumentSet extends \lithium\data\Collection {

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

		if (($model = $this->_model) && ($schema = $this->schema())) {
			$exists = $this->_exists;
			$pathKey = $this->_pathKey;
			$this->_data = $schema->cast($this, $this->_data, compact('exists', 'pathKey'));
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

		$this->offsetGet(null);
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

		$this->offsetGet(null);
		if ($format == 'array') {
			$options += $defaults;
			return Collection::toArray($this->_data, $options);
		}
		return parent::to($format, $options);
	}

	public function export(array $options = array()) {
		$this->offsetGet(null);
		return array(
			'exists' => $this->_exists,
			'key'  => $this->_pathKey,
			'data' => $this->_original,
			'update' => $this->_data
		);
	}

	/**
	 * Extract the next item from the result ressource and wraps it into a `Document` object.
	 *
	 * @return mixed Returns the next `Document` if exists. Returns `null` otherwise
	 */
	protected function _populate() {
		if ($this->closed() || !$this->_result->valid()) {
			return;
		}
		$data = $this->_result->current();
		$result = $this->_set($data, null, array('exists' => true));
		$this->_original[] = $result;
		$this->_result->next();

		return $result;
	}

	protected function _set($data = null, $offset = null, $options = array()) {
		if ($schema = $this->schema()) {
			$model = $this->_model;
			$pathKey = $this->_pathKey;
			$options =  compact('model', 'pathKey') + $options;
			$result = $schema->cast($this, array($offset => $data), $options);
			$data = reset($result);
		}
		($offset === null) ? $this->_data[] = $data : $this->_data[$offset] = $data;
		if (is_object($data)) {
			$data->assignTo($this);
		}
		return $data;
	}
}

?>