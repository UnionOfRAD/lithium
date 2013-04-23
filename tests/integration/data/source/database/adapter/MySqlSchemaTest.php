<?php
namespace lithium\tests\integration\data\source\database\adapter;

use lithium\data\Schema;
use lithium\data\Connections;
use lithium\tests\mocks\data\source\database\adapter\MockMySql;

class MySqlSchemaTest extends \lithium\tests\integration\data\Base {

	public function skip() {
		$connection = $this->_connection;
		$this->_dbConfig = Connections::get($this->_connection, array('config' => true));

		$this->skipIf(!$this->with(array('MySql')));

		$this->_db = new MockMySql($this->_dbConfig);
		$isConnected = $this->_db->isConnected(array('autoConnect' => true));
		$this->skipIf(!$isConnected, "No {$connection} connection available.");
	}

	public function testTableMeta() {
		$data = array(
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci',
			'engine' => 'InnoDB',
			'tablespace' => 'hello'
		);
		$result = array();
		foreach ($data as $key => $value) {
			$result[] = $this->_db->invokeMethod('_meta', array('table', $key, $value));
		}
		$expected = array(
			'DEFAULT CHARSET utf8',
			'COLLATE utf8_unicode_ci',
			'ENGINE InnoDB',
			'TABLESPACE hello'
		);
		$this->assertEqual($expected, $result);
	}

	public function testColumnMeta() {
		$data = array(
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci',
			'comment' => 'comment value'
		);
		$result = array();
		foreach ($data as $key => $value) {
			$result[] = $this->_db->invokeMethod('_meta', array('column', $key, $value));
		}
		$expected = array(
			'CHARACTER SET utf8',
			'COLLATE utf8_unicode_ci',
			'COMMENT \'comment value\''
		);
		$this->assertEqual($expected, $result);
	}

	public function testPrimaryKeyConstraint() {
		$data = array(
			'column' => 'id'
		);
		$result = $this->_db->invokeMethod('_constraint', array('primary', $data));
		$expected = 'PRIMARY KEY (`id`)';
		$this->assertEqual($expected, $result);

		$data = array(
			'column' => array('id', 'name')
		);
		$result = $this->_db->invokeMethod('_constraint', array('primary', $data));
		$expected = 'PRIMARY KEY (`id`, `name`)';
		$this->assertEqual($expected, $result);
	}

	public function testUniqueConstraint() {
		$data = array(
			'column' => 'id'
		);
		$result = $this->_db->invokeMethod('_constraint', array('unique', $data));
		$expected = 'UNIQUE (`id`)';
		$this->assertEqual($expected, $result);

		$data = array(
			'column' => array('id', 'name')
		);
		$result = $this->_db->invokeMethod('_constraint', array('unique', $data));
		$expected = 'UNIQUE (`id`, `name`)';
		$this->assertEqual($expected, $result);

		$data = array(
			'column' => array('id', 'name'),
			'index' => true
		);
		$result = $this->_db->invokeMethod('_constraint', array('unique', $data));
		$expected = 'UNIQUE INDEX (`id`, `name`)';
		$this->assertEqual($expected, $result);

		$data = array(
			'column' => array('id', 'name'),
			'index' => true,
			'key' => true
		);
		$result = $this->_db->invokeMethod('_constraint', array('unique', $data));
		$expected = 'UNIQUE KEY (`id`, `name`)';
		$this->assertEqual($expected, $result);
	}

	public function testCheckConstraint() {

		$schema = new Schema(array(
			'fields' => array(
				'value' => array('type' => 'integer'),
				'city' => array(
					'type' => 'string',
					'length' => 255,
					'null' => false
				)
			)
		));

		$data = array(
			'expr' => array(
				'value' => array('>' => '0'),
				'city' => 'Sandnes'
			)
		);
		$result = $this->_db->invokeMethod('_constraint', array('check', $data, $schema));
		$expected = 'CHECK ((`value` > 0) AND `city` = \'Sandnes\')';
		$this->assertEqual($expected, $result);
	}

	public function testForeignKeyConstraint() {
		$data = array(
			'column' => 'table_id',
			'to' => 'table',
			'toColumn' => 'id',
			'on' => 'DELETE CASCADE'
		);
		$result = $this->_db->invokeMethod('_constraint', array('foreign_key', $data));
		$expected = 'FOREIGN KEY (`table_id`) REFERENCES `table` (`id`) ON DELETE CASCADE';
		$this->assertEqual($expected, $result);
	}

