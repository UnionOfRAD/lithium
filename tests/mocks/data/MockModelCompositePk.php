<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

use lithium\tests\mocks\data\source\database\adapter\MockAdapter;

class MockModelCompositePk extends \lithium\data\Model {

	public static function __init() {}

	public static function &connection($records = null) {
		$mock = new MockAdapter(compact('records') + array(
			'columns' => array('lithium\tests\mocks\data\MockModelCompositePk' => array('client_id', 'invoice_id', 'payment')),
			'autoConnect' => false
		));
		self::meta(array(
			'key' => array('client_id', 'invoice_id'),
			'locked' => true
		));
		return $mock;
	}
}

?>
