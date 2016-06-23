<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Galleries;

class MongoDbTest extends \lithium\tests\integration\data\Base {

	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(['MongoDb']));
	}

	public function setUp() {
		Galleries::config(['meta' => ['connection' => 'test']]);
	}

	public function tearDown() {
		Galleries::remove();
		Galleries::reset();
	}

	public function testCountOnEmptyResultSet() {
		$data = Galleries::find('all', ['conditions' => ['name' => 'no match']]);

		$expected = 0;
		$result = $data->count();
		$this->assertIdentical($expected, $result);
	}

	public function testIterateOverEmptyResultSet() {
		$data = Galleries::find('all', ['conditions' => ['name' => 'no match']]);

		$result = next($data);
		$this->assertNull($result);
	}

	public function testDateCastingUsingExists() {
		Galleries::config(['schema' => ['_id' => 'id', 'created_at' => 'date']]);
		$gallery = Galleries::create(['created_at' => time()]);
		$gallery->save();

		$result = Galleries::first(['conditions' => ['created_at' => ['$exists' => false]]]);
		$this->assertNull($result);
	}
}

?>