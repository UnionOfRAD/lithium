<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\data;

use lithium\core\Libraries;
use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\tests\fixture\model\gallery\Images;
use lithium\tests\fixture\model\gallery\Galleries;
use lithium\util\Text;
use li3_fixtures\test\Fixtures;
use lithium\data\Schema;

class DatabaseTest extends \lithium\tests\integration\data\Base {

	protected $_export = null;

	protected $_fixtures = [
		'images' => 'lithium\tests\fixture\model\gallery\ImagesFixture',
		'galleries' => 'lithium\tests\fixture\model\gallery\GalleriesFixture',
	];

	/**
	 * Skip the test if no allowed database connection available.
	 */
	public function skip() {
		parent::connect($this->_connection);
		if (!class_exists('li3_fixtures\test\Fixtures')) {
			$this->skipIf(true, 'Need `li3_fixtures` to run tests.');
		}
		$this->skipIf(!$this->with(['MySql', 'PostgreSql', 'Sqlite3']));
		$this->_export = Libraries::path('lithium\tests\fixture\model\gallery\export', [
			'dirs' => true
		]);
	}

	/**
	 * Creating the test database
	 */
	public function setUp() {
		$options = [
			'db' => [
				'adapter' => 'Connection',
				'connection' => $this->_connection,
				'fixtures' => $this->_fixtures
			],
			'db_alternative' => [
				'adapter' => 'Connection',
				'connection' => $this->_connection . '_alternative',
				'fixtures' => $this->_fixtures
			]
		];

		if ($this->with('PostgreSql')) {
			foreach ($options as $key => &$value) {
				$value['alters']['change']['id'] = [
					'value' => function ($id) {
						return (object) 'default';
					}
				];
			}
		}

		Fixtures::config($options);
		Fixtures::save('db');
	}

	/**
	 * Dropping the test database
	 */
	public function tearDown() {
		Fixtures::clear('db');
		Galleries::reset();
		Images::reset();
	}

	public function testConnectWithNoDatabase() {
		$config = $this->_dbConfig;
		$config['autoConnect'] = false;
		$config['database'] = null;
		$config['object'] = null;
		$connection = 'no_database';
		Connections::add($connection, $config);
		$this->assertException("/No database configured/", function() use ($connection) {
			Connections::get($connection)->connect();
		});
	}

	public function testConnectWithWrongHost() {
		$this->skipIf(!$this->with('PostgreSql'));
		$config = $this->_dbConfig;
		$config['host'] = 'unknown.host.nowhere';
		$config['object'] = null;
		$connection = 'wrong_host';
		Connections::add($connection, $config);

		$this->assertException('/.*/', function() use ($connection) {
			Connections::get($connection)->connect();
		});
	}

	public function testConnectWithWrongPassword() {
		$this->skipIf(!$this->with('PostgreSql'));
		$config = $this->_dbConfig;
		$config['login'] = 'wrong_login';
		$config['password'] = 'wrong_pass';
		$config['object'] = null;
		$connection = 'wrong_passord';
		Connections::add($connection, $config);

		$this->assertException('/.*/', function() use ($connection) {
			Connections::get($connection)->connect();
		});
	}

	public function testExecuteException() {
		$db = $this->_db;

		$this->assertException("/error/", function() use ($db) {
			$db->read('SELECT * FROM * FROM table');
		});
	}

	public function testCreateData() {
		$gallery = Galleries::create(['name' => 'New Gallery']);
		$this->assertTrue($gallery->save());
		$this->assertNotEmpty($gallery->id);
		$this->assertTrue(Galleries::count() === 3);

		$img = Images::create([
			'image' => 'newimage.png',
			'title' => 'New Image',
			'gallery_id' => $gallery->id
		]);
		$this->assertEqual(true, $img->save());

		$img = Images::find($img->id);
		$this->assertEqual($gallery->id, $img->gallery_id);
	}

