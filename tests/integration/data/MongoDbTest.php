<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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