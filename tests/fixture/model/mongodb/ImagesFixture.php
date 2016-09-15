<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\mongodb;

class ImagesFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\mongodb\Images';

	protected $_fields = array(
		'_id' => array('type' => 'id'),
		'gallery' => array('type' => 'id'),
		'image' => array('type' => 'string', 'length' => 255),
		'title' => array('type' => 'string', 'length' => 50),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime')
	);

	protected $_records = array(
		array(
			'_id' => 1,
			'gallery' => 1,
			'image' => 'someimage.png',
			'title' => 'Amiga 1200',
			'created' => '2011-05-22T10:43:13Z',
			'modified' => '2012-11-30T18:38:10Z'
		),
		array(
			'_id' => 2,
			'gallery' => 1,
			'image' => 'image.jpg',
			'title' => 'Srinivasa Ramanujan',
			'created' => '2009-01-05T08:39:27Z',
			'modified' => '2009-03-14T05:42:07Z'
		),
		array(
			'_id' => 3,
			'gallery' => 1,
			'image' => 'photo.jpg',
			'title' => 'Las Vegas',
			'created' => '2010-08-11T23:12:03Z',
			'modified' => '2010-09-24T04:45:14Z'
		),
		array(
			'_id' => 4,
			'gallery' => 2,
			'image' => 'picture.jpg',
			'title' => 'Silicon Valley',
			'created' => '2008-08-22T17:55:10Z',
			'modified' => '2008-08-22T17:55:10Z'
		),
		array(
			'_id' => 5,
			'gallery' => 2,
			'image' => 'unknown.gif',
			'title' => 'Unknown',
			'created' => '2011-02-12T08:32:10Z',
			'modified' => '2012-04-16T14:18:52Z'
		)
	);
}

?>