	public function testManyToOne() {
		$opts = ['conditions' => ['gallery_id' => 1]];

		$query = new Query($opts + [
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\gallery\Images',
			'source' => 'images',
			'alias' => 'Images',
			'with' => ['Galleries']
		]);
		$images = $this->_db->read($query)->data();
		$expected = include $this->_export . '/testManyToOne.php';
		$this->assertEqual($expected, $images);

		$images = Images::find('all', $opts + ['with' => 'Galleries'])->data();
		$this->assertEqual($expected, $images);
	}

	public function testOneToMany() {
		$opts = ['conditions' => ['Galleries.id' => 1]];

		$query = new Query($opts + [
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\gallery\Galleries',
			'source' => 'galleries',
			'alias' => 'Galleries',
			'with' => ['Images']
		]);
		$galleries = $this->_db->read($query)->data();
		$expected = include $this->_export . '/testOneToMany.php';
		$gallery = Galleries::find('first', $opts + ['with' => 'Images'])->data();

		$this->assertEqual(3, count($gallery['images']));
		$this->assertEqual(reset($expected), $gallery);
	}

	public function testOneToManyUsingSameKeyName() {
		Fixtures::drop('db', ['galleries']);
		$fixture = Fixtures::get('db', 'galleries');
		$fixture->alter('change', 'id', [
			'to' => 'gallery_id'
		]);
		Fixtures::save('db', ['galleries']);

		Galleries::reset();
		Galleries::config(['meta' => [
			'connection' => $this->_connection, 'key' => 'gallery_id'
		]]);

		$opts = ['conditions' => ['Galleries.gallery_id' => 1]];

		$query = new Query($opts + [
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\gallery\Galleries',
			'source' => 'galleries',
			'alias' => 'Galleries',
			'with' => ['Images']
		]);
		$galleries = $this->_db->read($query);
		$this->assertCount(3, $galleries->first()->images);
	}

	public function testUpdate() {
		$options = ['conditions' => ['id' => 1]];
		$uuid = Text::uuid();
		$image = Images::find('first', $options);
		$image->title = $uuid;
		$firstID = $image->id;
		$image->save();
		$this->assertEqual($uuid, Images::find('first', $options)->title);

		$uuid = Text::uuid();
		Images::update(['title' => $uuid], ['id' => $firstID]);
		$this->assertEqual($uuid, Images::find('first', $options)->title);
		$this->images[0]['title'] = $uuid;
	}

	public function testFields() {
		$fields = ['id', 'image'];
		$image = Images::find('first', [
			'fields' => $fields,
			'conditions' => [
				'gallery_id' => 1
			]
		]);
		$this->assertEqual($fields, array_keys($image->data()));
	}

	public function testOrder() {
		$images = Images::find('all', [
			'order' => 'id DESC',
			'conditions' => [
				'gallery_id' => 1
			]
		]);

		$this->assertCount(3, $images);
		$id = $images->first()->id;
		foreach ($images as $image) {
			$this->assertTrue($id >= $image->id);
		}
	}

	public function testOrderWithRelationAndLimit() {
		$galleries = Galleries::first([
			'with' => ['Images'],
			'order' => 'name',
		]);
		$this->assertNotEmpty($galleries);
	}

	public function testOrderWithHasManyThrowsExceptionIfNonSequential() {
		$this->assertException('/^Associated records hydrated out of order.*/', function() {
			Galleries::find('all', [
				'order' => ['Images.title' => 'DESC'],
				'with' => 'Images'
			])->to('array');
		});
	}

	public function testOrderWithHasManyWorksIfOrderByMainIdFirst() {
		$expected = include $this->_export . '/testHasManyWithOrder.php';

		$galleries = Galleries::find('all', [
			'order' => ['id', 'Images.title' => 'DESC'],
			'with' => 'Images'
		]);

		$this->assertCount(2, $galleries);
		$this->assertEqual($expected, $galleries->to('array'));

		$galleries = Galleries::find('all', [
			'order' => [
				'name' => 'DESC',
				'Images.title' => 'DESC'
			],
			'with' => 'Images'
		]);

		$this->assertCount(2, $galleries);
		$this->assertEqual(array_reverse($expected, true), $galleries->to('array'));
	}

