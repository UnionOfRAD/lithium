<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\gallery;

class ImagesTagsFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\gallery\ImagesTags';

	protected $_fields = array(
		'id' => array('type' => 'id'),
		'image_id' => array('type' => 'integer', 'length' => 11),
		'tag_id' => array('type' => 'integer', 'length' => 11)
	);

	protected $_records = array(
		array('id' => 1, 'image_id' => 1, 'tag_id' => 1),
		array('id' => 2, 'image_id' => 1, 'tag_id' => 3),
		array('id' => 3, 'image_id' => 2, 'tag_id' => 5),
		array('id' => 4, 'image_id' => 3, 'tag_id' => 6),
		array('id' => 5, 'image_id' => 4, 'tag_id' => 6),
		array('id' => 6, 'image_id' => 4, 'tag_id' => 3),
		array('id' => 7, 'image_id' => 4, 'tag_id' => 1)
	);
}

?>