<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\collection;

use lithium\data\entity\Document;

class DocumentSet extends \lithium\data\Collection {

	/**
	 * Contains the original database value of the array. This value will be compared with the
	 * current value (`$_data`) to calculate the changes that should be sent to the database.
	 *
	 * @var array
	 */
	protected $_original = [];

	protected function _init() {
		parent::_init();
		$this->_original = $this->_data;
		$this->_handlers += [
			'MongoId' => function($value) { return (string) $value; },
			'MongoDate' => function($value) { return $value->sec; }
		];
	}

	public function sync($id = null, array $data = [], array $options = []) {
		$defaults = ['materialize' => true];
		$options += $defaults;

		if ($options['materialize']) {
			$this->_exists = true;
		}

		$this->offsetGet(null);
		$this->_original = $this->_data;
	}

	/**
	 * Determines if the `DocumentSet` has been modified since it was last saved
	 *
	 * @return boolean
	 */
	public function modified() {
		if (count($this->_original) !== count($this->_data)) {
			return true;
		}
		foreach ($this->_original as $key => $doc) {
			$updated = $this->_data[$key];
			if (!isset($updated)) {
				return true;
			}
			if ($doc !== $updated) {
				return true;
			}
			if (!is_object($updated) || !method_exists($updated, 'modified')) {
				continue;
			}
			$modified = $this->_data[$key]->modified();

			if (in_array(true, $modified)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds conversions checks to ensure certain class types and embedded values are properly cast.
	 *
	 * @param string $format Currently only `array` is supported.
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = []) {
		$this->offsetGet(null);
		return parent::to($format, $options);
	}

	public function export(array $options = []) {
		$this->offsetGet(null);
		return [
			'exists' => $this->_exists,
			'key'  => $this->_pathKey,
			'data' => array_values($this->_original),
			'update' => array_values($this->_data)
		];
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
		$result = $this->_set($data, null, ['exists' => true, 'original' => true]);
		$this->_result->next();

		return $result;
	}

	/**
	 * Helper method to normalize and set data.
	 *
	 * @param mixed $data
	 * @param null|integer|string $offset
	 * @param array $options
	 * @return mixed The (potentially) cast data.
	 */
	protected function _set($data = null, $offset = null, $options = []) {
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
		if (is_object($data) && method_exists($data, 'assignTo')) {
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