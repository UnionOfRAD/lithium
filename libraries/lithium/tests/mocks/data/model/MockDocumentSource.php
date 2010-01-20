<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDocumentSource extends \lithium\data\Source {

	public function connect() {	}
	public function disconnect() {}
	public function entities($class = null) {}
	public function describe($entity, $meta = array()) { }
	public function create($query, $options) {	}
	public function update($query, $options) {	}
	public function delete($query, $options) {	}

	protected $point = 0;
	protected $result = null;

	public function read($query = null, $options = null) {
		$this->point = 0;
		$this->result = array(
			array('id' => 1, 'name' => 'Joe'),
			array('id' => 2, 'name' => 'Moe'),
			array('id' => 3, 'name' => 'Roe')
		);
	}
	public function hasNext() {
		return (is_array($this->result) && sizeof($this->result) > $this->point);
	}
	public function getNext() {
		return $this->result[$this->point++];
	}

	public function result($type, $resource, $context) {
		switch ($type) {
			case 'next':
				$result = $resource->hasNext() ? $resource->getNext() : null;
			break;
			case 'close':
				unset($resource);
				$result = null;
				break;

		}
		return $result;
	}
}

?>