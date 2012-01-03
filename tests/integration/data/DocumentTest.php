<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\data\Connections;
use lithium\tests\mocks\data\Companies;

class DocumentTest extends \lithium\test\Integration {

	protected $_connection = null;

	protected $_key = null;

	public function setUp() {
		Companies::config();
		$this->_key = Companies::key();
		$this->_connection = Connections::get('test');
	}

	/**
	 * Skip the test if no test database connection available.
	 *
	 * @return void
	 */
	public function skip() {
		$isAvailable = (
			Connections::get('test', array('config' => true)) &&
			Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available.");
	}

	public function testUpdateWithNewArray() {
		$new = Companies::create(array('name' => 'Acme, Inc.', 'active' => true));

		$expected = array('name' => 'Acme, Inc.', 'active' => true);
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$new->foo = array('bar');
		$expected = array('name' => 'Acme, Inc.', 'active' => true, 'foo' => array('bar'));
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$this->assertTrue($new->save());

		$updated = Companies::find((string) $new->_id);
		$expected = 'bar';
		$result = $updated->foo[0];
		$this->assertEqual($expected, $result);

		$updated->foo[1] = 'baz';

		$this->assertTrue($updated->save());

		$updated = Companies::find((string) $updated->_id);
		$expected = 'baz';
		$result = $updated->foo[1];
		$this->assertEqual($expected, $result);
	}
}

?>