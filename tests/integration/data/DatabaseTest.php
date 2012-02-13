<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\source\Database;
use lithium\tests\mocks\data\source\Images;
use lithium\tests\mocks\data\source\Galleries;
use lithium\util\String;

class DatabaseTest extends \lithium\test\Integration {

	public $db = null;
	protected $_dbConfig;

	public $images = array(
			array(
				'id' => null,
				'gallery_id' => null,
				'image' => 'someimage.png',
				'title' => 'Image1 Title'
			),
			array(
				'id' => null,
				'gallery_id' => null,
				'image' => 'anotherImage.jpg',
				'title' => 'Our Vacation'
			),
			array(
				'id' => null,
				'gallery_id' => null,
				'image' => 'me.bmp',
				'title' => 'Me.'
			)
		);

	public $gallery = array('name' => 'Foo Gallery');

	public function setUp() {
		$mockBase = LITHIUM_LIBRARY_PATH . '/lithium/tests/mocks/data/source/database/adapter/';
		$files = array('galleries' => '_galleries.sql', 'images' => '_images.sql');
		$files = array_diff_key($files, array_flip($this->db->sources()));

		foreach ($files as $file) {
			$sqlFile = $mockBase . strtolower($this->_dbConfig['adapter']) . $file;
			$this->skipIf(!file_exists($sqlFile), "SQL file $sqlFile does not exist.");
			$sql = file_get_contents($sqlFile);
			$this->db->read($sql, array('return' => 'resource'));
		}
	}

	public function tearDown() {
		$this->db->read('DROP TABLE IF EXISTS `images`;');
		$this->db->read('DROP TABLE IF EXISTS `galleries`;');
	}

	public function skip() {
		$connection = 'lithium_mysql_test';
		$this->_dbConfig = Connections::get($connection, array(
			'config' => true
		));
		$isAvailable = (
			$this->_dbConfig &&
			Connections::get($connection)->isConnected(array(
				'autoConnect' => true
			))
		);
		$this->skipIf(!$isAvailable, "No {$connection} connection available.");

		$this->db = Connections::get($connection);
		$this->skipIf(
			!($this->db instanceof Database),
			"The {$connection} connection is not a relational database."
		);
	}

	public function testCreateData() {
		$gallery = Galleries::create($this->gallery);
		$this->assertTrue($gallery->save());
		$this->gallery = array('id' => $gallery->id) + $this->gallery;

		foreach ($this->images as $key => $image) {
			unset($image['id'], $image['gallery_id']);
			$img = Images::create($image + array('gallery_id' => $gallery->id));
			$this->assertEqual(true, $img->save());
			$this->images[$key]['id'] = $img->id;
			$this->images[$key]['gallery_id'] = $gallery->id;
		}
	}

	public function testManyToOne() {
		$this->_createGalleryWithImages();
		$opts = array('conditions' => array('gallery_id' => $this->gallery['id']));

		$query = new Query($opts + array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\source\Images',
			'source' => 'images',
			'alias' => 'Images',
			'with' => array('Galleries')
		));
		$images = $this->db->read($query)->data();
		reset($this->images);

		foreach ($images as $key => $image) {
			$expect = current($this->images) + array(
				'gallery_id' => $this->gallery['id'],
				'gallery' => $this->gallery
			);
			$this->assertEqual($expect, $image);
			next($this->images);
		}

		$images = Images::find('all', $opts + array('with' => 'Galleries'))->data();
		reset($this->images);

		foreach ($images as $key => $image) {
			$expect = (array) current($this->images) + array('gallery' => $this->gallery);
			ksort($expect);
			ksort($image);
			$this->assertEqual($expect, $image);
			next($this->images);
		}
	}

	public function testOneToMany() {
		$this->_createGalleryWithImages();
		$opts = array('conditions' => array('Galleries.id' => $this->gallery['id']));

		$query = new Query($opts + array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\source\Galleries',
			'source' => 'galleries',
			'alias' => 'Galleries',
			'with' => array('Images')
		));
		$galleries = $this->db->read($query)->data();

		foreach ($galleries as $key => $gallery) {
			$expect = $this->gallery + array('images' => $this->images);
			$this->assertEqual($expect, $gallery);
		}

		$gallery = Galleries::find('first', $opts + array('with' => 'Images'))->data();
		$expect = $this->gallery + array('images' => $this->images);
		$this->assertEqual($expect, $gallery);
	}

	public function testUpdate() {
		$this->_createGalleryWithImages();
		$options = array('conditions' => array('gallery_id' => $this->gallery['id']));
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
		$this->_createGalleryWithImages();
		$fields = array('id', 'image');
		$image = Images::find('first', array(
			'fields' => $fields,
			'conditions' => array(
				'gallery_id' => $this->gallery['id']
			)
		));
		$this->assertEqual($fields, array_keys($image->data()));
	}

	public function testOrder() {
		$this->_createGalleryWithImages();
		$images = Images::find('all', array(
			'order' => 'id DESC',
			'conditions' => array(
				'gallery_id' => $this->gallery['id']
			)
		))->data();
		krsort($this->images);
		reset($this->images);

		foreach ($images as $image) {
			$this->assertEqual(current($this->images), $image);
			next($this->images);
		}
	}

	public function testRemove() {
		$this->assertTrue(Galleries::remove());
		$this->assertTrue(Images::remove());
	}

	protected function _createGallery() {
		$gallery = Galleries::create($this->gallery);
		$gallery->save();
		return $gallery;
	}
	protected function _createGalleryWithImages() {
		$gallery = $this->_createGallery();

		foreach ($this->images as $key => $image) {
			unset($image['id'], $image['gallery_id']);
			$img = Images::create($image + array('gallery_id' => $gallery->id));
			$this->assertEqual(true, $img->save());
			$this->images[$key]['id'] = $img->id;
			$this->images[$key]['gallery_id'] = $gallery->id;
		}
	}
}

?>