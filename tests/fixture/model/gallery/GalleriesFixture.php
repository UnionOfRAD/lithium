<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\gallery;

class GalleriesFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\gallery\Galleries';

	protected $_fields = array(
		'id' => array('type' => 'id'),
		'name' => array('type' => 'string', 'length' => 50),
		'active' => array('type' => 'boolean', 'default' => true),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime')
	);

	protected $_records = array(
		array(
			'id' => 1,
			'name' => 'Foo Gallery',
			'active' => true,
			'created' => '2007-06-20 21:02:27',
			'modified' => '2009-12-14 22:36:09'
		),
		array(
			'id' => 2,
			'name' => 'Bar Gallery',
			'active' => true,
			'created' => '2008-08-22 16:12:42',
			'modified' => '2008-08-22 16:12:42'
		),
	);
}

?>