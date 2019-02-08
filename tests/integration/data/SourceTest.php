<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Images;
use lithium\tests\fixture\model\gallery\Galleries;
use li3_fixtures\test\Fixtures;
use Exception;

class SourceTest extends \lithium\tests\integration\data\Base {

	protected $_fixtures = [
		'images' => 'lithium\tests\fixture\model\gallery\ImagesFixture',
		'galleries' => 'lithium\tests\fixture\model\gallery\GalleriesFixture',
	];

	protected $_classes = [
		'images' => 'lithium\tests\fixture\model\gallery\Images',
		'galleries' => 'lithium\tests\fixture\model\gallery\Galleries'
	];

	public $galleriesData = [
		['name' => 'StuffMart', 'active' => true],
		['name' => 'Ma \'n Pa\'s Data Warehousing & Bait Shop', 'active' => false]
	];

	/**
	 * Skip the test if no test database connection available.
	 */
	public function skip() {
		parent::connect($this->_connection);
		if (!class_exists('li3_fixtures\test\Fixtures')) {
			$this->skipIf(true, 'Need `li3_fixtures` to run tests.');
		}
	}

	/**
	 * Creating the test database
	 */
	public function setUp() {
		Fixtures::config([
			'db' => [
				'adapter' => 'Connection',
				'connection' => $this->_connection,
				'fixtures' => $this->_fixtures
			]
		]);
		Fixtures::create('db');
	}

	/**
	 * Dropping the test database
	 */
	public function tearDown() {
		Fixtures::clear('db');
		Galleries::reset();
	}

	/**
	 * Tests that a single record with a manually specified primary key can be created, persisted
	 * to an arbitrary data store, re-read and updated.
	 */
	public function testSingleReadWriteWithKey() {
		$key = Galleries::meta('key');
		$new = Galleries::create([$key => 12345, 'name' => 'Acme, Inc.']);

		$result = $new->data();
		$expected = [$key => 12345, 'name' => 'Acme, Inc.'];
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertEqual($expected['name'], $result['name']);

		$this->assertFalse($new->exists());
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());

		$existing = Galleries::find(12345);
		$result = $existing->data();
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertEqual($expected['name'], $result['name']);
		$this->assertTrue($existing->exists());

		$existing->name = 'Big Brother and the Holding Galleries';
		$result = $existing->save();
		$this->assertTrue($result);

		$existing = Galleries::find(12345);
		$result = $existing->data();
		$expected['name'] = 'Big Brother and the Holding Galleries';
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertEqual($expected['name'], $result['name']);

