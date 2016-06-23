<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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