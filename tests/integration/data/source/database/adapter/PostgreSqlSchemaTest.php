<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */
namespace lithium\tests\integration\data\source\database\adapter;

use lithium\data\Schema;
use lithium\data\Connections;
use lithium\tests\mocks\data\source\database\adapter\MockPostgreSql;

class PostgreSqlSchemaTest extends \lithium\tests\integration\data\Base {

	public function skip() {
		$connection = $this->_connection;
		$this->_dbConfig = Connections::get($this->_connection, ['config' => true]);

		$this->skipIf(!$this->with(['PostgreSql']));

		$this->_db = new MockPostgreSql($this->_dbConfig);
		$isConnected = $this->_db->isConnected(['autoConnect' => true]);
		$this->skipIf(!$isConnected, "No {$connection} connection available.");
	}

	public function testTableMeta() {
		$data = [
			'tablespace' => 'hello'
		];
		$result = [];
		foreach ($data as $key => $value) {
			$result[] = $this->_db->invokeMethod('_meta', ['table', $key, $value]);
		}
		$expected = [
			'TABLESPACE hello'
		];
		$this->assertEqual($expected, $result);
	}

	public function testPrimaryKeyConstraint() {
		$data = [
			'column' => 'id'
		];
		$result = $this->_db->invokeMethod('_constraint', ['primary', $data]);
		$expected = 'PRIMARY KEY ("id")';
		$this->assertEqual($expected, $result);

		$data = [
			'column' => ['id', 'name']
		];
		$result = $this->_db->invokeMethod('_constraint', ['primary', $data]);
		$expected = 'PRIMARY KEY ("id", "name")';
		$this->assertEqual($expected, $result);
	}

	public function testUniqueConstraint() {
		$data = [
			'column' => 'id'
		];
		$result = $this->_db->invokeMethod('_constraint', ['unique', $data]);
		$expected = 'UNIQUE ("id")';
		$this->assertEqual($expected, $result);

		$data = [
			'column' => ['id', 'name']
		];
		$result = $this->_db->invokeMethod('_constraint', ['unique', $data]);
		$expected = 'UNIQUE ("id", "name")';
		$this->assertEqual($expected, $result);

		$data = [
			'column' => ['id', 'name'],
			'index' => true
		];
		$result = $this->_db->invokeMethod('_constraint', ['unique', $data]);
		$expected = 'UNIQUE ("id", "name")';
		$this->assertEqual($expected, $result);
	}

	public function testCheckConstraint() {

		$schema = new Schema([
			'fields' => [
				'value' => ['type' => 'integer'],
				'city' => [
					'type' => 'string',
					'length' => 255,
					'null' => false
				]
			]
		]);

		$data = [
			'expr' => [
				'value' => ['>' => '0'],
				'city' => 'Sandnes'
			]
		];
		$result = $this->_db->invokeMethod('_constraint', ['check', $data, $schema]);
		$expected = 'CHECK (("value" > 0) AND "city" = \'Sandnes\')';
		$this->assertEqual($expected, $result);
	}

	public function testForeignKeyConstraint() {
		$data = [
			'column' => 'table_id',
			'to' => 'table',
			'toColumn' => 'id',
			'on' => 'DELETE CASCADE'
		];
		$result = $this->_db->invokeMethod('_constraint', ['foreign_key', $data]);
		$expected = 'FOREIGN KEY ("table_id") REFERENCES "table" ("id") ON DELETE CASCADE';
		$this->assertEqual($expected, $result);
	}

