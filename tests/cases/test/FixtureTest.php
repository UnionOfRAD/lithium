<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test;

use lithium\data\Connections;
use lithium\tests\mocks\core\MockLogCall;
use lithium\test\Fixture;
use lithium\tests\mocks\data\source\mongo_db\MockMongoId;

class FixtureTest extends \lithium\test\Unit {

	protected $_connection = 'fixture_test';

	protected $_callable = null;

	public function skip() {
		$this->_callable = new MockLogCall();
		Connections::add($this->_connection, [
			'object' => $this->_callable
		]);
	}

	public function tearDown() {
		$this->_callable->__clear();
	}

	public function testInitMissingConnection() {
		$this->assertException("The `'connection'` option must be set.", function() {
			new Fixture();
		});
	}

	public function testInitMissingModelAndSource() {
		$this->assertException("The `'model'` or `'source'` option must be set.", function() {
			new Fixture(['connection' => $this->_connection]);
		});
	}

	public function testCreate() {
		$fields = [
			'id' => ['type' => 'id'],
			'name' => ['type' => 'string']
		];

		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields,
			'records' => [
				['id' => 1, 'name' => 'Nate'],
				['id' => 2, 'name' => 'Gwoo']
			]
		]);

		$fixture->create(false);
		$this->assertEqual(1, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);
		$this->assertEqual($fields, $call['params'][1]->fields());

		$fixture->create();
		$call = $this->_callable->call[1];
		$this->assertEqual('sources', $call['method']);
	}

	public function testDrop() {
		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts'
		]);

		$fixture->drop(false);
		$call = $this->_callable->call[0];
		$this->assertEqual('dropSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$fixture->drop();
		$call = $this->_callable->call[1];
		$this->assertEqual('sources', $call['method']);
	}

	public function testTruncate() {
		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts'
		]);

		$fixture->truncate(false);
		$call = $this->_callable->call[0];
		$this->assertEqual('delete', $call['method']);

		$this->_callable->__clear();
		$this->_callable->return = ['sources' => ['contacts']];

		$fixture->truncate();
		$call = $this->_callable->call[0];
		$this->assertEqual('sources', $call['method']);

		$fixture->truncate();
		$call = $this->_callable->call[1];
		$this->assertEqual('delete', $call['method']);
	}

	public function testSave() {
		$fields = [
			'id' => ['type' => 'id'],
			'name' => ['type' => 'string']
		];

		$records = [
			['id' => 1, 'name' => 'Nate'],
			['id' => 2, 'name' => 'Gwoo']
		];

		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields,
			'records' => $records
		]);

		$fixture->save(false);
		$this->assertEqual(3, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);
		$this->assertEqual($fields, $call['params'][1]->fields());

		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual(['data' => $records[0]], $query->data());

		$call = $this->_callable->call[2];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual(['data' => $records[1]], $query->data());
	}

	public function testAlter() {
		$fields = [
			'id' => ['type' => 'id'],
			'name' => ['type' => 'string'],
			'useless' => ['type' => 'string']
		];

		$records = [
			['id' => 1, 'name' => 'Nate', 'useless' => 'a'],
			['id' => 2, 'name' => 'Gwoo', 'useless' => 'b']
		];

		$alters = [
			'add' => [
				'lastname' => ['type' => 'string', 'default' => 'li3']
			],
			'change' => [
				'id' => [
					'type' => 'string',
					'length' => '24',
					'to' => '_id',
					'value' => function ($val) {
						return new MockMongoId('4c3628558ead0e594' . (string) ($val + 1000000));
					}
				],
				'name' => [
					'to' => 'firstname'
				]
			],
			'drop' => [
				'useless'
			]
		];

		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields,
			'records' => $records,
			'alters' => $alters
		]);

		$this->assertEqual($alters, $fixture->alter());

		$fixture->save(false);
		$this->assertEqual(3, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$expected = [
			'_id' => ['type' => 'string', 'length' => 24],
			'firstname' => ['type' => 'string'],
			'lastname' => ['type' => 'string', 'default' => 'li3']
		];
		$this->assertEqual($expected, $call['params'][1]->fields());

		$expected = [
			['_id' => new MockMongoId('4c3628558ead0e5941000001'), 'firstname' => 'Nate'],
			['_id' => new MockMongoId('4c3628558ead0e5941000002'), 'firstname' => 'Gwoo']
		];
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual(['data' => $expected[0]], $query->data());

		$call = $this->_callable->call[2];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual(['data' => $expected[1]], $query->data());
	}

	public function testPopulate() {
		$fields = [
			'id' => ['type' => 'id'],
			'name' => ['type' => 'string'],
			'useless' => ['type' => 'string']
		];

		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields
		]);

		$fixture->create(false);
		$this->assertEqual(1, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$record = ['id' => 1, 'name' => 'Nate', 'useless' => 'a'];
		$fixture->populate($record);
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual(['data' => $record], $query->data());
	}

	public function testLiveAlter() {
		$fields = [
			'id' => ['type' => 'id'],
			'name' => ['type' => 'string'],
			'useless' => ['type' => 'string']
		];

		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields
		]);

		$fixture->alter('change', 'id', [
			'type' => 'string',
			'length' => '24',
			'to' => '_id',
			'value' => function ($val) {
				return new MockMongoId('4c3628558ead0e594' . (string) ($val + 1000000));
			}
		]);
		$fixture->alter('change', 'name', ['to' => 'firstname']);
		$fixture->alter('drop', 'useless');
		$fixture->alter('add', 'lastname', ['type' => 'string', 'default' => 'li3']);

		$fixture->create(false);
		$this->assertEqual(1, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$record = ['id' => 1, 'name' => 'Nate', 'useless' => 'a'];
		$fixture->populate($record);
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$expected = ['_id' => new MockMongoId('4c3628558ead0e5941000001'), 'firstname' => 'Nate'];
		$this->assertEqual(['data' => $expected], $query->data());

		$record = ['id' => 1, 'name' => 'Nate', 'useless' => 'a'];
		$fixture->populate($record, false);
		$call = $this->_callable->call[2];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$expected = ['id' => 1, 'name' => 'Nate'];
		$this->assertEqual(['data' => []], $query->data());
	}

	public function testSchemaLess() {
		$record = [
			'_id' => new MockMongoId('4c3628558ead0e5941000001'),
			'name' => 'John'
		];
		$fixture = new Fixture([
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => [],
			'records' => [$record],
			'locked' => false
		]);

		MockLogCall::$returnStatic = ['enabled' => false];
		$this->_callable->return = ['sources' => ['contacts']];

		$fixture->drop();
		$call = $this->_callable->call[0];
		$this->assertEqual('delete', $call['method']);

		$this->_callable->__clear();
		$this->_callable->return = ['sources' => ['contacts']];
		$fixture->create();
		$call = $this->_callable->call[0];
		$this->assertEqual(1, count($this->_callable->call));
		$this->assertEqual('delete', $call['method']);


		$this->_callable->__clear();
		$this->_callable->return = ['sources' => ['contacts']];
		$fixture->save();
		$this->assertEqual(2, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('delete', $call['method']);
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$this->assertEqual(['data' => $record], $call['params'][0]->data());
	}
}

?>