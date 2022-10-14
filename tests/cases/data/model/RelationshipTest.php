<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2013, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\model;

use lithium\util\Collection;
use lithium\data\Connections;
use lithium\data\model\Relationship;
use lithium\tests\mocks\data\model\MockDatabase;
use lithium\tests\mocks\data\model\MockGallery;
use lithium\tests\mocks\data\model\MockImage;

class RelationshipTest extends \lithium\test\Unit {

	protected $_db;
	protected $_gallery = 'lithium\tests\mocks\data\model\MockGallery';
	protected $_image = 'lithium\tests\mocks\data\model\MockImage';

	public function setUp() {
		$this->_db = new MockDatabase();
		Connections::add('mockconn', ['object' => $this->_db]);

		MockGallery::config(['meta' => ['connection' => 'mockconn']]);
		MockImage::config(['meta' => ['connection' => 'mockconn']]);
	}

	public function testDown() {
		Connections::remove('mockconn');
		MockGallery::reset();
		MockImage::reset();
	}

	public function testHasManyKey() {
		$config = [
			'from' => $this->_gallery,
			'to' => $this->_image,
			'type' => 'hasMany',
			'fieldName' => 'images',
		];
		$relation = new Relationship($config + [
			'key' => 'gallery_id'
		]);

		$expected = ['id' => 'gallery_id'];
		$this->assertEqual($expected, $relation->key());

		$relation = new Relationship($config + [
			'key' => ['id' => 'gallery_id']
		]);
		$this->assertEqual($expected, $relation->key());
	}

	public function testBelongsToKey() {
		$config = [
			'from' => $this->_gallery,
			'to' => $this->_image,
			'type' => 'belongsTo',
			'fieldName' => 'images',
		];
		$relation = new Relationship($config + [
			'key' => 'gallery_id'
		]);

		$expected = ['gallery_id' => 'id'];
		$this->assertEqual($expected, $relation->key());

		$relation = new Relationship($config + [
			'key' => ['gallery_id' => 'id']
		]);
		$this->assertEqual($expected, $relation->key());
	}

	public function testForeignKeysFromEntity() {
		$entity = MockGallery::create(['id' => 5]);
		$relation = MockGallery::relations('Image');
		$this->assertEqual(['gallery_id' => 5], $relation->foreignKey($entity));
	}

	public function testHasManyForeignKey() {
		$config = [
			'from' => $this->_gallery,
			'to' => $this->_image,
			'type' => 'hasMany',
			'fieldName' => 'images'
		];
		$relation = new Relationship($config + [
			'key' => 'gallery_id'
		]);

		$expected = ['gallery_id' => 5];
		$this->assertEqual($expected, $relation->foreignKey(['id' => 5]));

		$relation = new Relationship($config + [
			'key' => ['id' => 'gallery_id']
		]);
		$this->assertEqual($expected, $relation->foreignKey(['id' => 5]));
	}

	public function testBelongsToForeignKey() {
		$config = [
			'from' => $this->_image,
			'to' => $this->_gallery,
			'type' => 'belongsTo',
			'fieldName' => 'gallery'
		];
		$relation = new Relationship($config + [
			'key' => 'gallery_id'
		]);

		$expected = ['gallery_id' => 5];
		$this->assertEqual($expected, $relation->foreignKey(['id' => 5]));

		$relation = new Relationship($config + [
			'key' => ['gallery_id' => 'id']
		]);

		$this->assertEqual($expected, $relation->foreignKey(['id' => 5]));
	}

	public function testEmptyRequiredOptions() {
		$expected = "/`'type'`, `'fieldName'` and `'from'` options can't be empty./";
		$this->assertException($expected, function() {
			new Relationship();
		});
	}

	public function testEmptyToAndName() {
		$expected = "/`'to'` and `'name'` options can't both be empty./";
		$gallery = $this->_gallery;

		$this->assertException($expected, function() use ($gallery) {
			new Relationship([
				'from' => $gallery,
				'type' => 'belongsTo',
				'fieldName' => 'field_id'
			]);
		});
	}

	/**
	 * Tests that queries are correctly generated for LINK_KEY,
	 */
	public function testQueryGenerationWithLinkKey() {
		$relationship = new Relationship([
			'name' => 'Users',
			'type' => 'belongsTo',
			'link' => Relationship::LINK_KEY,
			'from' => 'my\models\Images',
			'to'   => 'my\models\Galleries',
			'key'  => ['gallery_id' => '_id'],
			'fieldName' => 'users'
		]);

		$this->assertNull($relationship->query((object) []));

		$expected = ['conditions' => ['_id' => 1], 'fields' => null];
		$this->assertEqual($expected, $relationship->query((object) [
			'gallery_id' => 1
		]));

		$id = (object) 1;
		$expected = ['conditions' => ['_id' => $id], 'fields' => null];
		$this->assertEqual($expected, $relationship->query((object) [
			'gallery_id' => $id
		]));
	}

	/**
	 * Tests that queries are correctly generated for LINK_KEY_LIST,
	 */
	public function testQueryGenerationWithLinkKeyList() {
		$relationship = new Relationship([
			'name' => 'Users',
			'type' => 'hasMany',
			'link' => Relationship::LINK_KEY_LIST,
			'from' => 'my\models\Groups',
			'to'   => 'my\models\Users',
			'key'  => ['users' => '_id'],
			'fieldName' => 'users'
		]);

		$this->assertNull($relationship->query((object) []));

		$keys = [1, 2, 3];
		$expected = ['conditions' => ['_id' => $keys], 'fields' => null];

		$this->assertEqual($expected, $relationship->query((object) [
			'users' => new Collection(['data' => $keys])
		]));
	}
}

?>