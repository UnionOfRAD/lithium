<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data\Model;

class MockDatabasePost extends \Lithium\Data\Model {

	public $hasMany = array('MockDatabaseComment');

	protected $_meta = array(
		'connection' => 'mock-database-connection'
	);

	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'author_id' => array('type' => 'integer'),
		'title' => array('type' => 'string'),
		'created' => array('type' => 'datetime')
	);
}

?>