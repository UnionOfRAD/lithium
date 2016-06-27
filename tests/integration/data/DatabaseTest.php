<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\core\Libraries;
use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\tests\fixture\model\gallery\Images;
use lithium\tests\fixture\model\gallery\Galleries;
use lithium\util\String;
use li3_fixtures\test\Fixtures;
use lithium\data\Schema;

class DatabaseTest extends \lithium\tests\integration\data\Base {

	protected $_export = null;

	protected $_fixtures = array(
		'images' => 'lithium\tests\fixture\model\gallery\ImagesFixture',
		'galleries' => 'lithium\tests\fixture\model\gallery\GalleriesFixture',
	);

	/**
	 * Skip the test if no allowed database connection available.
	 */
	public function skip() {
		parent::connect($this->_connection);
		if (!class_exists('li3_fixtures\test\Fixtures')) {
			$this->skipIf(true, 'Need `li3_fixtures` to run tests.');
		}
		$this->skipIf(!$this->with(array('MySql', 'PostgreSql', 'Sqlite3')));
		$this->_export = Libraries::path('lithium\tests\fixture\model\gallery\export', array(
			'dirs' => true
		));
	}

	/**
	 * Creating the test database
	 */
	public function setUp() {
		$options = array(
			'db' => array(
				'adapter' => 'Connection',
				'connection' => $this->_connection,
				'fixtures' => $this->_fixtures
			),
			'db_alternative' => array(
				'adapter' => 'Connection',
				'connection' => $this->_connection . '_alternative',
				'fixtures' => $this->_fixtures
			)
		);

		if ($this->with('PostgreSql')) {
			foreach ($options as $key => &$value) {
				$value['alters']['change']['id'] = array(
					'value' => function ($id) {
						return (object) 'default';
					}
				);
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
		$config['database'] = null;
		$config['object'] = null;
		$connection = 'no_database';
		Connections::add($connection, $config);

		$this->assertException("/No Database configured/", function() use ($connection) {
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
		$gallery = Galleries::create(array('name' => 'New Gallery'));
		$this->assertTrue($gallery->save());
		$this->assertNotEmpty($gallery->id);
		$this->assertTrue(Galleries::count() === 3);

		$img = Images::create(array(
			'image' => 'newimage.png',
			'title' => 'New Image',
			'gallery_id' => $gallery->id
		));
		$this->assertEqual(true, $img->save());

		$img = Images::find($img->id);
		$this->assertEqual($gallery->id, $img->gallery_id);
	}

	public function testManyToOne() {
		$opts = array('conditions' => array('gallery_id' => 1));

		$query = new Query($opts + array(
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\gallery\Images',
			'source' => 'images',
			'alias' => 'Images',
			'with' => array('Galleries')
		));
		$images = $this->_db->read($query)->data();
		$expected = include $this->_export . '/testManyToOne.php';
		$this->assertEqual($expected, $images);

		$images = Images::find('all', $opts + array('with' => 'Galleries'))->data();
		$this->assertEqual($expected, $images);
	}

	public function testOneToMany() {
		$opts = array('conditions' => array('Galleries.id' => 1));

		$query = new Query($opts + array(
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\gallery\Galleries',
			'source' => 'galleries',
			'alias' => 'Galleries',
			'with' => array('Images')
		));
		$galleries = $this->_db->read($query)->data();
		$expected = include $this->_export . '/testOneToMany.php';
		$gallery = Galleries::find('first', $opts + array('with' => 'Images'))->data();

		$this->assertEqual(3, count($gallery['images']));
		$this->assertEqual(reset($expected), $gallery);
	}

	public function testOneToManyUsingSameKeyName() {
		Fixtures::drop('db', array('galleries'));
		$fixture = Fixtures::get('db', 'galleries');
		$fixture->alter('change', 'id', array(
			'to' => 'gallery_id'
		));
		Fixtures::save('db', array('galleries'));

		Galleries::reset();
		Galleries::config(array('meta' => array(
			'connection' => $this->_connection, 'key' => 'gallery_id'
		)));

		$opts = array('conditions' => array('Galleries.gallery_id' => 1));

		$query = new Query($opts + array(
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\gallery\Galleries',
			'source' => 'galleries',
			'alias' => 'Galleries',
			'with' => array('Images')
		));
		$galleries = $this->_db->read($query);
		$this->assertCount(3, $galleries->first()->images);
	}

	public function testUpdate() {
		$options = array('conditions' => array('id' => 1));
		$uuid = String::uuid();
		$image = Images::find('first', $options);
		$image->title = $uuid;
		$firstID = $image->id;
		$image->save();
		$this->assertEqual($uuid, Images::find('first', $options)->title);

		$uuid = String::uuid();
		Images::update(array('title' => $uuid), array('id' => $firstID));
		$this->assertEqual($uuid, Images::find('first', $options)->title);
		$this->images[0]['title'] = $uuid;
	}

	public function testFields() {
		$fields = array('id', 'image');
		$image = Images::find('first', array(
			'fields' => $fields,
			'conditions' => array(
				'gallery_id' => 1
			)
		));
		$this->assertEqual($fields, array_keys($image->data()));
	}

	public function testOrder() {
		$images = Images::find('all', array(
			'order' => 'id DESC',
			'conditions' => array(
				'gallery_id' => 1
			)
		));

		$this->assertCount(3, $images);
		$id = $images->first()->id;
		foreach ($images as $image) {
			$this->assertTrue($id >= $image->id);
		}
	}

	public function testOrderWithRelationAndLimit() {
		$galleries = Galleries::first(array(
			'with' => array('Images'),
			'order' => 'name',
		));
		$this->assertNotEmpty($galleries);
	}

	public function testOrderWithHasManyThrowsExceptionIfNonSequential() {
		$this->assertException('/^Associated records hydrated out of order.*/', function() {
			Galleries::find('all', array(
				'order' => array('Images.title' => 'DESC'),
				'with' => 'Images'
			))->to('array');
		});
	}

	public function testOrderWithHasManyWorksIfOrderByMainIdFirst() {
		$expected = include $this->_export . '/testHasManyWithOrder.php';

		$galleries = Galleries::find('all', array(
			'order' => array('id', 'Images.title' => 'DESC'),
			'with' => 'Images'
		));

		$this->assertCount(2, $galleries);
		$this->assertEqual($expected, $galleries->to('array'));

		$galleries = Galleries::find('all', array(
			'order' => array(
				'name' => 'DESC',
				'Images.title' => 'DESC'
			),
			'with' => 'Images'
		));

		$this->assertCount(2, $galleries);
		$this->assertEqual(array_reverse($expected, true), $galleries->to('array'));
	}

	public function testGroup() {
		$field = $this->_db->name('Images.id');
		$galleries = Galleries::find('all', array(
			'fields' => array(array("count($field) AS count")),
			'with' => 'Images',
			'group' => array('Galleries.id'),
			'order' => array('Galleries.id' => 'ASC')
		));

		$this->assertCount(2, $galleries);
		$expected = array(3, 2);

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

		$connectionConfig1 = Connections::get($connection1, array('config' => true));
		$connectionConfig2 = Connections::get($connection2, array('config' => true));

		parent::connect($connection2);
		$this->skipIf(!$connectionConfig2, "The `'{$connection2}' connection is not available`.");
		$this->skipIf(!$this->with(array('MySql', 'PostgreSql', 'Sqlite3')));

		$bothInMemory = $connectionConfig1['database'] == ':memory:';
		$bothInMemory = $bothInMemory && $connectionConfig2['database'] == ':memory:';
		$this->skipIf($bothInMemory, 'Cannot use two connections with in memory databases');

		Galleries::config(array('meta' => array('connection' => $connection1)));

		$galleriesCountOriginal = Galleries::find('count');

		$gallery = Galleries::create(array('name' => 'record_in_db'));
		$gallery->save();

		Fixtures::save('db_alternative');

		Galleries::config(array('meta' => array('connection' => $connection2)));

		$expected = $galleriesCountOriginal;
		$result = Galleries::find('count');
		$this->assertEqual($expected, $result);

		Galleries::config(array('meta' => array('connection' => $connection1)));

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

		$connectionConfig1 = Connections::get($connection1, array('config' => true));
		$connectionConfig2 = Connections::get($connection2, array('config' => true));

		parent::connect($connection2);
		$this->skipIf(!$connectionConfig2, "The `'{$connection2}' connection is not available`.");
		$this->skipIf(!$this->with(array('MySql', 'PostgreSql', 'Sqlite3')));

		$bothInMemory = $connectionConfig1['database'] == ':memory:';
		$bothInMemory = $bothInMemory && $connectionConfig2['database'] == ':memory:';
		$this->skipIf($bothInMemory, 'Cannot use two connections with in memory databases');

		Fixtures::save('db_alternative');

		Galleries::config(array('meta' => array('connection' => $connection1)));
		Images::config(array('meta' => array('connection' => $connection1)));

		$galleriesCountOriginal = Galleries::find('count');
		$imagesCountOriginal = Images::find('count');

		$gallery = Galleries::create(array('name' => 'record_in_db'));
		$gallery->save();

		$image = Images::find('first', array('conditions' => array('id' => 1)));
		$image->delete();

		Galleries::config(array('meta' => array('connection' => $connection2)));

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
		Galleries::config(array(
			'schema' => new Schema(array(
				'fields' => array(
					'id' => array('type' => 'id'),
					'name' => array('type' => 'string', 'length' => 50),
					'active' => array('type' => 'boolean', 'default' => true),
					'created' => array('type' => 'datetime'),
					'modified' => array('type' => 'datetime')
				)
			))
		));
		$results = Galleries::find('all', array(
			'conditions' => array(
				'name' => 'Foo Gallery'
			),
			'order' => array('id' => 'DESC')
		));
		$this->assertEqual(1, $results->count());
	}

	/**
	 * Tests if DISTINCT queries work as expected and do not
	 * duplicate records.
	 *
	 * @link https://github.com/UnionOfRAD/lithium/issues/1175
	 */
	public function testDistinctResultsInNoDuplicates() {
		Galleries::create(array('name' => 'A'))->save();
		Galleries::create(array('name' => 'B'))->save();
		Galleries::create(array('name' => 'C'))->save();
		Galleries::create(array('name' => 'D'))->save();
		Galleries::create(array('name' => 'A'))->save();
		Galleries::create(array('name' => 'A'))->save();
		Galleries::create(array('name' => 'A'))->save();
		Galleries::create(array('name' => 'B'))->save();
		Galleries::create(array('name' => 'C'))->save();
		Galleries::create(array('name' => 'D'))->save();

		$results = Galleries::find('all', array(
			'fields' => array(
				'DISTINCT name as d__name'
			)
		));
		$names = array();
		foreach ($results as $result) {
			$this->assertNotContains($result->d__name, $names);
			$names[] = $result->d__name;
		}

		$results = Galleries::find('all', array(
			'fields' => array(
				'DISTINCT id AS d__id',
				'name'
			)
		));
		$ids = array();
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
		$results = Galleries::find('all', array(
			'fields' => array(
				'name',
				'(SELECT 23) AS number'
			)
		));
		$this->assertEqual(2, $results->count());
	}
}

?>