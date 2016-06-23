<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockGallery extends \lithium\data\Model {

	public $hasMany = [
		'Image' => ['to' => 'lithium\tests\mocks\data\model\MockImage']
	];
	public $belongsTo = [
		'Parent' => ['to' => 'lithium\tests\mocks\data\model\MockGallery']
	];

	protected $_meta = [
		'key' => 'id',
		'name' => 'Gallery',
		'source' => 'mock_gallery',
		'connection' => false
	];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'name']
	];
}

?>