	public function testGroup() {
		$field = $this->_db->name('Images.id');
		$galleries = Galleries::find('all', [
			'fields' => [["count($field) AS count"]],
			'with' => 'Images',
			'group' => ['Galleries.id'],
			'order' => ['Galleries.id' => 'ASC']
		]);

		$this->assertCount(2, $galleries);
		$expected = [3, 2];

		foreach ($galleries as $gallery) {
			$this->assertEqual(current($expected), $gallery->count);
			next($expected);
		}
	}

	public function testRemove() {
		$this->assertTrue(Galleries::remove());
		$this->assertTrue(Images::remove());
	}

	/**
	 * Prove that one model's connection can be switched while
	 * keeping on working upon the correct databases.
	 */
	public function testSwitchingDatabaseOnModel() {
		$connection1 = $this->_connection;
		$connection2 = $this->_connection . '_alternative';

		$connectionConfig1 = Connections::get($connection1, ['config' => true]);
		$connectionConfig2 = Connections::get($connection2, ['config' => true]);

		parent::connect($connection2);
		$this->skipIf(!$connectionConfig2, "The `'{$connection2}' connection is not available`.");
		$this->skipIf(!$this->with(['MySql', 'PostgreSql', 'Sqlite3']));

		$bothInMemory = $connectionConfig1['database'] == ':memory:';
		$bothInMemory = $bothInMemory && $connectionConfig2['database'] == ':memory:';
		$this->skipIf($bothInMemory, 'Cannot use two connections with in memory databases');

		Galleries::config(['meta' => ['connection' => $connection1]]);

		$galleriesCountOriginal = Galleries::find('count');

		$gallery = Galleries::create(['name' => 'record_in_db']);
		$gallery->save();

		Fixtures::save('db_alternative');

		Galleries::config(['meta' => ['connection' => $connection2]]);

		$expected = $galleriesCountOriginal;
		$result = Galleries::find('count');
		$this->assertEqual($expected, $result);

		Galleries::config(['meta' => ['connection' => $connection1]]);

		$expected = $galleriesCountOriginal + 1;
		$result = Galleries::find('count');
		$this->assertEqual($expected, $result);

		Fixtures::clear('db_alternative');
	}

	/**
	 * Prove that two distinct models each having a different connection to a different
	 * database are working independently upon the correct databases.
	 */
	public function testSwitchingDatabaseDistinctModels() {
		$connection1 = $this->_connection;
		$connection2 = $this->_connection . '_alternative';

		$connectionConfig1 = Connections::get($connection1, ['config' => true]);
		$connectionConfig2 = Connections::get($connection2, ['config' => true]);

		parent::connect($connection2);
		$this->skipIf(!$connectionConfig2, "The `'{$connection2}' connection is not available`.");
		$this->skipIf(!$this->with(['MySql', 'PostgreSql', 'Sqlite3']));

		$bothInMemory = $connectionConfig1['database'] == ':memory:';
		$bothInMemory = $bothInMemory && $connectionConfig2['database'] == ':memory:';
		$this->skipIf($bothInMemory, 'Cannot use two connections with in memory databases');

		Fixtures::save('db_alternative');

		Galleries::config(['meta' => ['connection' => $connection1]]);
		Images::config(['meta' => ['connection' => $connection1]]);

		$galleriesCountOriginal = Galleries::find('count');
		$imagesCountOriginal = Images::find('count');

		$gallery = Galleries::create(['name' => 'record_in_db']);
		$gallery->save();

		$image = Images::find('first', ['conditions' => ['id' => 1]]);
		$image->delete();

		Galleries::config(['meta' => ['connection' => $connection2]]);

		$expected = $galleriesCountOriginal;
		$result = Galleries::find('count');
		$this->assertEqual($expected, $result);

		$expected = $imagesCountOriginal - 1;
		$result = Images::find('count');
		$this->assertEqual($expected, $result);

		Fixtures::clear('db_alternative');
	}

