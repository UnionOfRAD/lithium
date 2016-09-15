<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\mongodb;

class ImagesTagsFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\mongodb\ImagesTags';

	protected $_fields = array(
		'_id' => array('type' => 'id'),
		'image_id' => array('type' => 'id'),
		'tag_id' => array('type' => 'id')
	);

	protected $_records = array(
		array('_id' => 1, 'image' => 1, 'tag' => 1),
		array('_id' => 2, 'image' => 1, 'tag' => 3),
		array('_id' => 3, 'image' => 2, 'tag' => 5),
		array('_id' => 4, 'image' => 3, 'tag' => 6),
		array('_id' => 5, 'image' => 4, 'tag' => 6),
		array('_id' => 6, 'image' => 4, 'tag' => 3),
		array('_id' => 7, 'image' => 4, 'tag' => 1)
	);
}

?>