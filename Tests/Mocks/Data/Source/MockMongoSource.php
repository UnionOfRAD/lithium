<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data\Source;

use MongoId;
use Lithium\Tests\Mocks\Data\Source\MongoDb\MockResult;

class MockMongoSource extends \Lithium\Core\Object {

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
		$result = new MockResult(array('data' => current($this->resultSets)));
		next($this->resultSets);
		return $result;
	}
}

?>