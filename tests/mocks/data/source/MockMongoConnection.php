<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

use MongoId;

class MockMongoConnection {

	public $queries = [];

	public $results = [];

	protected $_collection = null;

	public $gridFsPrefix = null;

	public function connect() {
		return false;
	}

	public function &__get($property) {
		$this->_collection = $property;
		return $this;
	}

	public function listDBs() {
		return [];
	}

	public function getConnections() {
		return [[
			'hash' => 'localhost:27017;-;X;56052',
			'server' => [],
			'connection' => []
		]];
	}

	public function insert(array &$data, array $options = []) {
		$data['_id'] = new MongoId();
		return $this->_record(__FUNCTION__, compact('data', 'options'));
	}

	protected function _record($type, array $data = []) {
		$collection = $this->_collection;
		$this->queries[] = compact('type', 'collection') + $data;
		$result = array_pop($this->results);
		return $result === null ? false : $result;
	}

	public function update($conditions, $update, $options) {
		return $this->_record(__FUNCTION__, compact('conditions', 'update', 'options'));
	}

	public function remove($conditions, $options) {
		return $this->_record(__FUNCTION__, compact('conditions', 'options'));
	}

	public function find($conditions, $fields) {
		return $this->_record(__FUNCTION__, compact('conditions', 'fields'));
	}

	public function listCollections() {
		return $this->_record(__FUNCTION__);
	}

	public function getGridFS($prefix = "fs") {
		$this->gridFsPrefix = $prefix;
		return $this;
	}

	public function storeBytes($bytes = null, array $extra = [], array $options = []) {
		return;
	}
}

?>