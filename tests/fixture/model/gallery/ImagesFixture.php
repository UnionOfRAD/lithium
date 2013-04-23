<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\gallery;

class ImagesFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\gallery\Images';

	protected $_fields = array(
		'id' => array('type' => 'id'),
		'gallery_id' => array('type' => 'integer', 'length' => 11),
		'image' => array('type' => 'string', 'length' => 255),
		'title' => array('type' => 'string', 'length' => 50),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime')
	);

	protected $_records = array(
		array(
			'id' => 1,
			'gallery_id' => 1,
			'image' => 'someimage.png',
			'title' => 'Amiga 1200',
			'created' => '2011-05-22 10:43:13',
			'modified' => '2012-11-30 18:38:10'
		),
		array(
			'id' => 2,
			'gallery_id' => 1,
			'image' => 'image.jpg',
			'title' => 'Srinivasa Ramanujan',
			'created' => '2009-01-05 08:39:27',
			'modified' => '2009-03-14 05:42:07'
		),
		array(
			'id' => 3,
			'gallery_id' => 1,
			'image' => 'photo.jpg',
			'title' => 'Las Vegas',
			'created' => '2010-08-11 23:12:03',
			'modified' => '2010-09-24 04:45:14'
		),
		array(
			'id' => 4,
			'gallery_id' => 2,
			'image' => 'picture.jpg',
			'title' => 'Silicon Valley',
			'created' => '2008-08-22 17:55:10',
			'modified' => '2008-08-22 17:55:10'
		),
		array(
			'id' => 5,
			'gallery_id' => 2,
			'image' => 'unknown.gif',
			'title' => 'Unknown',
			'created' => '2011-02-12 08:32:10',
			'modified' => '2012-04-16 14:18:52'
		)
	);
}

?>