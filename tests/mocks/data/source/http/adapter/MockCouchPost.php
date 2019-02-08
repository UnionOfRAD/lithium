<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source\http\adapter;

use lithium\data\DocumentSchema;

class MockCouchPost extends \lithium\data\Model {

	protected $_meta = ['source' => 'posts', 'connection' => false, 'key' => 'id'];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'author_id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255],
		'body' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];

	public static function resetSchema($array = false) {
		if ($array) {
			return static::_object()->_schema = [];
		}
		static::_object()->_schema = new DocumentSchema();
	}
}

?>