		$this->assertTrue($existing->delete());
	}

	public function testRewind() {
		$key = Galleries::meta('key');
		$new = Galleries::create([$key => 12345, 'name' => 'Acme, Inc.']);

		$result = $new->data();
		$this->assertNotEmpty($result);
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());

		$result = Galleries::all(12345);
		$this->assertNotNull($result);

		$result = $result->rewind();
		$this->assertNotNull($result);
		$this->assertInstanceOf('lithium\data\Entity', $result);
	}

	public function testFindFirstWithFieldsOption() {
		return;
		$key = Galleries::meta('key');
		$new = Galleries::create([$key => 1111, 'name' => 'Test find first with fields.']);
		$result = $new->data();

		$expected = [$key => 1111, 'name' => 'Test find first with fields.'];
		$this->assertEqual($expected['name'], $result['name']);
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertFalse($new->exists());
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());

		$result = Galleries::find('first', ['fields' => ['name']]);
		$this->assertNotInternalType('null', $result);

		$this->skipIf($result === null, 'No result returned to test');
		$result = $result->data();
		$this->assertEqual($expected['name'], $result['name']);

		$this->assertTrue($new->delete());
	}

	public function testReadWriteMultiple() {
		$this->skipIf($this->with(['CouchDb']));
		$galleries = [];
		$key = Galleries::meta('key');

		foreach ($this->galleriesData as $data) {
			$galleries[] = Galleries::create($data);
			$this->assertTrue(end($galleries)->save());
			$this->assertNotEmpty(end($galleries)->$key);
		}

		$this->assertIdentical(2, Galleries::count());
		$this->assertIdentical(1, Galleries::count(['active' => true]));
		$this->assertIdentical(1, Galleries::count(['active' => false]));
		$this->assertIdentical(0, Galleries::count(['active' => null]));
		$all = Galleries::all();
		$this->assertIdentical(2, Galleries::count());

		$expected = count($this->galleriesData);
		$this->assertEqual($expected, $all->count());
		$this->assertEqual($expected, count($all));

		$id = (string) $all->first()->{$key};
		$this->assertTrue(strlen($id) > 0);
		$this->assertNotEmpty($all->data());

		foreach ($galleries as $galleries) {
			$this->assertTrue($galleries->delete());
		}
		$this->assertIdentical(0, Galleries::count());
	}

	public function testEntityFields() {
		foreach ($this->galleriesData as $data) {
			Galleries::create($data)->save();
		}
		$all = Galleries::all();

		$result = $all->first(function($doc) { return $doc->name === 'StuffMart'; });
		$this->assertEqual('StuffMart', $result->name);

		$result = $result->data();
		$this->assertEqual('StuffMart', $result['name']);

		$result = $all->next();
		$this->assertEqual('Ma \'n Pa\'s Data Warehousing & Bait Shop', $result->name);

		$result = $result->data();
		$this->assertEqual('Ma \'n Pa\'s Data Warehousing & Bait Shop', $result['name']);

		$this->assertFalse($all->next());
	}

	/**
	 * Tests that a record can be created, saved, and subsequently re-read using a key
	 * auto-generated by the data source. Uses short-hand `find()` syntax which does not support
	 * compound keys.
	 */
	public function testGetRecordByGeneratedId() {
		$key = Galleries::meta('key');
		$galleries = Galleries::create(['name' => 'Test Galleries']);
		$this->assertTrue($galleries->save());

		$id = (string) $galleries->{$key};
		$galleriesCopy = Galleries::find($id)->data();
		$data = $galleries->data();

		foreach ($data as $key => $value) {
			$this->assertTrue(isset($galleriesCopy[$key]));
			$this->assertEqual($data[$key], $galleriesCopy[$key]);
		}
	}

	/**
	 * Tests the default relationship information provided by the backend data source.
	 */
	public function testDefaultRelationshipInfo() {
		$db = $this->_db;
		$this->skipIf(!$db::enabled('relationships'));
		$this->assertEqual(['Images'], array_keys(Galleries::relations()));
		$this->assertEqual([
			'Galleries', 'ImagesTags', 'Comments'
		], array_keys(Images::relations()));

		$this->assertEqual(['Images'], Galleries::relations('hasMany'));
		$this->assertEqual(['Galleries'], Images::relations('belongsTo'));

		$this->assertEmpty(Galleries::relations('belongsTo'));
		$this->assertEmpty(Galleries::relations('hasOne'));

		$this->assertEqual(['ImagesTags', 'Comments'], Images::relations('hasMany'));
		$this->assertEmpty(Images::relations('hasOne'));

		$result = Galleries::relations('Images');

		$this->assertEqual('hasMany', $result->data('type'));
		$this->assertEqual($this->_classes['images'], $result->data('to'));
	}

	public function testAbstractTypeHandling() {
		$key = Galleries::meta('key');

		foreach ($this->galleriesData as $data) {
			$galleries[] = Galleries::create($data);
			$this->assertTrue(end($galleries)->save());
			$this->assertNotEmpty(end($galleries)->{$key});
		}

		foreach (Galleries::all() as $galleries) {
			$this->assertTrue($galleries->delete());
		}
	}

	public function testSerializingEntity() {
		Fixtures::save('db');

		$data = Images::find('first');
		$this->skipIf(!$data, 'Fixtures not applied/available.');

		$result = true;
		try {
			$data = serialize($data);
			$data = unserialize($data);
		} catch (Exception $e) {
			$result = false;
			$data = [];
		}
		$this->assertTrue($result);

		$expected = 'Amiga 1200';
		$result = $data->title;
		$this->assertEqual($expected, $result);
	}

	public function testSerializingCollection() {
		Fixtures::save('db');

		$data = Images::find('all');
		$this->skipIf(!$data, 'Fixtures not applied/available.');

		$result = true;
		try {
			$data = serialize($data);
			$data = unserialize($data);
		} catch (Exception $e) {
			$result = false;
			$data = [];
		}
		$this->assertTrue($result);

		$expected = 'Amiga 1200';
		foreach ($data as $item) {
			$result = $item->title;
			break;
		}
		$this->assertEqual($expected, $result);
	}
}

?>