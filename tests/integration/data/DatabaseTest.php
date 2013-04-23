<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
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
			$this->skipIf(true, "These tests need `'li3_fixtures'` to be runned.");
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
			)
		);

		if ($this->with('PostgreSql')) {
			$options['db']['alters']['change']['id'] = array(
				'value' => function ($id) {
					return (object) 'default';
				}
			);
		}

		Fixtures::config($options);
		Fixtures::save('db');
	}

	/**
	 * Dropping the test database
	 */
	public function tearDown() {
		Fixtures::clear('db');
	}

	public function testConnectWithNoDatabase() {
		$config = $this->_dbConfig;
		$config['database'] = null;
		$config['object'] = null;
		$connection = 'no_database';
		Connections::add($connection, $config);
		$this->expectException("/No Database configured/");
		Connections::get($connection)->connect();
	}

	public function testConnectWithWrongHost() {
		$this->skipIf(!$this->with('PostgreSql'));
		$config = $this->_dbConfig;
		$config['host'] = 'unknown.host.nowhere';
		$config['object'] = null;
		$connection = 'wrong_host';
		Connections::add($connection, $config);
		$this->expectException();
		Connections::get($connection)->connect();
	}

	public function testConnectWithWrongPassword() {
		$this->skipIf(!$this->with('PostgreSql'));
		$config = $this->_dbConfig;
		$config['login'] = 'wrong_login';
		$config['password'] = 'wrong_pass';
		$config['object'] = null;
		$connection = 'wrong_passord';
		Connections::add($connection, $config);
		$this->expectException();
		Connections::get($connection)->connect();
	}

	public function testExecuteException() {
		$this->expectException("/error/");
		$this->_db->read('SELECT * FROM * FROM table');
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
		$this->assertEqual(reset($expected), $gallery);
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
}

?>