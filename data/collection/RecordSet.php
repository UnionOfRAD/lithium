<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\collection;

use RuntimeException;
use lithium\util\Set;

class RecordSet extends \lithium\data\Collection {

	/**
	 * A 2D array of column-mapping information, where the top-level key is the fully-namespaced
	 * model name, and the sub-arrays are column names.
	 *
	 * @var array
	 */
	protected $_columns = [];

	/**
	 * A recursive array of relation dependencies where key are relations
	 * and value are arrays with their relation dependencies
	 *
	 * @var array
	 */
	protected $_dependencies = [];

	/**
	 * Holds the relationships as returned via `$this->_query->relationships()`.
	 *
	 * @var array
	 */
	protected $_relationships = [];

	/**
	 * Precompute index of the main model primary key(s) which allow to find
	 * values directly in result data without the column name matching process.
	 *
	 * @var array
	 */
	protected $_keyIndex = [];

	/**
	 * Keeps a list of hydrated main record indexes values already seen.
	 *
	 * @var array
	 */
	protected $_seen = [];

	/**
	 * Initializes the record set and uses the database connection to get the column list contained
	 * in the query that created this object.
	 *
	 * @todo The part that uses _handle->schema() should be rewritten so that the column list
	 *       is coming from the query object.
	 * @see lithium\data\collection\RecordSet::$_columns
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		if (!$this->_result) {
			return;
		}
		$this->_columns = $this->_columnMap();

		if (!$this->_query) {
			return;
		}
		$this->_keyIndex = $this->_keyIndex();

		$this->_dependencies = Set::expand(Set::normalize(
			array_filter(array_keys($this->_columns))
		));
		$this->_relationships = $this->_query->relationships();
	}

	/**
	 * Extracts the next item from the result resource and wraps it into a `Record` object.
	 *
	 * @return mixed Returns the next `Record` if exists. Returns `null` otherwise
	 */
	protected function _populate() {
		if ($this->closed() || !$this->_result->valid()) {
			return;
		}
		$data = $this->_result->current();

		if ($this->_query) {
			$data = $this->_mapRecord($data);
		}
		$result = $this->_set($data, null, ['exists' => true]);
		$this->_result->next();

		return $result;
	}

	/**
	 * Sets item to passed data with the key from the query object or a given offset.
	 *
	 * Due to behaviour changes in PHP 7.3 and above the internal array pointer of the
	 * `$_data` property might not be updated after setting a value by key. To preserve
	 * the behavior the internal array pointer is updated manually using `reset()` and
	 * `end()`.
	 *
	 * @link https://bugs.php.net/bug.php?id=78529
	 * @param mixed $data
	 * @param integer $offset
	 * @param array $options
	 * @return mixed $data
	 */
	protected function _set($data = null, $offset = null, $options = []) {
		if ($model = $this->_model) {
			$options += ['defaults' => false];
			$data = !is_object($data) ? $model::create($data, $options) : $data;
			$key = $model::key($data);
		} else {
			$key = $offset;
		}
		if (is_array($key)) {
			$key = count($key) === 1 ? current($key) : null;
		}
		if ($key === null) {
			return $this->_data[] = $data;
		}
		reset($this->_data);
		$this->_data[$key] = $data;
		end($this->_data);
		return $data;
	}

