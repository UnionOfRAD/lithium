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
	 * An array containing each record's unique key. This allows, for example, lookups of records
	 * with composite keys, i.e.:
	 *
	 * {{{
	 * $payment = $records[array('client_id' => 42, 'invoice_id' => 21)];
	 * }}}
	 *
	 * @var array
	 */
	protected $_index = array();

	/**
	 * A 2D array of column-mapping information, where the top-level key is the fully-namespaced
	 * model name, and the sub-arrays are column names.
	 *
	 * @var array
	 */
	protected $_columns = array();

	/**
	 * Class constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$this->_autoConfig[] = 'index';
		parent::__construct($config);
	}

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
		if ($this->_data && !$this->_index) {
			$this->_index = array_keys($this->_data);
			$this->_data = array_values($this->_data);
		}
	}

	/**
	 * Checks to see if a record with the given index key is in the record set. If the record
	 * cannot be found, and not all records have been loaded into the set, it will continue loading
	 * records until either all available records have been loaded, or a matching key has been
	 * found.
	 *
	 * @see lithium\data\collection\RecordSet::offsetGet()
	 * @param mixed $offset The ID of the record to check for.
	 * @return boolean Returns true if the record's ID is found in the set, otherwise false.
	 */
	public function offsetExists($offset) {
		$this->offsetGet($offset);
		if (in_array($offset, $this->_index, true)) {
			return true;
		}
		return false;
	}

	/**
	 * Gets a record from the record set using PHP's array syntax, i.e. `$records[5]`. Using loose
	 * typing, integer keys can be accessed using strings and vice-versa. For record sets with
	 * composite keys, records may be accessed using arrays as array keys. Note that the order of
	 * the keys in the array does not matter.
	 *
	 * Because record data in `RecordSet` is lazy-loaded from the database, new records are fetched
	 * until one with a matching key is found.
	 *
	 * @see lithium\data\collection\RecordSet::$_index
	 * @param mixed $offset The offset, or ID (index) of the record you wish to load.  If
	 *                      `$offset` is `null`, all records are loaded into the record set, and
	 *                      `offsetGet` returns `null`.
	 * @return object Returns a `Record` object if a record is found with a key that matches the
	 *                value of `$offset`, otheriwse returns `null`.
	 */
	public function offsetGet($offset) {
		if ($offset !== null && in_array($offset, $this->_index, true)) {
			return $this->_data[array_search($offset, $this->_index)];
		}
		if ($this->closed()) {
			return null;
		}
		if ($model = $this->_model) {
			$offsetKey = $model::key($offset);
			while ($record = $this->_populate($offset)) {
				$curKey = $model::key($record);
				$keySet = $offsetKey == $curKey;
				if (!is_null($offset) && $keySet) {
					return $record;
				}
			}
		}
		return $this->close();
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @param integer $offset The offset to assign the value to.
	 * @param mixed $data The value to set.
	 * @return mixed The value which was set.
	 */
	public function offsetUnset($offset) {
		$this->offsetGet($offset);
		unset($this->_index[$index = array_search($offset, $this->_index)]);
		prev($this->_data);
		if (key($this->_data) === null) {
			$this->rewind();
		}
		unset($this->_data[$index]);
	}

	/**
	 * Returns the currently pointed to record's unique key.
	 *
	 * @param boolean $full If true, returns the complete key.
	 * @return mixed
	 */
	public function key($full = false) {
		if ($this->_started === false) {
			$this->current();
		}
		if ($this->_valid) {
			$key = $this->_index[key($this->_data)];
			return (is_array($key) && !$full) ? reset($key) : $key;
		}
		return null;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array.
	 *
	 * @param string $format
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
		$default = array('indexed' => true);
		$options += $default;
		$options['internal'] = !$options['indexed'];
		unset($options['indexed']);

		$this->offsetGet(null);
		if (!$options['internal'] && !is_scalar(current($this->_index))) {
			$options['internal'] = true;
		}
		return $result = parent::to($format, $options);
	}

	/**
	 * Applies a callback to all data in the collection.
	 *
	 * Overriden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @return object This collection instance.
	 */
	public function each($filter) {
		$this->offsetGet(null);
		return parent::each($filter);
	}

	/**
	 * Filters a copy of the items in the collection.
	 *
	 * Overridden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter Callback to use for filtering.
	 * @param array $options The available options are:
	 *              - `'collect'`: If `true`, the results will be returned wrapped
	 *              in a new `Collection` object or subclass.
	 * @return mixed The filtered items. Will be an array unless `'collect'` is defined in the
	 * `$options` argument, then an instance of this class will be returned.
	 */
	public function find($filter, array $options = array()) {
		$this->offsetGet(null);
		return parent::find($filter, $options);
	}

	/**
	 * Applies a callback to a copy of all data in the collection
	 * and returns the result.
	 *
	 * Overriden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @param array $options The available options are:
	 *              - `'collect'`: If `true`, the results will be returned wrapped
	 *              in a new `Collection` object or subclass.
	 * @return object The filtered data.
	 */
	public function map($filter, array $options = array()) {
		$this->offsetGet(null);
		return parent::map($filter, $options);
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
		if (($model = $this->_model) && is_array($data)) {
			$data = $model::connection()->item($model, $data, $options);
			$key = $model::key($data);
		} else {
			$key = $offset;
		}

		if (!$key) {
			$key = count($this->_data);
		}

		if (is_array($key)) {
			$key = count($key) === 1 ? reset($key) : $key;
		}
		if (in_array($key, $this->_index)) {
			$index = array_search($key, $this->_index);
			$this->_data[$index] = $data;
			return $this->_data[$index];
		}
		$this->_data[] = $data;
		$this->_index[] = $key;
		return $data;
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