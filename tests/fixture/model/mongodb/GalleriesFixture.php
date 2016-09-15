<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\mongodb;

class GalleriesFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\mongodb\Galleries';

	protected $_fields = array(
		'_id' => array('type' => 'id'),
		'name' => array('type' => 'string', 'length' => 50),
		'active' => array('type' => 'boolean', 'default' => true),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime')
	);

	protected $_records = array(
		array(
			'_id' => 1,
			'name' => 'Foo Gallery',
			'active' => true,
			'created' => '2007-06-20T21:02:27Z',
			'modified' => '2009-12-14T22:36:09Z'
		),
		array(
			'_id' => 2,
			'name' => 'Bar Gallery',
			'active' => true,
			'created' => '2008-08-22T16:12:42Z',
			'modified' => '2008-08-22T16:12:42Z'
		),
	);
}

?>