	public function testBuildStringColumn() {
		$data = [
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 32,
			'null' => true,
			'comment' => 'test'
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" varchar(32) NULL';
		$this->assertEqual($expected, $result);

		$data['precision'] = 2;
		$result = $this->_db->column($data);
		$this->assertEqual($expected, $result);

		$data = [
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 32,
			'default' => 'default value'
		];

		$result = $this->_db->column($data);
		$expected = '"fieldname" varchar(32) DEFAULT \'default value\'';
		$this->assertEqual($expected, $result);

		$data['null'] = false;
		$result = $this->_db->column($data);
		$expected = '"fieldname" varchar(32) NOT NULL DEFAULT \'default value\'';
		$this->assertEqual($expected, $result);
	}

	public function testBuildFloatColumn() {
		$data = [
			'name' => 'fieldname',
			'type' => 'float',
			'length' => 10
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" real';
		$this->assertEqual($expected, $result);

		$data['precision'] = 2;
		$result = $this->_db->column($data);
		$expected = '"fieldname" numeric(10,2)';
		$this->assertEqual($expected, $result);
	}

	public function testBuildTextColumn() {
		$data = [
			'name' => 'fieldname',
			'type' => 'text',
			'default' => 'value'
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" text DEFAULT \'value\'';
		$this->assertEqual($expected, $result);

		$data = [
			'name' => 'fieldname',
			'type' => 'text',
			'default' => null
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" text';
		$this->assertEqual($expected, $result);
	}

	public function testBuildDatetimeColumn() {
		$data = [
			'name' => 'created',
			'type' => 'datetime',
			'default' => (object) 'CURRENT_TIMESTAMP',
			'null' => false
		];

		$result = $this->_db->column($data);
		$expected = '"created" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($expected, $result);

		$data = [
			'name' => 'created',
			'type' => 'datetime',
			'default' => (object) 'CURRENT_TIMESTAMP'
		];
		$result = $this->_db->column($data);
		$expected = '"created" timestamp DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($expected, $result);

		$data = [
			'name' => 'modified',
			'type' => 'datetime',
			'null' => true
		];
		$result = $this->_db->column($data);
		$expected = '"modified" timestamp NULL';
		$this->assertEqual($expected, $result);
	}

	public function testBuildDateColumn() {
		$data = [
			'name' => 'created',
			'type' => 'date'
		];

		$result = $this->_db->column($data);
		$expected = '"created" date';
		$this->assertEqual($expected, $result);
	}

	public function testBuildTimeColumn() {
		$data = [
			'name' => 'created',
			'type' => 'time'
		];

		$result = $this->_db->column($data);
		$expected = '"created" time';
		$this->assertEqual($expected, $result);
	}

	public function testBooleanColumn() {
		$data = [
			'name' => 'bool',
			'type' => 'boolean'
		];

		$result = $this->_db->column($data);
		$expected = '"bool" boolean';
		$this->assertEqual($expected, $result);
	}

	public function testBinaryColumn() {
		$data = [
			'name' => 'raw',
			'type' => 'binary'
		];

		$result = $this->_db->column($data);
		$expected = '"raw" bytea';
		$this->assertEqual($expected, $result);
	}

	public function testBuildColumnCastDefaultValue() {
		$data = [
			'name' => 'fieldname',
			'type' => 'integer',
			'length' => 11,
			'default' => 1
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" integer DEFAULT 1';
		$this->assertEqual($expected, $result);

		$data = [
			'name' => 'fieldname',
			'type' => 'integer',
			'length' => 11,
			'default' => '1'
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" integer DEFAULT 1';
		$this->assertEqual($expected, $result);

		$data = [
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 64,
			'default' => 1
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" varchar(64) DEFAULT \'1\'';
		$this->assertEqual($expected, $result);

		$data = [
			'name' => 'fieldname',
			'type' => 'text',
			'default' => 15
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" text DEFAULT \'15\'';
		$this->assertEqual($expected, $result);
	}

	public function testBuildColumnBadType() {
		$data = [
			'name' => 'fieldname',
			'type' => 'badtype',
			'null' => true
		];
		$db = $this->_db;

		$this->assertException('Column type `badtype` does not exist.', function() use ($db, $data) {
			$db->column($data);
		});
	}

	public function testOverrideType() {
		$data = [
			'name' => 'fieldname',
			'type' => 'string',
			'use' => 'numeric',
			'length' => 11,
			'precision' => 2
		];
		$result = $this->_db->column($data);
		$expected = '"fieldname" numeric(11,2)';
		$this->assertEqual($expected, $result);
	}

	public function testCreateSchema() {
		$schema = new Schema([
			'fields' => [
				'id' => ['type' => 'id'],
				'table_id' => ['type' => 'integer'],
				'published' => [
					'type' => 'datetime',
					'null' => false,
					'default' => (object) 'CURRENT_TIMESTAMP'
				],
				'decimal' => [
					'type' => 'float',
					'length' => 10,
					'precision' => 2
				],
				'integer' => [
					'type' => 'integer',
					'use' => 'numeric',
					'length' => 10,
					'precision' => 2
				],
				'date' => [
					'type' => 'date',
					'null' => false,
				],
				'text' => [
					'type' => 'text',
					'null' => false,
				]
			],
			'meta' => [
				'constraints' => [
					[
						'type' => 'primary',
						'column' => 'id'
					],
					[
						'type' => 'check',
						'expr' => [
							'integer' => ['<' => 10]
						]
					],
					[
						'type' => 'foreign_key',
						'column' => 'table_id',
						'toColumn' => 'id',
						'to' => 'other_table',
						'on' => 'DELETE NO ACTION'
					],
				]
			]
		]);

		$result = $this->_db->dropSchema('test_table');
		$this->assertEqual('DROP TABLE IF EXISTS "test_table";', $result);

		$expected = 'CREATE TABLE "test_table" (' . "\n";
		$expected .= '"id" serial NOT NULL,' . "\n";
		$expected .= '"table_id" integer,' . "\n";
		$expected .= '"published" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,' . "\n";
		$expected .= '"decimal" numeric(10,2),' . "\n";
		$expected .= '"integer" numeric(10,2),' . "\n";
		$expected .= '"date" date NOT NULL,' . "\n";
		$expected .= '"text" text NOT NULL,' . "\n";
		$expected .= 'PRIMARY KEY ("id"),' . "\n";
		$expected .= 'CHECK (("integer" < 10)),' . "\n";
		$expected .= 'FOREIGN KEY ("table_id") REFERENCES "other_table" ("id") ';
		$expected .= 'ON DELETE NO ACTION);';

		$result = $this->_db->createSchema('test_table', $schema);
		$this->assertEqual($expected, $result);
	}
}

?>