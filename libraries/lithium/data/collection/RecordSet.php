<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
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
	 * The internal pointer to indicate which `Record` is the current record.
	 *
	 * @var integer
	 */
	protected $_pointer = 0;

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
		if (in_array($offset, $this->_index)) {
			return true;
		}
		return parent::offsetExists($offset);
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
		if ($offset !== null && in_array($offset, $this->_index)) {
			return $this->_data[array_search($offset, $this->_index)];
		}
		if ($this->closed()) {
			return null;
		}
		$model = $this->_model;

		while ($record = $this->_populate(null, $offset)) {
			if (!is_null($offset) && $offset == $model::key($record)) {
				return $record;
			}
		}
		$this->close();
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @param integer $offset The offset to assign the value to.
	 * @param mixed $data The value to set.
	 * @return mixed The value which was set.
	 */
	public function offsetSet($offset, $data) {
		return $this->_populate($data, $offset);
	}

	/**
	 * Unsets an offset.
	 *
	 * @param string $offset The offset to unset.
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->_index[$index = array_search($offset, $this->_index)]);
		unset($this->_data[$index]);
	}

	/**
	 * Reset the set's iterator and return the first record in the set.
	 * The next call of `current()` will get the first record in the set.
	 *
	 * @return object `Record`
	 */
	public function rewind() {
		$this->_pointer = 0;
		reset($this->_index);

		if ($record = parent::rewind()) {
			return $record;
		}
		return empty($this->_data) ? null : $this->_data[$this->_pointer];
	}

	/**
	 * Returns the currently pointed to record in the set.
	 *
	 * @return `Record`
	 */
	public function current() {
		return $this->_data[$this->_pointer];
	}

	/**
	 * Returns the currently pointed to record's unique key.
	 *
	 * @param boolean $full If true, returns the complete key.
	 * @return mixed
	 */
	public function key($full = false) {
		$key = $this->_index[$this->_pointer];
		return (is_array($key) && !$full) ? reset($key) : $key;
	}

	/**
	 * Returns the next record in the set, and advances the object's internal pointer. If the end of
	 * the set is reached, a new record will be fetched from the data source connection handle.
	 * If no more records can be fetched, returns `null`.
	 *
	 * @return object Returns the next record in the set, or `null`, if no more records are
	 *                available.
	 */
	public function next() {
		$this->_valid = (next($this->_data) !== false && next($this->_index) !== false);

		if (!$this->_valid) {
			$this->_valid = !is_null($this->_populate());
		}
		$return = null;

		if ($this->_valid) {
			$this->_pointer++;
			$return = $this->current();
		}
		return $return;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array.
	 *
	 * @param string $format
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
		$defaults = array('indexed' => true);
		$options += $defaults;

		$result = null;
		$this->offsetGet(null);

		switch ($format) {
			case 'array':
				$result = array_map(function($r) { return $r->to('array'); }, $this->_data);
				if (is_scalar(current($this->_index)) && $options['indexed']) {
					if (!empty($this->_index) && !empty($result)) {
						$result = array_combine($this->_index, $result);
					} else {
						$result = array();
					}
				}
			break;
			default:
				$result = parent::to($format, $options);
			break;
		}
		return $result;
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
	 * Applies a callback to a copy of all data in the collection
	 * and returns the result.
	 *
	 * Overriden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @param array $options The available options are:
	 *              - `'collect'`: If `true`, the results will be returned wrapped
	 *              in a new `Collection` object or subclass.
	 * @return array|object The filtered data.
	 */
	public function map($filter, array $options = array()) {
		$this->offsetGet(null);
		return parent::map($filter, $options);
	}

	/**
	 * Lazy-loads records from a query using a reference to a database adapter and a query
	 * result resource.
	 *
	 * @param array $data
	 * @param mixed $key
	 * @return array
	 */
	protected function _populate($data = null, $key = null) {
		if ($this->closed() && !$data || !($model = $this->_model)) {
			return;
		}
		$conn = $model::connection();

		if (!($data = $data ?: $this->_result->next())) {
			return $this->close();
		}
		$key = null;
		$offset = 0;
		$recordMap = is_object($data) ? array($model => $data) : array();

		if (!$recordMap) {
			foreach ($this->_columns as $model => $fields) {
				$record = array_combine($fields, array_slice($data, $offset, count($fields)));

				if ($model == $this->_model) {
					$key = $key = $model::key($record);
				}
				$recordMap[$model] = $conn->item($model, $record, array('exists' => true));
			}
		} else {
			$key = $model::key(reset($recordMap));
		}
		$record = reset($recordMap);
		unset($recordMap[key($recordMap)]);

		if (is_array($key)) {
			$key = count($key) === 1 ? reset($key) : $key;
		}
		if (in_array($key, $this->_index)) {
			$index = array_search($key, $this->_index);
			$this->_data[$index] = $record;
			return $this->_data[$index];
		}
		$this->_data[] = $record;
		$this->_index[] = $key;
		return $record;
	}

	protected function _columnMap() {
		if ($this->_query && $map = $this->_query->map()) {
			return $map;
		}
		if (!($model = $this->_model)) {
			return array();
		}
		return $model::connection()->schema($this->_query, $this->_result, $this);
	}
}

?>