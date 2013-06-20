<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockGallery extends \lithium\data\Model {

	public $hasMany = array(
		'Image' => array('to' => 'lithium\tests\mocks\data\model\MockImage')
	);
	public $belongsTo = array(
		'Parent' => array('to' => 'lithium\tests\mocks\data\model\MockGallery')
	);

	protected $_meta = array(
		'key' => 'id',
		'name' => 'Gallery',
		'source' => 'mock_gallery',
		'connection' => false
	);

	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'title' => array('type' => 'name')
	);
}

?>