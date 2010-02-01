<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

class RecordSet extends \lithium\util\Collection {

	/**
	 * The fully-namespaced class name of the model object to which this record set is bound. This
	 * is usually the model that executed the query which created this object.
	 *
	 * @var string
	 */
	protected $_model = null;

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

	protected $_pointer = 0;

	/**
	 * A reference to the object that originated this record set; usually an instance of
	 * `lithium\data\Source` or `lithium\data\source\Database`. Used to load column definitions and
	 * lazy-load records.
	 *
	 * @var object
	 */
	protected $_handle = null;

	/**
	 * A reference to the query object that originated this record set; usually an instance of
	 * `lithium\data\model\Query`.
	 *
	 * @var object
	 */
	protected $_query = null;

	/**
	 * A pointer or resource that is used to load records from the object (`$_handle`) that
	 * originated this record set.
	 *
	 * @var resource
	 */
	protected $_result = null;

	/**
	 * A 2D array of column-mapping information, where the top-level key is the fully-namespaced
	 * model name, and the sub-arrays are column names.
	 *
	 * @var array
	 */
	protected $_columns = array();

	protected $_classes = array(
		'record' => '\lithium\model\Record',
		'media' => '\lithium\net\http\Media'
	);

	/**
	 * Indicates whether the current position is valid or not. This overrides the default value of
	 * the parent class.
	 *
	 * @var boolean
	 * @see lithium\util\Collection::valid()
	 */
	protected $_valid = true;

	/**
	 * By default, query results are not fetched until the record set is iterated. Set to true when
	 * the record set has begun iterating and fetching records.
	 *
	 * @var boolean
	 * @see lithium\data\model\RecordSet::rewind()
	 * @see lithium\data\model\RecordSet::_populate()
	 */
	protected $_hasInitialized = false;

	protected $_autoConfig = array(
		'items', 'classes' => 'merge', 'handle', 'model', 'result', 'query'
	);

	/**
	 * Initializes the record set and uses the database handle to get the column list contained in
	 * the query that created this object.
	 *
	 * @return void
	 * @see lithium\data\model\RecordSet::$_columns
	 * @todo The part that uses _handle->columns() should be rewritten so that the column list
	 *       is coming from the query object.
	 */
	protected function _init() {
		parent::_init();

		if ($this->_handle && $this->_result) {
			$this->_columns = $this->_handle->columns($this->_query, $this->_result, $this);
		}
	}

	/**
	 * Checks to see if a record with the given index key is in the record set. If the record
	 * cannot be found, and not all records have been loaded into the set, it will continue loading
	 * records until either all available records have been loaded, or a matching key has been
	 * found.
	 *
	 * @param mixed $offset The ID of the record to check for.
	 * @return boolean Returns true if the record's ID is found in the set, otherwise false.
	 * @see lithium\data\model\RecordSet::offsetGet()
	 */
	public function offsetExists($offset) {
		if (in_array($offset, $this->_index)) {
			return true;
		}
		return ($this->offsetGet($offset) !== null);
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
	 * @param mixed $offset The offset, or ID (index) of the record you wish to load.  If
	 *                      `$offset` is `null`, all records are loaded into the record set, and
	 *                      `offsetGet` returns `null`.
	 * @return object Returns a `Record` object if a record is found with a key that matches the
	 *                value of `$offset`, otheriwse returns `null`.
	 * @see lithium\data\model\RecordSet::$_index
	 */
	public function offsetGet($offset) {
		if (!is_null($offset) && in_array($offset, $this->_index)) {
			return $this->_items[array_search($offset, $this->_index)];
		}
		if ($this->_closed()) {
			return null;
		}
		$model = $this->_model;

		while ($record = $this->_populate(null, $offset)) {
			if (!is_null($offset) && $offset == $model::key($record)) {
				return $record;
			}
		}
		$this->_close();
	}

	public function offsetSet($offset, $value) {
		if (in_array($offset, $this->_index)) {
			return $this->_items[array_search($offset, $this->_index)] = $value;
		}
		$this->_index[] = $offset;
		return $this->_items[] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->_index[$index = array_search($offset, $this->_index)]);
		unset($this->_items[$index]);
	}

	/**
	* Reset the set's iterator and return the first record in the set.
	* The next call of `current()` will get the first record in the set.
	*
	* @return `Record`
	*/
	public function rewind() {
		$this->_pointer = 0;
		$this->_valid = (reset($this->_items) !== false && reset($this->_index));

		if (!$this->_valid && !$this->_hasInitialized) {
			$this->_hasInitialized = true;

			if ($record = $this->_populate()) {
				$this->_valid = true;
				return $record;
			}
		}
 		return $this->_items[$this->_pointer];
	}

	/**
	* Returns the currently pointed to record in the set.
	*
	* @return `Record`
	*/
	public function current() {
		return $this->_items[$this->_pointer];
	}

	/**
	* Returns the currently pointed to record's unique key.
	*
	* @return mixed
	*/
	public function key() {
		return $this->_index[$this->_pointer];
	}

	/**
	 * Returns the next record in the set, and advances the object's internal pointer. If the end of
	 * the set is reached, a new record will be fetched from the data source connection handle
	 * (`$_handle`). If no more records can be fetched, returns `null`.
	 *
	 * @return object Returns the next record in the set, or `null`, if no more records are
	 *                available.
	 */
	public function next() {
		$this->_valid = (next($this->_items) !== false && next($this->_index) !== false);

		if (!$this->_valid) {
			$this->_valid = !is_null($this->_populate());
		}

		if ($this->_valid) {
			$this->_pointer++;
		}
		return $this->_valid ? $this->current() : null;
	}

	public function meta() {
		return array('model' => $this->_model);
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array.
	 *
	 * @param string $format
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, $options = array()) {
		$defaults = array('indexed' => true);
		$options += $defaults;

		$result = null;
		$this->offsetGet(null);

		switch ($format) {
			case 'array':
				$result = array_map(function($r) { return $r->to('array'); }, $this->_items);
				if (is_scalar(current($this->_index)) && $options['indexed']) {
					$result = array_combine($this->_index, $result);
				}
			break;
			default:
				$result = parent::to($format, $options);
			break;
		}
		return $result;
	}

	/**
	* Magic alias for _close().
	*/
	public function __destruct() {
		$this->_close();
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
		if ($this->_closed()) {
			return;
		}
		$data = $data ?: $this->_handle->result('next', $this->_result, $this);

		if (!$data) {
			return $this->_close();
		}
		$result = null;

		foreach ((array) $this->_columns as $model => $fields) {
			$data = array_combine($fields, array_slice($data, 0, count($fields)));
			$exists = true;

			$class = $this->_classes['record'];
			$this->_items[] = ($record = new $class(compact('model', 'data', 'exists')));
			$this->_index[] = $key;
			return $record;
		}
	}

	/**
	 * Executes when the associated result resource pointer reaches the end of its record set. The
	 * resource is freed by the connection, and the reference to the connection is unlinked.
	 *
	 * @return void
	 */
	protected function _close() {
		if (!$this->_closed()) {
			$this->_result = $this->_handle->result('close', $this->_result, $this);
			unset($this->_handle);
			$this->_handle = null;
		}
	}

	/**
	 * Checks to see if this record set has already fetched all available records and freed the
	 * associated result resource.
	 *
	 * @return boolean Returns true if all records are loaded and the database resources have been
	 *         freed, otherwise returns false.
	 */
	protected function _closed() {
		return (empty($this->_result) || empty($this->_handle));
	}
}

?>