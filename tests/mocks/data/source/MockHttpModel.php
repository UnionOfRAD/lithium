<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source;

use lithium\data\source\Http;

class MockHttpModel extends \lithium\data\Model {

	protected $_meta = [
		'source' => 'posts',
		'connection' => false
	];

	public static $connection = null;

	protected $_schema = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'author_id' => ['type' => 'integer'],
		'title' => ['type' => 'string', 'length' => 255],
		'body' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];

	public static function &connection() {
		if (static::$connection) {
			return static::$connection;
		}
		$result = new Http();
		return $result;
	}
}

?>