<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Galleries;

class MongoDbTest extends \lithium\tests\integration\data\Base {

	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(array('MongoDb')));
	}

	public function setUp() {
		Galleries::config(array('meta' => array('connection' => 'test')));
	}

	public function tearDown() {
		Galleries::remove();
		Galleries::reset();
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

	public function testDateCastingUsingExists() {
		Galleries::config(array('schema' => array('_id' => 'id', 'created_at' => 'date')));
		$gallery = Galleries::create(array('created_at' => time()));
		$gallery->save();

		$result = Galleries::first(array('conditions' => array('created_at' => array('$exists' => false))));
		$this->assertNull($result);
	}
}

?>