<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\g11n\catalog\adapter;

/**
 * The `Memory` class is an adapter for reading and writing data during runtime.
 *
 * Written data is stored in memory and lost after the end of the script execution. The
 * adapter is also very useful for testing.
 */
class Memory extends \lithium\g11n\catalog\Adapter {

	/**
	 * Holds data during runtime.
	 *
	 * @var array
	 */
	protected $_data = [];

	/**
	 * Reads data.
	 *
	 * @param string $category A category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return array
	 */
	public function read($category, $locale, $scope) {
		$scope = $scope ?: 'default';

		if (isset($this->_data[$scope][$category][$locale])) {
			return $this->_data[$scope][$category][$locale];
		}
	}

	/**
	 * Writes data.
	 *
	 * @param string $category A category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @param array $data The data to write.
	 * @return boolean
	 */
	public function write($category, $locale, $scope, array $data) {
		$scope = $scope ?: 'default';

		if (!isset($this->_data[$scope][$category][$locale])) {
			$this->_data[$scope][$category][$locale] = [];
		}
		foreach ($data as $item) {
			$this->_data[$scope][$category][$locale] = $this->_merge(
				$this->_data[$scope][$category][$locale],
				$this->_prepareForWrite($item)
			);
		}
		return true;
	}
}

?>