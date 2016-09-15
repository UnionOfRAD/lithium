<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use MongoId;
use lithium\core\Libraries;
use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\tests\fixture\model\mongodb\Images;
use lithium\tests\fixture\model\mongodb\Galleries;
use li3_fixtures\test\Fixtures;

class MongoDbTest extends \lithium\tests\integration\data\Base {

	protected $_export = null;

	protected $_fixtures = array(
		'images' => 'lithium\tests\fixture\model\mongodb\ImagesFixture',
		'galleries' => 'lithium\tests\fixture\model\mongodb\GalleriesFixture',
	);

	public function skip() {
		parent::connect($this->_connection);
		if (!class_exists('li3_fixtures\test\Fixtures')) {
			$this->skipIf(true, 'Need `li3_fixtures` to run tests.');
		}
		$this->skipIf(!$this->with(array('MongoDb')));
		$this->_export = Libraries::path('lithium\tests\fixture\model\mongodb\export', array(
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

	public function testCountOnEmptyResultSet() {
		$data = Galleries::find('all', array('conditions' => array('name' => 'no match')));

		$expected = 0;
		$result = $data->count();
		$this->assertIdentical($expected, $result);
	}

	public function testIterateOverEmptyResultSet() {
		$data = Galleries::find('all', array('conditions' => array('name' => 'no match')));

		$result = next($data);
		$this->assertNull($result);
	}

	public function testDateCastingUsingExists() {
		Galleries::remove();
		Galleries::config(array('schema' => array('_id' => 'id', 'created_at' => 'date')));
		$gallery = Galleries::create(array('created_at' => time()));
		$gallery->save();

		$result = Galleries::first(array('conditions' => array('created_at' => array('$exists' => false))));
		$this->assertNull($result);
	}

	public function testManyToOne() {
		$opts = array('conditions' => array('gallery' => 1));

		$query = new Query($opts + array(
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\mongodb\Images',
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
		$opts = array('conditions' => array('_id' => 1));

		$query = new Query($opts + array(
			'type' => 'read',
			'model' => 'lithium\tests\fixture\model\mongodb\Galleries',
			'source' => 'galleries',
			'alias' => 'Galleries',
			'with' => array('Images')
		));
		$galleries = $this->_db->read($query)->data();
		$expected = include $this->_export . '/testOneToMany.php';
		$this->assertEqual($expected, $galleries);

		$gallery = Galleries::find('first', $opts + array('with' => 'Images'))->data();
		$this->assertEqual(3, count($gallery['images']));
		$this->assertEqual(reset($expected), $gallery);
	}

}

?>