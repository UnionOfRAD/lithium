<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\g11n\catalog;

use lithium\core\AutoConfigurable;

/**
 * This is the foundation class for all g11n catalog adapters.
 */
class Adapter {

	use AutoConfigurable;

	/**
	 * Reads data.
	 *
	 * Override this method in subclasses if you want the adapter
	 * to have read support. The method is expected to return `null`
	 * if the passed category is not supported.
	 *
	 * @param string $category A category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return null This currently does nothing.
	 */
	public function read($category, $locale, $scope) {
		return null;
	}

	/**
	 * Writes data.
	 *
	 * Override this method in subclasses if you want the adapter
	 * to have write support. The method is expected to return `false`
	 * if the passed category is not supported.
	 *
	 * Please note that existing data is silently overwritten.
	 *
	 * @param string $category A category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @param array $data The data to write.
	 * @return false This currently does nothing.
	 */
	public function write($category, $locale, $scope, array $data) {
		return false;
	}

	/**
	 * Prepares an item before it is being written.
	 *
	 * Override this method in sublcasses if you need to
	 * i.e. escape the item's values.
	 *
	 * @param array $item
	 * @return array
	 */
	protected function _prepareForWrite(array $item) {
		return $item;
	}

	/**
	 * Merges an item into given data.
	 *
	 * @param array $data Data to merge item into.
	 * @param array $item Item to merge into $data. The item must have an `'id'` key.
	 * @return array The merged data.
	 */
	protected function _merge(array $data, array $item) {
		if (!isset($item['id'])) {
			return $data;
		}
		$id = $item['id'];

		$defaults = [
			'ids' => [],
			'translated' => null,
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		];
		$item += $defaults;

		if (isset($item['context']) && $item['context']) {
			$id .= '|' . $item['context'];
		}

		if (!isset($data[$id])) {
			$data[$id] = $item;
			return $data;
		}
		foreach (['ids', 'flags', 'comments', 'occurrences'] as $field) {
			$data[$id][$field] = array_merge($data[$id][$field], $item[$field]);
		}
		if (!isset($data[$id]['translated'])) {
			$data[$id]['translated'] = $item['translated'];
		} elseif (is_array($item['translated'])) {
			$data[$id]['translated'] = (array) $data[$id]['translated'] + $item['translated'];
		}
		return $data;
	}
}

?>