	/**
	 * Tests if the `value()` and `_cast()` methods work correctly
	 * when a schema is hardcoded.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1003
	 */
	public function testValueWithHardcodedSchema() {
		Galleries::config([
			'schema' => new Schema([
				'fields' => [
					'id' => ['type' => 'id'],
					'name' => ['type' => 'string', 'length' => 50],
					'active' => ['type' => 'boolean', 'default' => true],
					'created' => ['type' => 'datetime'],
					'modified' => ['type' => 'datetime']
				]
			])
		]);
		$results = Galleries::find('all', [
			'conditions' => [
				'name' => 'Foo Gallery'
			],
			'order' => ['id' => 'DESC']
		]);
		$this->assertEqual(1, $results->count());
	}

	/**
	 * Tests if DISTINCT queries work as expected and do not
	 * duplicate records.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1175
	 */
	public function testDistinctResultsInNoDuplicates() {
		Galleries::create(['name' => 'A'])->save();
		Galleries::create(['name' => 'B'])->save();
		Galleries::create(['name' => 'C'])->save();
		Galleries::create(['name' => 'D'])->save();
		Galleries::create(['name' => 'A'])->save();
		Galleries::create(['name' => 'A'])->save();
		Galleries::create(['name' => 'A'])->save();
		Galleries::create(['name' => 'B'])->save();
		Galleries::create(['name' => 'C'])->save();
		Galleries::create(['name' => 'D'])->save();

		$results = Galleries::find('all', [
			'fields' => [
				'DISTINCT name as d__name'
			]
		]);
		$names = [];
		foreach ($results as $result) {
			$this->assertNotContains($result->d__name, $names);
			$names[] = $result->d__name;
		}

		$results = Galleries::find('all', [
			'fields' => [
				'DISTINCT id AS d__id',
				'name'
			]
		]);
		$ids = [];
		foreach ($results as $result) {
			$this->assertNotContains($result->d__id, $ids);
			$ids[] = $result->d__id;
		}
	}

	/**
	 * Tests that even when using a subquery the correct
	 * number of records is returned.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1209
	 */
	public function testSubqueryWithMultipleRecords() {
		$results = Galleries::find('all', [
			'fields' => [
				'name',
				'(SELECT 23) AS number'
			]
		]);
		$this->assertEqual(2, $results->count());
	}

	public function testUpdateWithMultiThread() {
		$thread1 = Images::first(1);
		$thread2 = Images::first(1);
		
		$thread1->image = 'tmp';
		$thread2->title = 'tmp';

		$thread2->save();
		$thread1->save();

		$this->assertEqual('tmp', Images::first(1)->title);
	}

	public function testUpdateWithoutFieldsChanged() {
		$image = Images::first(1);
		$title = $image->title;
		$image->title = $title;
		$this->assertTrue($image->save());
		
		$image = Images::first(1);
		$title = $image->title;
		$image->save(['title' => 'test'], [
			'whitelist' => ['image']
		]);
		$this->assertEqual($title, Images::first(1)->title);
	}

	public function testUpdateWithSomeFieldsChanged() {
		$image = Images::first(1);
		$image->save(['title' => 'foo']);
		$this->assertEqual('foo', Images::first(1)->title);
	}

	public function testUpdateWithNonExistFieldsChanged() {
		$image = Images::first(1);
		$image->save(['foo' => 'foo']);
		$this->assertNull(Images::first(1)->foo);
	}

	public function testUpdateWithRemoveFieldsViaWhitelist() {
		$image = Images::first(1);
		$image->save(['foo' => 'foo'], [
			'whitelist' => ['image']
		]);
		$this->assertNull(Images::first(1)->foo);

		$image = Images::first(1);
		$title = $image->title;
		$image->save(['title' => 'foo'], [
			'whitelist' => ['image']
		]);
		$this->assertEqual($title, Images::first(1)->title);

		$image = Images::first(1);
		$body = $image->body;
		$image->save(['title' => 'foo', 'body' => 'bar'], [
			'whitelist' => ['title']
		]);
		$this->assertEqual('foo', Images::first(1)->title);
		$this->assertEqual($body, Images::first(1)->body);

		$image = Images::first(1);
		$body = $image->body;
		$title = $image->title;
		$image->save(['title' => 'foo', 'body' => 'bar'], [
			'whitelist' => []
		]);
		$this->assertEqual($title, Images::first(1)->title);
		$this->assertEqual($body, Images::first(1)->body);
	}
}

?>