<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\entity;

use lithium\data\Connections;
use lithium\data\entity\Record;
use lithium\data\Schema;
use lithium\tests\mocks\data\MockSource;
use lithium\tests\mocks\data\MockPost;

class RecordTest extends \lithium\test\Unit {

	protected $_record = null;

	public function setUp() {
		Connections::add('mockconn', array('object' => new MockSource()));

		$schema = new Schema(array(
			'fields' => array(
				'id' => 'int', 'title' => 'string', 'body' => 'text'
			)
		));
		MockPost::config(array(
			'meta' => array('connection' => 'mockconn', 'key' => 'id', 'locked' => true),
			'schema' => $schema
		));
		$this->_record = new Record(array('model' => 'lithium\tests\mocks\data\MockPost'));
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockPost::reset();
	}

	/**
	 * Tests that a record's fields are accessible as object properties.
	 *
	 * @return void
	 */
	public function testDataPropertyAccess() {
		$data = array('title' => 'Test record', 'body' => 'Some test record data');
		$this->_record = new Record(compact('data'));

		$this->assertEqual('Test record', $this->_record->title);
		$this->assertTrue(isset($this->_record->title));

		$this->assertEqual('Some test record data', $this->_record->body);
		$this->assertTrue(isset($this->_record->body));

		$this->assertNull($this->_record->foo);
		$this->assertFalse(isset($this->_record->foo));
	}

	/**
	 * Tests that a record can be exported to a given series of formats.
	 *
	 * @return void
	 */
	public function testRecordFormatExport() {
		$data = array('foo' => 'bar');
		$this->_record = new Record(compact('data'));

		$this->assertEqual($data, $this->_record->to('array'));
		$this->assertEqual($this->_record, $this->_record->to('foo'));
	}

	public function testErrorsPropertyAccess() {
		$errors = array(
			'title' => 'please enter a title',
			'email' => array('email is empty', 'email is not valid')
		);

		$record = new Record();
		$result = $record->errors($errors);
		$this->assertEqual($errors, $result);

		$result = $record->errors();
		$this->assertEqual($errors, $result);

		$expected = 'please enter a title';
		$result = $record->errors('title');
		$this->assertEqual($expected, $result);

		$expected = array('email is empty', 'email is not valid');
		$result = $record->errors('email');
		$this->assertEqual($expected, $result);

		$result = $record->errors('not_a_field');
		$this->assertNull($result);

		$result = $record->errors('not_a_field', 'badness');
		$this->assertEqual('badness', $result);
	}

	/**
	 * Test the ability to set multiple field's values, and that they can be read back.
	 */
	public function testSetData() {
		$this->assertEmpty($this->_record->data());
		$expected = array('id' => 1, 'name' => 'Joe Bloggs', 'address' => 'The Park');
		$this->_record->set($expected);
		$this->assertEqual($expected, $this->_record->data());
		$this->assertEqual($expected, $this->_record->to('array'));
		$this->assertEqual($expected['name'], $this->_record->data('name'));
	}

	public function testRecordExists() {
		$this->assertFalse($this->_record->exists());
		$this->_record->sync(313);
		$this->assertIdentical(313, $this->_record->id);
		$this->assertTrue($this->_record->exists());

		$this->_record = new Record(array('exists' => true));
		$this->assertTrue($this->_record->exists());
	}

	public function testMethodDispatch() {
		$result = $this->_record->save(array('title' => 'foo'));
		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual(array('title' => 'foo'), $result['query']->data());

		$this->expectException("Unhandled method call `invalid`.");
		$this->assertNull($this->_record->invalid());
	}
}

?>