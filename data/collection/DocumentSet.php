<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

use lithium\util\Collection;
use lithium\data\entity\Document;

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
		$result = $this->_set($data, null, array('exists' => true, 'original' => true));
		$this->_result->next();

		return $result;
	}

	protected function _set($data = null, $offset = null, $options = array()) {
		if ($schema = $this->schema()) {
			$model = $this->_model;
			$pathKey = $this->_pathKey;
			$options =  compact('model', 'pathKey') + $options;
			$data = !is_object($data) ? $schema->cast($this, $offset, $data, $options) : $data;
			$key = $model && $data instanceof Document ? $model::key($data) : $offset;
		} else {
			$key = $offset;
		}
		if (is_array($key)) {
			$key = count($key) === 1 ? current($key) : null;
		}
		if (is_object($key)) {
			$key = (string) $key;
		}
		if (method_exists($data, 'assignTo')) {
			$data->assignTo($this);
		}
		$key !== null ? $this->_data[$key] = $data : $this->_data[] = $data;
		if (isset($options['original']) && $options['original']) {
			$key !== null ? $this->_original[$key] = $data : $this->_original[] = $data;
		}
		return $data;
	}
}

?>