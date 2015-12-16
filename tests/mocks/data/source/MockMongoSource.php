<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

use lithium\core\Filterable;
use MongoId;
use lithium\tests\mocks\data\source\mongo_db\MockResultResource;

class MockMongoSource extends \lithium\core\Object {
	use Filterable;

	public $resultSets = array();

	public $queries = array();

	public function __get($name) {
		return $this;
	}

	public function insert(&$data, $options) {
		$this->queries[] = compact('data', 'options');
		$result = current($this->resultSets);
		next($this->resultSets);
		$data['_id'] = new MongoId();
		return $result;
	}

	public function find($conditions, $fields) {
		$this->queries[] = compact('conditions', 'fields');
		$result = new MockResultResource(array('data' => current($this->resultSets)));
		next($this->resultSets);
		return $result;
	}
}

?>