	/**
	 * Converts a PDO `Result` array to a nested `Record` object.
	 *
	 * 1. Builds an associative array with data from the row, with joined row data
	 *    nested under the relationships name. Joined row data is added and new
	 *    results consumed from the result cursor under the relationships name until
	 *    the value of the main primary key changes.
	 *
	 * 2. The built array is then hydrated and returned.
	 *
	 * Note: Joined records must appear sequentially, when non-sequential records
	 *       are detected an exception is thrown.
	 *
	 * @throws RuntimeException
	 * @param array $row 2 dimensional PDO `Result` array
	 * @return \lithium\data\entity\Record Returns a `Record` object.
	 */
	protected function _mapRecord($row) {
		$main = array_intersect_key($row, $this->_keyIndex);

		if ($main) {
			if (in_array($main, $this->_seen)) {
				$message  = 'Associated records hydrated out of order: ';
				$message .= var_export($this->_seen, true);
				throw new RuntimeException($message);
			}
			$this->_seen[] = $main;
		}

		$i = 0;
		$record = [];

		do {
			$offset = 0;

			foreach ($this->_columns as $name => $fields) {
				$record[$i][$name] = array_combine(
					$fields, array_slice($row, $offset, ($count = count($fields)))
				);
				$offset += $count;
			}
			$i++;

			if (!$peek = $this->_result->peek()) {
				break;
			}
			if ($main !== array_intersect_key($peek, $this->_keyIndex)) {
				break;
			}
		} while ($main && ($row = $this->_result->next()));

		return $this->_hydrateRecord($this->_dependencies, $this->_model, $record, 0, $i, '');
	}

	/**
	 * Hydrates a 2 dimensional PDO row `Result` array recursively.
	 *
	 * @param array $relations The cascading with relation
	 * @param string $primary Model classname
	 * @param array $record Loaded Records
	 * @param integer $min
	 * @param integer $max
	 * @param string $name Alias name
	 * @return \lithium\data\entity\Record Returns a `Record` object as created by the model.
	 */
	protected function _hydrateRecord(array $relations, $primary, array $record, $min, $max, $name) {
		$options = ['exists' => true, 'defaults' => false];

		foreach ($relations as $relation => $subrelations) {
			$relName  = $name ? "{$name}.{$relation}" : $relation;
			$relModel = $this->_relationships[$relName]['model'];
			$relField = $this->_relationships[$relName]['fieldName'];
			$relType  = $this->_relationships[$relName]['type'];

			if ($relType !== 'hasMany') {
				$record[$min][$name][$relField] = $this->_hydrateRecord(
					$subrelations ?: [], $relModel, $record, $min, $max, $relName
				);
				continue;
			}

			$rel = [];
			$main = $relModel::key($record[$min][$relName]);

			$i = $min;
			$j = $i + 1;

			while ($j < $max) {
				$keys = $relModel::key($record[$j][$relName]);

				if ($main != $keys) {
					$rel[] = $this->_hydrateRecord(
						$subrelations ?: [], $relModel, $record, $i, $j, $relName
					);
					$main = $keys;
					$i = $j;
				}
				$j++;
			}
			if (array_filter($record[$i][$relName])) {
				$rel[] = $this->_hydrateRecord(
					$subrelations ?: [], $relModel, $record, $i, $j, $relName
				);
			}
			$record[$min][$name][$relField] = $relModel::create($rel, [
				'class' => 'set'
			] + $options);
		}
		return $primary::create(
			isset($record[$min][$name]) ? $record[$min][$name] : [], $options
		);
	}

	protected function _columnMap() {
		if ($this->_query && ($map = $this->_query->map())) {
			return $map;
		}
		if (!($model = $this->_model)) {
			return [];
		}
		if (!is_object($this->_query) || !$this->_query->join()) {
			return $model::connection()->schema($this->_query);
		}
		$model = $this->_model;
		return $model::connection()->schema($this->_query);
	}

	/**
	 * Extracts the numerical index of the primary key in numerical indexed row data.
	 * Works only for the main row data and not for relationship rows.
	 *
	 * This method will also correctly detect a primary key which doesn't come
	 * first.
	 *
	 * @return array An array where key are index and value are primary key fieldname.
	 */
	protected function _keyIndex() {
		if (!($model = $this->_model) || !isset($this->_columns[''])) {
			return [];
		}
		$index = 0;

		foreach ($this->_columns as $name => $fields) {
			if ($name === '') {
				if (($offset = array_search($model::meta('key'), $fields)) === false) {
					return [];
				}
				return [$index + $offset => $model::meta('key')];
			}
			$index += count($fields);
		}
		return [];
	}
}

?>