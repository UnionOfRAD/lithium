<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model;

use lithium\data\entity\Document;

class MockDocumentMultipleKey extends \lithium\data\Model {

	protected $_meta = [
		'key' => ['id', 'rev'],
		'name' => null,
		'title' => null,
		'class' => null,
		'source' => null,
		'connection' => false,
		'initialized' => false
	];

	public function ret($record, $param1 = null, $param2 = null) {
		if ($param2) {
			return $param2;
		}
		if ($param1) {
			return $param1;
		}
		return null;
	}

	public static function find($type = 'all', array $options = []) {
		if ($type === 'first') {
			return new Document(['data' => [
				'id' => 2, 'rev' => '1-1', 'name' => 'Two', 'content' => 'Lorem ipsum two'
			]]);
		}

		return new Document(['data' => [
			['id' => 1, 'rev' => '1-1','name' => 'One', 'content' => 'Lorem ipsum one'],
			['id' => 2, 'rev' => '1-1','name' => 'Two', 'content' => 'Lorem ipsum two'],
			['id' => 3, 'rev' => '1-1', 'name' => 'Three', 'content' => 'Lorem ipsum three']
		]]);
	}
}

?>