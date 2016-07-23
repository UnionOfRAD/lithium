<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\mongodb;

class TagsFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\mongodb\Tags';

	protected $_fields = array(
		'_id' => array('type' => 'id'),
		'name' => array('type' => 'string', 'length' => 50),
		'author' => array('type' => 'id')
	);

	protected $_records = array(
		array('_id' => 1, 'name' => 'High Tech', 'author' => 6),
		array('_id' => 2, 'name' => 'Sport', 'author' => 9),
		array('_id' => 3, 'name' => 'Computer', 'author' => 6),
		array('_id' => 4, 'name' => 'Art', 'author' => 2),
		array('_id' => 5, 'name' => 'Science', 'author' => 1),
		array('_id' => 6, 'name' => 'City', 'author' => 2)
	);
}

?>