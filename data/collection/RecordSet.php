<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\collection;

class RecordSet extends \lithium\data\Collection {

	/**
	 * A 2D array of column-mapping information, where the top-level key is the fully-namespaced
	 * model name, and the sub-arrays are column names.
	 *
	 * @var array
	 */
	protected $_columns = array();

	/**
	 * Initializes the record set and uses the database connection to get the column list contained
	 * in the query that created this object.
	 *
	 * @see lithium\data\collection\RecordSet::$_columns
	 * @return void
	 * @todo The part that uses _handle->schema() should be rewritten so that the column list
	 *       is coming from the query object.
	 */
	protected function _init() {
		parent::_init();
		if ($this->_result) {
			$this->_columns = $this->_columnMap();
		}
	}

	/**
	 * Extract the next item from the result ressource and wraps it into a `Record` object.
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
		$result = $this->_set($data, null, array('exists' => true));
		$this->_result->next();

		return $result;
	}

	protected function _set($data = null, $offset = null, $options = array()) {
		if ($model = $this->_model) {
			$data = !is_object($data) ? $model::connection()->item($model, $data, $options) : $data;
			$key = $model::key($data);
		} else {
			$key = $offset;
		}
		if (is_array($key)) {
			$key = count($key) === 1 ? current($key) : null;
		}
		return $key !== null ? $this->_data[$key] = $data : $this->_data[] = $data;
	}

	protected function _mapRecord($data) {
		$options = array('exists' => true);
		$relationships = array();
		$primary = $this->_model;
		$conn = $primary::connection();

		if (!$this->_query) {
			return $conn->item($primary, $data, $options + compact('relationships'));
		}

		$dataMap = array();
		$relMap = $this->_query->relationships();
		$main = null;

		do {
			$offset = 0;

			foreach ($this->_columns as $name => $fields) {
				$fieldCount = count($fields);
				$record = array_combine($fields, array_slice($data, $offset, $fieldCount));
				$offset += $fieldCount;

				if ($name === 0) {
					if ($main && $main != $record) {
						$this->_result->prev();
						break 2;
					}
					$main = $record;
					continue;
				}

				if ($relMap[$name]['type'] != 'hasMany') {
					$dataMap[$name] = $record;
					continue;
				}

				if (array_filter($record)) {
					$dataMap[$name][] = $record;
				}
			}
		} while ($data = $this->_result->next());

		foreach (array_filter(array_keys($this->_columns)) as $name) {
			if (!array_key_exists($name, $dataMap)) {
				$dataMap[$name] = array();
			}
		}

		foreach ($dataMap as $name => $rel) {
			$field = $relMap[$name]['fieldName'];
			$relModel = $relMap[$name]['model'];

			if ($relMap[$name]['type'] == 'hasMany') {
				foreach ($rel as &$data) {
					$data = $conn->item($relModel, $data, $options);
				}
				$opts = array('class' => 'set');
				$relationships[$field] = $conn->item($relModel, $rel, $options + $opts);
				continue;
			}
			$relationships[$field] = $conn->item($relModel, $rel, $options);
		}
		return $conn->item($primary, $main, $options + compact('relationships'));
	}

	protected function _columnMap() {
		if ($this->_query && $map = $this->_query->map()) {
			if (isset($map[$this->_query->alias()])) {
				$map = array($map[$this->_query->alias()]) + $map;
				unset($map[$this->_query->alias()]);
			} else {
				$map = array(array_shift($map)) + $map;
			}
			return $map;
		}
		if (!($model = $this->_model)) {
			return array();
		}
		if (!is_object($this->_query) || !$this->_query->join()) {
			$map = $model::connection()->schema($this->_query, $this->_result, $this);
			return array_values($map);
		}

		$model = $this->_model;
		$map = $model::connection()->schema($this->_query, $this->_result, $this);
		$map = array($map[$this->_query->alias()]) + $map;
		unset($map[$this->_query->alias()]);

		return $map;
	}
}

?>