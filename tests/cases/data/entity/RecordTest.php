<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\entity;

use lithium\data\Connections;
use lithium\data\entity\Record;

class RecordTest extends \lithium\test\Unit {

	protected $_configs = array();

	public function setUp() {
		$this->_configs = Connections::config();

		Connections::config(array('mock-source' => array(
			'type' => '\lithium\tests\mocks\data\MockSource'
		)));
		$model = 'lithium\tests\mocks\data\MockPost';
		$model::config(array('connection' => 'mock-source', 'key' => 'id'));
		$this->record = new Record(compact('model'));
	}

	public function tearDown() {
		Connections::reset();
		Connections::config($this->_configs);
	}

	/**
	 * Tests that a record's fields are accessible as object properties.
	 *
	 * @return void
	 */
	public function testDataPropertyAccess() {
		$data = array(
			'title' => 'Test record',
			'body' => 'Some test record data'
		);

		$this->record = new Record(compact('data'));

		$expected = 'Test record';
		$result = $this->record->title;
		$this->assertEqual($expected, $result);
		$this->assertTrue(isset($this->record->title));

		$expected = 'Some test record data';
		$result = $this->record->body;
		$this->assertEqual($expected, $result);
		$this->assertTrue(isset($this->record->body));

		$this->assertNull($this->record->foo);
		$this->assertFalse(isset($this->record->foo));
	}

	/**
	 * Tests that a record can be exported to a given series of formats.
	 *
	 * @return void
	 */
	public function testRecordFormatExport() {
		$data = array('foo' => 'bar');
		$this->record = new Record(compact('data'));

		$result = $this->record->to('array');
		$expected = $data;
		$this->assertEqual($expected, $result);

		$result = $this->record->to('foo');
		$this->assertEqual($this->record, $result);
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
		$this->assertFalse($this->record->data());
		$expected = array('id' => 1, 'name' => 'Joe Bloggs', 'address' => 'The Park');
		$this->record->set($expected);
		$this->assertEqual($expected, $this->record->data());
		$this->assertEqual($expected, $this->record->to('array'));
		$this->assertEqual($expected['name'], $this->record->data('name'));
	}

	public function testRecordExists() {
		$this->assertFalse($this->record->exists());
		$this->record->update(313);
		$this->assertIdentical(313, $this->record->id);
		$this->assertTrue($this->record->exists());

		$this->record = new Record(array('exists' => true));
		$this->assertTrue($this->record->exists());
	}

	public function testMethodDispatch() {
		$result = $this->record->save(array('title' => 'foo'));
		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual(array('title' => 'foo'), $result['query']->data());

		$this->expectException("No model bound or unhandled method call `invalid`.");
		$this->assertNull($this->record->invalid());
	}
}

?>