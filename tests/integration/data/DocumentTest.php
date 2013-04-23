<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Galleries;

class DocumentTest extends \lithium\tests\integration\data\Base {

	/**
	 * Skip the test if no allowed database connection available.
	 */
	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(array('MongoDb', 'CouchDb')));
	}

	public function setUp() {
		Galleries::config(array('meta' => array('connection' => 'test')));
	}

	public function tearDown() {
		Galleries::remove();
		Galleries::reset();
	}

	public function testUpdateWithNewArray() {
		$new = Galleries::create(array('name' => 'Poneys', 'active' => true));

		$expected = array('name' => 'Poneys', 'active' => true);
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$new->foo = array('bar');
		$expected = array('name' => 'Poneys', 'active' => true, 'foo' => array('bar'));
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$this->assertTrue($new->save());

		$updated = Galleries::find((string) $new->_id);
		$expected = 'bar';
		$result = $updated->foo[0];
		$this->assertEqual($expected, $result);

		$updated->foo[1] = 'baz';

		$this->assertTrue($updated->save());

		$updated = Galleries::find((string) $updated->_id);
		$expected = 'baz';
		$result = $updated->foo[1];
		$this->assertEqual($expected, $result);
	}
}

?>