<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

/**
 * `Document` is an alternative to `model\RecordSet`, which is optimized for organizing collections
 * of records from document-oriented databases such as CouchDB or MongoDB.
 */
class Document extends \lithium\util\Collection {

	/**
	 * The fully-namespaced class name of the model object to which this document is bound. This
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
	 * A reference to this object's parent `Document` object.
	 *
	 * @var object
	 */
	protected $_parent = null;

	/**
	 * Indicates whether this document has already been created in teh database.
	 *
	 * @var boolean
	 */
	protected $_exists = false;

	/**
	 * The class dependencies for `Document`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'record' => '\lithium\model\Record',
		'media' => '\lithium\http\Media'
	);

	protected $_hasInitialized = false;

	protected $_autoConfig = array(
		'items', 'classes' => 'merge', 'handle', 'model', 'result', 'query', 'parent', 'exists'
	);

	public function __construct($config = array()) {
		if (isset($config['data']) && !isset($config['items'])) {
			$config['items'] = $config['data'];
			unset($config['data']);
		}
		parent::__construct($config);
	}

	public function __get($name) {
		if (!isset($this->_items[$name])) {
			return null;
		}
		if (is_array($this->_items[$name])) {
			$class = get_class($this);
			$items = $this->_items[$name];
			$model = $this->_model;
			$parent = $this;
			return ($this->_items[$name] = $this->_record(get_class($this), $this->_items[$name]));
		}
		return $this->_items[$name];
	}

	public function __set($name, $value) {
		if (is_array($value)) {
			$class = get_class($this);
			$value = new $class(array('items' => $value));
		}
		$this->_items[$name] = $value;
	}

	public function rewind() {
		$this->_valid = (reset($this->_items) !== false);

		if (!$this->_valid && !$this->_hasInitialized) {
			$this->_hasInitialized = true;

			if ($record = $this->_populate()) {
				$this->_valid = true;
				return $record;
			}
		}
		return current($this->_items);
	}

	public function __call($method, $params = array()) {
		$model = $this->_model;
		array_unshift($params, $this);
		$class = $model::invokeMethod('_instance');
		return call_user_func_array(array(&$class, $method), $params);
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
		$this->_valid = (next($this->_items) !== false);
		$this->_valid = $this->_valid ?: !is_null($this->_populate());
		return $this->_valid ? $this->current() : null;
	}

	public function exists() {
		return $this->_exists;
	}

	/**
	 * Gets the raw data associated with this document.
	 *
	 * @return array Returns a raw array of document data
	 */
	public function data() {
		return $this->to('array');
	}

	protected function _update($id = null) {
		if ($id) {
			$model = $this->_model;
			$key = $model::meta('key');
			$this->__set($key, $id);
		}
		$this->_exists = true;
	}

	protected function _populate($items = null, $key = null) {
		if ($this->_closed() || !$this->_handle) {
			return;
		}
		if (!$items = $items ?: $this->_handle->result('next', $this->_result, $this)) {
			return $this->_close();
		}
		return ($this->_items[] = ($record = $this->_record($this->_classes['record'], $items)));
	}

	protected function _record($class, $items) {
		$parent = $this;
		$model = $this->_model;
		$exists = true;
		return new $class(compact('model', 'items', 'parent', 'exists'));
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