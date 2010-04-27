<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

/**
 * The `Collection` class extends the generic `lithium\util\Collection` class to provide
 * context-specific features for working with sets of data persisted by a backend data store. This
 * is a general abstraction that operates on abitrary sets of data from either relational or
 * non-relational data stores.
 */
abstract class Collection extends \lithium\util\Collection {

	/**
	 * The fully-namespaced class name of the model object to which this record set is bound. This
	 * is usually the model that executed the query which created this object.
	 *
	 * @var string
	 */
	protected $_model = null;

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
	 * @see lithium\data\model\DataSet::rewind()
	 * @see lithium\data\model\DataSet::_populate()
	 */
	protected $_hasInitialized = false;

	/**
	 * Holds an array of values that should be processed on initialization.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'items', 'classes' => 'merge', 'handle', 'model', 'result', 'query'
	);

	/**
	 * Returns a boolean indicating whether an offset exists for the 
	 * current `Document`.
	 *
	 * @param string $offset String or integer indicating the offset or 
	 *               index of a document in a set, or the name of a field in an
	 *               individual document.
	 * @return boolean Result.
	 */
	public function offsetExists($offset) {
		return ($this->offsetGet($offset) !== null);
	}

	/**
	 * Reset the set's iterator and return the first record in the set.
	 * The next call of `current()` will get the first record in the set.
	 *
	 * @return object `Record`
	 */
	public function rewind() {
		$this->_valid = (reset($this->_items) !== false);

		if (!$this->_valid && !$this->_hasInitialized) {
			$this->_hasInitialized = true;

			if ($record = $this->_populate()) {
				$this->_valid = true;
				return $record;
			}
		}
	}

	/**
	 * Returns meta information for this `RecordSet`
	 *
	 * @return array
	 */
	public function meta() {
		return array('model' => $this->_model);
	}

	/**
	 * Applies a callback to all items in the collection.
	 *
	 * Overriden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @return object This collection instance.
	 */
	public function each($filter) {
		if (!$this->_closed()) {
			while($this->next()) {}
		}
		return parent::each($filter);
	}

	/**
	 * Applies a callback to a copy of all items in the collection
	 * and returns the result.
	 *
	 * Overriden to load any data that has not yet been loaded.
	 *
	 * @param callback $filter The filter to apply.
	 * @param array $options The available options are:
	 *              - `'collect'`: If `true`, the results will be returned wrapped
	 *              in a new Collection object or subclass.
	 * @return array|object The filtered items.
	 */
	public function map($filter, array $options = array()) {
		if (!$this->_closed()) {
			while($this->next()) {}
		}
		return parent::map($filter, $options);
	}

	/**
	 * Magic alias for `_close()`. Ensures that the data set's connection is closed when the object
	 * is destroyed.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->_close();
	}

	abstract protected function _populate($data = null, $key = null);

	/**
	 * Executes when the associated result resource pointer reaches the end of its data set. The
	 * resource is freed by the connection, and the reference to the connection is unlinked.
	 *
	 * @return void
	 */
	protected function _close() {
		if (!$this->_closed()) {
			$this->_result = $this->_handle->result('close', $this->_result, $this);
			unset($this->_handle);
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
		return (empty($this->_result) || !isset($this->_handle) || empty($this->_handle));
	}
}

?>