<?php

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Images;
use lithium\tests\fixture\model\gallery\Galleries;
use li3_fixtures\test\Fixtures;

class FieldsTest extends \lithium\tests\integration\data\Base {

	protected $_fixtures = array(
		'images' => 'lithium\tests\fixture\model\gallery\ImagesFixture',
		'galleries' => 'lithium\tests\fixture\model\gallery\GalleriesFixture',
	);

	/**
	 * Skip the test if no test database connection available.
	 */
	public function skip() {
		parent::connect($this->_connection);
		if (!class_exists('li3_fixtures\test\Fixtures')) {
			$this->skipIf(true, "These tests need `'li3_fixtures'` to be runned.");
		}
		$this->skipIf($this->with(array('CouchDb')));
	}

	/**
	 * Creating the test database
	 */
	public function setUp() {
		Fixtures::config(array(
			'db' => array(
				'adapter' => 'Connection',
				'connection' => $this->_connection,
				'fixtures' => $this->_fixtures
			)
		));
		Fixtures::create('db');

		$db = $this->_db;
		if (!$db::enabled('schema')) {
			$gallery = Fixtures::get('db', 'galleries');
			$images = Fixtures::get('db', 'images');
			Galleries::schema($gallery->fields());
			Images::schema($images->fields());
		}
	}

	/**
	 * Dropping the test database
	 */
	public function tearDown() {
		Fixtures::clear('db');
	}

	public function testSingleField() {
		$new = Galleries::create(array('name' => 'People'));
		$key = Galleries::meta('key');
		$new->save();
		$id = is_object($new->{$key}) ? (string) $new->{$key} : $new->{$key};

		$entity = Galleries::first($id);

		$this->assertInstanceOf('lithium\data\Entity', $entity);

		$expected = array(
			$key => $id,
			'name' => 'People',
			'active' => true
		);
		$result = $entity->data();
		$this->assertEqual($expected, array_filter($result));

		$entity = Galleries::first(array(
			'conditions' => array($key => $id),
			'fields' => array($key)
		));

		$this->assertInstanceOf('lithium\data\Entity', $entity);

		$expected = array($key => $id);
		$result = $entity->data();
		$this->assertEqual($expected, $result);

		$entity = Galleries::find('first',array(
			'conditions' => array($key => $id),
			'fields' => array($key, 'name')
		));
		$this->assertInstanceOf('lithium\data\Entity', $entity);

		$entity->name = 'Celebrities';
		$result = $entity->save();
		$this->assertTrue($result);

		$entity = Galleries::find('first',array(
			'conditions' => array($key => $id),
			'fields' => array($key, 'name')
		));
		$this->assertEqual($entity->name, 'Celebrities');
		$new->delete();
	}

	public function testFieldsWithJoins() {
		$db = $this->_db;
		$this->skipIf(!$db::enabled('relationships'));
		$this->skipIf($this->with(array('MongoDb')));

		$new = Galleries::create(array('name' => 'Celebrities'));
		$cKey = Galleries::meta('key');
		$result = $new->save();
		$this->assertTrue($result);
		$cId = (string) $new->{$cKey};

		$new = Images::create(array(
			'gallery_id' => $cId,
			'title' => 'John Doe'
		));
		$eKey = Images::meta('key');
		$result = $new->save();
		$this->assertTrue($result);

		$eId = (string) $new->{$eKey};
		$entity = Galleries::first(array(
			'with' => 'Images',
			'conditions' => array(
				'Galleries.id' => $cId
			),
			'fields' => array(
				'Galleries' => array('name'),
				'Images' => array('id', 'title')
			)
		));
		$expected = array(
			'id' => $cId,
			'name' => 'Celebrities',
			'images' => array(
				$eId => array(
					'id' => $eId,
					'title' => 'John Doe'
				)
			)
		);
		$this->assertEqual($expected, $entity->data());
	}
}

?>