	public function testBuildStringColumn() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 32,
			'null' => true,
			'comment' => 'test'
		);
		$result = $this->_db->column($data);
		$expected = '`fieldname` varchar(32) NULL COMMENT \'test\'';
		$this->assertEqual($expected, $result);

		$data['precision'] = 2;
		$result = $this->_db->column($data);
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 32,
			'default' => 'default value'
		);

		$result = $this->_db->column($data);
		$expected = '`fieldname` varchar(32) DEFAULT \'default value\'';
		$this->assertEqual($expected, $result);

		$data['null'] = false;
		$result = $this->_db->column($data);
		$expected = '`fieldname` varchar(32) NOT NULL DEFAULT \'default value\'';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 32,
			'null' => false,
			'charset' => 'utf8',
			'collate' => 'utf8_unicode_ci'
		);
		$result = $this->_db->column($data);
		$expected = '`fieldname` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL';
		$this->assertEqual($expected, $result);
	}

	public function testBuildFloatColumn() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'float',
			'length' => 10
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` float(10)";
		$this->assertEqual($expected, $result);

		$data['precision'] = 2;
		$result = $this->_db->column($data);
		$expected = "`fieldname` decimal(10,2)";
		$this->assertEqual($expected, $result);
	}

	public function testBuildTextColumn() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'text',
			'default' => 'value'
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` text DEFAULT 'value'";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'text',
			'default' => null
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` text";
		$this->assertEqual($expected, $result);
	}

	public function testBuildDatetimeColumn() {
		$data = array(
			'name' => 'created',
			'type' => 'datetime',
			'default' => (object) 'CURRENT_TIMESTAMP',
			'null' => false
		);

		$result = $this->_db->column($data);
		$expected = '`created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'created',
			'type' => 'datetime',
			'default' => (object) 'CURRENT_TIMESTAMP'
		);
		$result = $this->_db->column($data);
		$expected = '`created` datetime DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'modified',
			'type' => 'datetime',
			'null' => true
		);
		$result = $this->_db->column($data);
		$expected = '`modified` datetime NULL';
		$this->assertEqual($expected, $result);
	}

	public function testBuildDateColumn() {
		$data = array(
			'name' => 'created',
			'type' => 'date'
		);

		$result = $this->_db->column($data);
		$expected = '`created` date';
		$this->assertEqual($expected, $result);
	}

	public function testBuildTimeColumn() {
		$data = array(
			'name' => 'created',
			'type' => 'time'
		);

		$result = $this->_db->column($data);
		$expected = '`created` time';
		$this->assertEqual($expected, $result);
	}

	public function testBooleanColumn() {
		$data = array(
			'name' => 'bool',
			'type' => 'boolean'
		);

		$result = $this->_db->column($data);
		$expected = '`bool` tinyint(1)';
		$this->assertEqual($expected, $result);
	}

	public function testBinaryColumn() {
		$data = array(
			'name' => 'raw',
			'type' => 'binary'
		);

		$result = $this->_db->column($data);
		$expected = '`raw` blob';
		$this->assertEqual($expected, $result);
	}

	public function testBuildColumnCastDefaultValue() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'integer',
			'length' => 11,
			'default' => 1
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` int(11) DEFAULT 1";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'integer',
			'length' => 11,
			'default' => '1'
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` int(11) DEFAULT 1";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'length' => 64,
			'default' => 1
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` varchar(64) DEFAULT '1'";
		$this->assertEqual($expected, $result);

		$data = array(
			'name' => 'fieldname',
			'type' => 'text',
			'default' => 15
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` text DEFAULT '15'";
		$this->assertEqual($expected, $result);
	}

	public function testBuildColumnBadType() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'badtype',
			'null' => true
		);
		$this->expectException('Column type `badtype` does not exist.');
		$this->_db->column($data);
	}

	public function testOverrideType() {
		$data = array(
			'name' => 'fieldname',
			'type' => 'string',
			'use' => 'decimal',
			'length' => 11,
			'precision' => 2
		);
		$result = $this->_db->column($data);
		$expected = "`fieldname` decimal(11,2)";
		$this->assertEqual($expected, $result);
	}

	public function testCreateSchema() {
		$schema = new Schema(array(
			'fields' => array(
				'id' => array('type' => 'id'),
				'table_id' => array('type' => 'integer'),
				'published' => array(
					'type' => 'datetime',
					'null' => false,
					'default' => (object) 'CURRENT_TIMESTAMP'
				),
				'decimal' => array(
					'type' => 'float',
					'length' => 10,
					'precision' => 2
				),
				'integer' => array(
					'type' => 'integer',
					'use' => 'numeric',
					'length' => 10,
					'precision' => 2
				),
				'date' => array(
					'type' => 'date',
					'null' => false,
				),
				'text' => array(
					'type' => 'text',
					'null' => false,
				)
			),
			'meta' => array(
				'constraints' => array(
					array(
						'type' => 'primary',
						'column' => 'id'
					),
					array(
						'type' => 'check',
						'expr' => array(
							'integer' => array('<' => 10)
						)
					),
					array(
						'type' => 'foreign_key',
						'column' => 'table_id',
						'toColumn' => 'id',
						'to' => 'other_table',
						'on' => 'DELETE NO ACTION'
					)
				),
				'table' => array(
					'charset' => 'utf8',
					'collate' => 'utf8_unicode_ci',
					'engine' => 'InnoDB'
				)
			)
		));

		$result = $this->_db->dropSchema('test_table');
		$this->assertEqual('DROP TABLE IF EXISTS `test_table`;', $result);

		$expected = 'CREATE TABLE `test_table` (' . "\n";
		$expected .= '`id` int(11) NOT NULL AUTO_INCREMENT,' . "\n";
		$expected .= '`table_id` int(11),' . "\n";
		$expected .= '`published` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,' . "\n";
		$expected .= '`decimal` decimal(10,2),' . "\n";
		$expected .= '`integer` numeric(10,2),' . "\n";
		$expected .= '`date` date NOT NULL,' . "\n";
		$expected .= '`text` text NOT NULL,' . "\n";
		$expected .= 'PRIMARY KEY (`id`),' . "\n";
		$expected .= 'CHECK ((`integer` < 10)),' . "\n";
		$expected .= 'FOREIGN KEY (`table_id`) REFERENCES `other_table` (`id`) ';
		$expected .= 'ON DELETE NO ACTION) ';
		$expected .= 'DEFAULT CHARSET utf8 COLLATE utf8_unicode_ci ENGINE InnoDB;';

		$result = $this->_db->createSchema('test_table', $schema);
		$this->assertEqual($expected, $result);
	}
}

?>