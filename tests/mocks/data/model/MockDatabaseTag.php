<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockDatabaseTag extends \lithium\data\Model {

	public $hasMany = ['MockDatabaseTagging'];

	protected $_meta = ['connection' => false, 'key' => 'id'];

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'title' => ['type' => 'string'],
		'created' => ['type' => 'datetime']
	];
}

?>