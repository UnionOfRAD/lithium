<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\data\source\database\adapter;

use lithium\data\Schema;
use lithium\data\model\Query;
use lithium\data\source\database\adapter\MySql;
use lithium\tests\mocks\data\source\database\adapter\MockMySql;
use lithium\tests\fixture\model\gallery\Galleries;
use ReflectionMethod;

class MySqlTest extends \lithium\tests\integration\data\Base {

	protected $_schema = ['fields' => [
		'id' => ['type' => 'id'],
		'name' => ['type' => 'string', 'length' => 255],
		'active' => ['type' => 'boolean'],
		'created' => ['type' => 'datetime', 'null' => true],
		'modified' => ['type' => 'datetime', 'null' => true]
	]];

	/**
	 * Skip the test if a MySQL adapter configuration is unavailable.
	 */
	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(['MySql']));
	}

	public function setUp() {
		$this->_db->dropSchema('galleries');
		$schema = new Schema($this->_schema);
		$this->_db->createSchema('galleries', $schema);
		Galleries::config(['meta' => ['connection' => $this->_connection]]);
	}

	public function tearDown() {
		$this->_db->dropSchema('galleries');
		Galleries::reset();
	}

	public function testEnabledFeatures() {
		$supported = ['booleans', 'schema', 'relationships', 'sources'];
		$notSupported = ['arrays', 'transactions'];

		foreach ($supported as $feature) {
			$this->assertTrue(MySql::enabled($feature));
		}

		foreach ($notSupported as $feature) {
			$this->assertFalse(MySql::enabled($feature));
		}

		$this->assertNull(MySql::enabled('unexisting'));
	}

	/**
	 * Tests that the object is initialized with the correct default values.
	 */
	public function testConstructorDefaults() {
		$db = new MockMySql(['autoConnect' => false, AUTO_INIT_CLASS => false]);
		$result = $db->get('_config');
		$expected = [
			'autoConnect' => false, 'encoding' => null,'persistent' => true,
			'host' => 'localhost:3306', 'strict' => null, 'login' => 'root', 'password' => '',
			'database' => null, 'dsn' => null, 'options' => [], 'init' => false
		];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that this adapter can connect to the database, and that the status is properly
	 * persisted.
	 */
	public function testDatabaseConnection() {
		$db = new MySql(['autoConnect' => false] + $this->_dbConfig);

		$this->assertTrue($db->connect());
		$this->assertTrue($db->isConnected());

		$this->assertTrue($db->disconnect());
		$this->assertFalse($db->isConnected());

		$db = new MySQL([
			'autoConnect' => false, 'encoding' => null,'persistent' => false,
			'host' => 'localhost:3306', 'login' => 'garbage', 'password' => '',
			'database' => 'garbage', 'init' => true
		] + $this->_dbConfig);

		$this->assertException('/.*/', function() use ($db) {
			$db->connect();
		});
		$this->assertFalse($db->isConnected());

		$this->assertTrue($db->disconnect());
		$this->assertFalse($db->isConnected());
	}

	public function testDatabaseEncoding() {
		$this->assertTrue($this->_db->isConnected());

		$this->assertTrue($this->_db->encoding('ascii'));
		$this->assertEqual('ascii', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('ASCII'));
		$this->assertEqual('ascii', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('LATIN2'));
		$this->assertEqual('latin2',  $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('UTF8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('utf8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('UTF-8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('utf-8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('utf8mb4'));
		$this->assertEqual('utf8mb4', $this->_db->encoding());
	}

	public function testDatabaseStrictMode() {
		$backup = $this->_db->strict();

		$this->assertTrue($this->_db->strict(false));
		$this->assertFalse($this->_db->strict());

		$this->assertTrue($this->_db->strict(true));
		$this->assertTrue($this->_db->strict());

		$this->_db->strict($backup);
	}

	public function testValueByIntrospect() {
		$expected = "'string'";
		$result = $this->_db->value("string");
		$this->assertInternalType('string', $result);
		$this->assertEqual($expected, $result);

		$expected = "'\'this string is escaped\''";
		$result = $this->_db->value("'this string is escaped'");
		$this->assertInternalType('string', $result);
		$this->assertEqual($expected, $result);

		$this->assertIdentical(1, $this->_db->value(true));
		$this->assertIdentical(1, $this->_db->value('1'));
		$this->assertIdentical(1.1, $this->_db->value('1.1'));
	}

	public function testValueWithSchema() {
		$result = $this->_db->value('2013-01-07 13:57:03.621684', ['type' => 'timestamp']);
		$this->assertIdentical("'2013-01-07 13:57:03'", $result);

		$result = $this->_db->value('2012-05-25 22:44:00', ['type' => 'timestamp']);
		$this->assertIdentical("'2012-05-25 22:44:00'", $result);

		$result = $this->_db->value('2012-00-00', ['type' => 'date']);
		$this->assertIdentical("'2011-11-30'", $result);

		$result = $this->_db->value((object) "'2012-00-00'", ['type' => 'date']);
		$this->assertIdentical("'2012-00-00'", $result);
	}

	public function testNameQuoting() {
		$result = $this->_db->name('title');
		$expected = '`title`';
		$this->assertEqual($expected, $result);
	}

	public function testColumnAbstraction() {
		$method = new ReflectionMethod($this->_db, '_column');
		$method->setAccessible(true);

		$result = $method->invokeArgs($this->_db, ['varchar']);
		$this->assertIdentical(['type' => 'string'], $result);

		$result = $method->invokeArgs($this->_db, ['tinyint(1)']);
		$this->assertIdentical(['type' => 'boolean'], $result);

		$result = $method->invokeArgs($this->_db, ['varchar(255)']);
		$this->assertIdentical(['type' => 'string', 'length' => 255], $result);

		$result = $method->invokeArgs($this->_db, ['text']);
		$this->assertIdentical(['type' => 'text'], $result);

		$result = $method->invokeArgs($this->_db, ['text']);
		$this->assertIdentical(['type' => 'text'], $result);

		$result = $method->invokeArgs($this->_db, ['decimal(12,2)']);
		$this->assertIdentical(['type' => 'float', 'length' => 12, 'precision' => 2], $result);

		$result = $method->invokeArgs($this->_db, ['int(11)']);
		$this->assertIdentical(['type' => 'integer', 'length' => 11], $result);
	}

	public function testRawSqlQuerying() {
		$this->assertTrue($this->_db->create(
			'INSERT INTO galleries (name, active) VALUES (?, ?)',
			['Test', 1]
		));

		$result = $this->_db->read('SELECT * FROM galleries AS Company WHERE name = {:name}', [
			'name' => 'Test',
			'return' => 'array'
		]);
		$this->assertCount(1, $result);
		$expected = ['id', 'name', 'active', 'created', 'modified'];
		$this->assertEqual($expected, array_keys($result[0]));

		$this->assertInternalType('numeric', $result[0]['id']);
		unset($result[0]['id']);

		$expected = [
			'name' => 'Test',
			'active' => '1',
			'created' => null,
			'modified' => null
		];
		$this->assertIdentical($expected, $result[0]);

		$this->assertTrue($this->_db->delete('DELETE FROM galleries WHERE name = {:name}', [
			'name' => 'Test'
		]));

		$result = $this->_db->read('SELECT * FROM galleries AS Company WHERE name = {:name}', [
			'name' => 'Test',
			'return' => 'array'
		]);
		$this->assertEmpty($result);
	}

	public function testExecuteException() {
		$db = $this->_db;

		$this->assertException('/.*/', function() use ($db) {
			$db->read('SELECT deliberate syntax error');
		});
	}

	public function testEntityQuerying() {
		$sources = $this->_db->sources();
		$this->assertInternalType('array', $sources);
		$this->assertNotEmpty($sources);
	}

	public function testQueryOrdering() {
		$insert = new Query([
			'type' => 'create',
			'source' => 'galleries',
			'data' => [
				'name' => 'Foo',
				'active' => true,
				'created' => date('Y-m-d H:i:s')
			]
		]);
		$this->assertTrue($this->_db->create($insert));

		$insert->data([
			'name' => 'Bar',
			'created' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
		]);
		$this->assertTrue($this->_db->create($insert));

		$insert->data([
			'name' => 'Baz',
			'created' => date('Y-m-d H:i:s', strtotime('-10 minutes'))
		]);
		$this->assertTrue($this->_db->create($insert));

		$read = new Query([
			'type' => 'read',
			'source' => 'galleries',
			'fields' => ['name'],
			'order' => ['created' => 'asc']
		]);
		$result = $this->_db->read($read, ['return' => 'array']);
		$expected = [
			['name' => 'Baz'],
			['name' => 'Bar'],
			['name' => 'Foo']
		];
		$this->assertEqual($expected, $result);

		$read->order(['created' => 'desc']);
		$result = $this->_db->read($read, ['return' => 'array']);
		$expected = [
			['name' => 'Foo'],
			['name' => 'Bar'],
			['name' => 'Baz']
		];
		$this->assertEqual($expected, $result);

		$delete = new Query(['type' => 'delete', 'source' => 'galleries']);
		$this->assertTrue($this->_db->delete($delete));
	}

	/**
	 * Ensures that DELETE queries are not generated with table aliases, as MySQL does not support
	 * this.
	 */
	public function testDeletesWithoutAliases() {
		$delete = new Query(['type' => 'delete', 'source' => 'galleries']);
		$this->assertTrue($this->_db->delete($delete));
	}

	/**
	 * Tests that describing a table's schema returns the correct column meta-information.
	 */
	public function testDescribe() {
		$result = $this->_db->describe('galleries')->fields();
		$expected = [
			'id' => ['type' => 'integer', 'length' => 11, 'null' => false, 'default' => null],
			'name' => [
				'type' => 'string', 'length' => 255, 'null' => true, 'default' => null
			],
			'active' => ['type' => 'boolean', 'null' => true, 'default' => null],
			'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
			'modified' => ['type' => 'datetime', 'null' => true, 'default' => null]
		];
		$this->assertEqual($expected, $result);

		unset($expected['name']);
		unset($expected['modified']);
		$result = $this->_db->describe('galleries', $expected)->fields();
		$this->assertEqual($expected, $result);
	}

	public function testResultSet() {
		for ($i = 1; $i < 9; $i++) {
			Galleries::create(['id' => $i, 'name' => "Title {$i}"])->save();
		}

		$galleries = Galleries::all();

		$cpt = 0;
		foreach ($galleries as $gallery) {
			$this->assertEqual(++$cpt, $gallery->id);
		}
		$this->assertIdentical(8, $cpt);
		$this->assertCount(8, $galleries);
	}

	/**
	 * Contrary to other data sources, MySQL only support one TIMESTAMP column by table.
	 */
	public function testDefaultValues() {
		$this->_db->dropSchema('galleries');

		$schema = new Schema(['fields' => [
			'id' => ['type' => 'id'],
			'name' => ['type' => 'string', 'length' => 255, 'default' => 'image'],
			'active' => ['type' => 'boolean', 'default' => false],
			'show' => ['type' => 'boolean', 'default' => true],
			'empty' => ['type' => 'text', 'null' => true],
			'created' => [
				'type' => 'timestamp', 'null' => true, 'default' => (object) 'CURRENT_TIMESTAMP'
			]
		]]);

		$this->_db->createSchema('galleries', $schema);

		$gallery = Galleries::create();
		$this->assertEqual('image', $gallery->name);
		$this->assertEqual(false, $gallery->active);
		$this->assertEqual(true, $gallery->show);
		$this->assertEqual(null, $gallery->empty);

		$gallery->save();
		$result = Galleries::find('first')->data();

		$this->assertEqual(1, $result['id']);
		$this->assertEqual('image', $result['name']);
		$this->assertEqual(false, $result['active']);
		$this->assertEqual(true, $result['show']);
		$this->assertEqual(null, $result['empty']);

		$this->assertPattern('$\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$', $result['created']);
		$this->assertTrue(time() - strtotime($result['created']) < 24 * 3600);
	}

	/**
	 * Verifies that setting options using a raw SQL string works, when
	 * the operation returns no result.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1210
	 */
	public function testRawOptionSettingWithNoResultResource() {
		$expected = [];
		$result = Galleries::connection()->read('SET @TEST = 1;');
		$this->assertEqual($expected, $result);
	}
}

?>