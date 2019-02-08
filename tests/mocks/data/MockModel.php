<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data;

use lithium\tests\mocks\data\source\database\adapter\MockAdapter;

class MockModel extends \lithium\data\Model {

	protected $_meta = ['connection' => false];

	public static function &connection($records = null) {
		$mock = new MockAdapter(compact('records') + [
			'columns' => [
				'lithium\tests\mocks\data\MockModel' => ['id', 'data']
			],
			'autoConnect' => false
		]);
		static::meta(['key' => 'id', 'locked' => true]);
		return $mock;
	}
}

?>