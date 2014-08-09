<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2014, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Galleries;

class MongoDbTest extends \lithium\tests\integration\data\Base {

	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(array('MongoDb')));
	}

	public function testCountOnEmptyResultSet() {
		$data = Galleries::find('all', array('conditions' => array('name' => 'no match')));

		$expected = 0;
		$result = $data->count();
		$this->assertIdentical($expected, $result);
	}

	public function testIterateOverEmptyResultSet() {
		$data = Galleries::find('all', array('conditions' => array('name' => 'no match')));

		$result = next($data);
		$this->assertNull($result);
	}
}

?>