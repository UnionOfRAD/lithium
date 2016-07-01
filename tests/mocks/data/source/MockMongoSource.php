<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source;

use MongoId;
use lithium\tests\mocks\data\source\mongo_db\MockResultResource;

class MockMongoSource extends \lithium\core\Object {

	public $resultSets = [];

	public $queries = [];

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
		$result = new MockResultResource(['data' => current($this->resultSets)]);
		next($this->resultSets);
		return $result;
	}
}

?>