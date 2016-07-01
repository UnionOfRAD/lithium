<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
		Connections::add('mockconn', ['object' => new MockSource()]);

		$schema = new Schema([
			'fields' => [
				'id' => 'int', 'title' => 'string', 'body' => 'text'
			]
		]);
		MockPost::config([
			'meta' => ['connection' => 'mockconn', 'key' => 'id', 'locked' => true],
			'schema' => $schema
		]);
		$this->_record = new Record(['model' => 'lithium\tests\mocks\data\MockPost']);
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockPost::reset();
	}

	/**
	 * Tests that a record's fields are accessible as object properties.
	 */
	public function testDataPropertyAccess() {
		$data = ['title' => 'Test record', 'body' => 'Some test record data'];
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
	 */
	public function testRecordFormatExport() {
		$data = ['foo' => 'bar'];
		$this->_record = new Record(compact('data'));

		$this->assertEqual($data, $this->_record->to('array'));
		$this->assertEqual($this->_record, $this->_record->to('foo'));
	}

	public function testErrorsPropertyAccess() {
		$errors = [
			'title' => 'please enter a title',
			'email' => ['email is empty', 'email is not valid']
		];

		$record = new Record();
		$result = $record->errors($errors);
		$this->assertEqual($errors, $result);

		$result = $record->errors();
		$this->assertEqual($errors, $result);

		$expected = 'please enter a title';
		$result = $record->errors('title');
		$this->assertEqual($expected, $result);

		$expected = ['email is empty', 'email is not valid'];
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
		$expected = ['id' => 1, 'name' => 'Joe Bloggs', 'address' => 'The Park'];
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

		$this->_record = new Record(['exists' => true]);
		$this->assertTrue($this->_record->exists());
	}

	public function testMethodDispatch() {
		$result = $this->_record->save(['title' => 'foo']);
		$this->assertEqual('create', $result['query']->type());
		$this->assertEqual(['title' => 'foo'], $result['query']->data());

		$record = $this->_record;
		$this->assertException("Unhandled method call `invalid`.", function() use ($record) {
			$record->invalid();
		});
	}
}

?>