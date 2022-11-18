<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2013, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\data\source\database\adapter;

use ReflectionMethod;
use lithium\core\Libraries;
use lithium\data\Connections;
use lithium\data\Schema;
use lithium\data\model\Query;
use lithium\data\source\database\adapter\Sqlite3;
use lithium\tests\fixture\model\gallery\Galleries;
use lithium\tests\mocks\data\source\database\adapter\MockSqlite3;

class Sqlite3Test extends \lithium\tests\integration\data\Base {

	protected $_schema = ['fields' => [
		'id' => ['type' => 'id'],
		'name' => ['type' => 'string', 'length' => 255],
		'active' => ['type' => 'boolean'],
		'created' => ['type' => 'datetime', 'null' => true],
		'modified' => ['type' => 'datetime', 'null' => true]
	]];

	/**
	 * Skip the test if a Sqlite3 adapter configuration is unavailable.
	 */
	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(['Sqlite3']));
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
			$this->assertTrue(Sqlite3::enabled($feature));
		}

		foreach ($notSupported as $feature) {
			$this->assertFalse(Sqlite3::enabled($feature));
		}

		$this->assertNull(Sqlite3::enabled('unexisting'));
	}

	/**
	 * Tests that the object is initialized with the correct default values.
	 */
	public function testConstructorDefaults() {
		$db = new MockSqlite3(['autoConnect' => false, AUTO_INIT_CLASS => false]);
		$result = $db->get('_config');
		$expected = [
			'autoConnect' => false,
			'database' => ':memory:',
			'encoding' => null,
			'persistent' => true,
			'host' => 'localhost',
			'login' => 'root',
			'password' => '',
			'dsn' => null,
			'options' => [],
			'init' => false
		];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that this adapter can connect to the database, and that the status is properly
	 * persisted.
	 */
	public function testDatabaseConnection() {
		$db = new Sqlite3(['autoConnect' => false] + $this->_dbConfig);
		$this->assertTrue($db->connect());
		$this->assertTrue($db->isConnected());

		$this->assertTrue($db->disconnect());
		$this->assertFalse($db->isConnected());
	}

	public function testDatabaseEncoding() {
		$this->assertTrue($this->_db->isConnected());

		$this->assertTrue($this->_db->encoding('UTF8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('utf8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('UTF-8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());

		$this->assertTrue($this->_db->encoding('utf-8'));
		$this->assertEqual('UTF-8', $this->_db->encoding());
	}

	public function testValueByIntrospect() {
		$expected = "'string'";
		$result = $this->_db->value("string");
		$this->assertInternalType('string', $result);
		$this->assertEqual($expected, $result);

		$expected = "'''this string is escaped'''";
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
		$expected = '"title"';
		$this->assertEqual($expected, $result);
	}

	public function testColumnAbstraction() {
		$method = new ReflectionMethod($this->_db, '_column');
		$method->setAccessible(true);

		$result = $method->invokeArgs($this->_db, ['varchar']);
		$this->assertEqual(['type' => 'string', 'length' => 255], $result);

		$result = $method->invokeArgs($this->_db, ['tinyint(1)']);
		$this->assertEqual(['type' => 'boolean'], $result);

		$result = $method->invokeArgs($this->_db, ['varchar(255)']);
		$this->assertEqual(['type' => 'string', 'length' => 255], $result);

		$result = $method->invokeArgs($this->_db, ['text']);
		$this->assertEqual(['type' => 'text'], $result);

		$result = $method->invokeArgs($this->_db, ['text']);
		$this->assertEqual(['type' => 'text'], $result);

		$result = $method->invokeArgs($this->_db, ['decimal(12,2)']);
		$this->assertEqual(['type' => 'float', 'length' => 12, 'precision' => 2], $result);

		$result = $method->invokeArgs($this->_db, ['int(11)']);
		$this->assertEqual(['type' => 'integer', 'length' => 11], $result);
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

	/**
	 * Ensures that DELETE queries are not generated with table aliases, as Sqlite3 does not
	 * support this.
	 */
	public function testDeletesWithoutAliases() {
		$delete = new Query(['type' => 'delete', 'source' => 'galleries']);
		$this->assertTrue($this->_db->delete($delete));
	}

	/**
	 * Tests that describing a table's schema returns the correct column meta-information.
	 */
	public function testDescribe() {
		$result = $this->_db->describe('galleries');
		$expected = [
			'id' => ['type' => 'integer', 'null' => false, 'default' => null],
			'name' => [
				'type' => 'string', 'length' => 255, 'null' => false, 'default' => null
			],
			'active' => [
				'type' => 'boolean', 'null' => false, 'default' => null
			],
			'created' => [
				'type' => 'text', 'null' => false, 'default' => null
			],
			'modified' => [
				'type' => 'text', 'null' => false, 'default' => null
			]
		];
		$this->assertEqual($expected, $result->fields());

		unset($expected['name']);
		unset($expected['modified']);
		$result = $this->_db->describe('galleries', $expected)->fields();
		$this->assertEqual($expected, $result);
	}

	public function testResultSetInMemory() {
		$connection = 'sqlite_memory';
		Connections::add($connection, [
			'type' => 'database',
			'adapter' => 'Sqlite3',
			'database' => ':memory:',
			'encoding' => 'UTF-8'
		]);
		$this->_testResultSet($connection);
	}

	public function testResultSetInFile() {
		$connection = 'sqlite_file';
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "Path `{$base}` is not writable.");
		$filename = tempnam($base, "sqlite");
		Connections::add($connection, [
			'type' => 'database',
			'adapter' => 'Sqlite3',
			'database' => "{$filename}.sq3",
			'encoding' => 'UTF-8'
		]);
		$this->_testResultSet($connection);
		$this->_cleanUp();
	}

	protected function _testResultSet($connection) {
		$db = Connections::get($connection);
		$db->dropSchema('galleries');
		$schema = new Schema($this->_schema);
		$db->createSchema('galleries', $schema);

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

		$db->dropSchema('galleries');
	}

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
			],
			'modified' => [
				'type' => 'timestamp', 'null' => false, 'default' => (object) 'CURRENT_TIMESTAMP'
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
		$this->assertPattern('$\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$', $result['modified']);

		$time = time();
		$this->assertTrue($time - strtotime($result['created']) < 24 * 3600);
		$this->assertTrue($time - strtotime($result['modified']) < 24 * 3600);
	}
}

?>