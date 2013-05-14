<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use lithium\data\model\Relationship;
use lithium\tests\mocks\data\model\MockGallery;

class RelationshipTest extends \lithium\test\Unit {

	protected $_gallery = 'lithium\tests\mocks\data\model\MockGallery';
	protected $_image = 'lithium\tests\mocks\data\model\MockImage';

	public function testRespondsTo() {
		$query = new Relationship(array(
			'type' => 'belongsTo',
			'fieldName' => 'bob',
			'to' => $this->_image
		));
		$this->assertTrue($query->respondsTo('foobarbaz'));
		$this->assertFalse($query->respondsTo(0));
	}

	public function testHasManyKey() {
		$config = array(
			'from' => $this->_gallery,
			'to' => $this->_image,
			'type' => 'hasMany',
			'fieldName' => 'images',
		);
		$relation = new Relationship($config + array(
			'key' => 'gallery_id'
		));

		$expected = array('id' => 'gallery_id');
		$this->assertEqual($expected, $relation->key());

		$relation = new Relationship($config + array(
			'key' => array('id' => 'gallery_id')
		));
		$this->assertEqual($expected, $relation->key());
	}

	public function testBelongsToKey() {
		$config = array(
			'from' => $this->_gallery,
			'to' => $this->_image,
			'type' => 'belongsTo',
			'fieldName' => 'images',
		);
		$relation = new Relationship($config + array(
			'key' => 'gallery_id'
		));

		$expected = array('gallery_id' => 'id');
		$this->assertEqual($expected, $relation->key());

		$relation = new Relationship($config + array(
			'key' => array('gallery_id' => 'id')
		));
		$this->assertEqual($expected, $relation->key());
	}

	public function testForeignKeysFromEntity() {
		$entity = MockGallery::create(array('id' => 5));
		$relation = MockGallery::relations('Image');
		$this->assertEqual(array('gallery_id' => 5), $relation->foreignKey($entity));
	}

	public function testHasManyForeignKey() {
		$config = array(
			'from' => $this->_gallery,
			'to' => $this->_image,
			'type' => 'hasMany',
			'fieldName' => 'images'
		);
		$relation = new Relationship($config + array(
			'key' => 'gallery_id'
		));

		$expected = array('gallery_id' => 5);
		$this->assertEqual($expected, $relation->foreignKey(array('id' => 5)));

		$relation = new Relationship($config + array(
			'key' => array('id' => 'gallery_id')
		));
		$this->assertEqual($expected, $relation->foreignKey(array('id' => 5)));
	}

	public function testBelongsToForeignKey() {
		$config = array(
			'from' => $this->_image,
			'to' => $this->_gallery,
			'type' => 'belongsTo',
			'fieldName' => 'gallery'
		);
		$relation = new Relationship($config + array(
			'key' => 'gallery_id'
		));

		$expected = array('gallery_id' => 5);
		$this->assertEqual($expected, $relation->foreignKey(array('id' => 5)));

		$relation = new Relationship($config + array(
			'key' => array('gallery_id' => 'id')
		));

		$this->assertEqual($expected, $relation->foreignKey(array('id' => 5)));
	}

	public function testEmptyRequiredOptions() {
		$this->expectException("/`'type'`, `'fieldName'` and `'from'` options can't be empty./");
		$query = new Relationship();
	}

	public function testEmptyToAndName() {
		$this->expectException("/`'to'` and `'name'` options can't both be empty./");
		$query = new Relationship(array(
			'from' => $this->_gallery,
			'type' => 'belongsTo',
			'fieldName' => 'field_id'
		));
	}
}

?>