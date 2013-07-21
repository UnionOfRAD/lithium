<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\mongo_db;

use MongoId;
use lithium\data\source\MongoDb;
use lithium\data\source\mongo_db\Schema;

class SchemaTest extends \lithium\test\Unit {

	protected $_db;

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'MongoDb is not enabled');
	}

	public function setUp() {
		$this->_db = new MongoDb(array('autoConnect' => false));
	}

	public function testCastingIdArray() {
		$schema = new Schema(array('fields' => array(
			'_id' => array('type' => 'id'),
			'users' => array('type' => 'id', 'array' => true)
		)));

		$result = $schema->cast(null, null, array('users' => new MongoId()), array(
			'database' => $this->_db
		));

		$this->assertEqual(array('users'), array_keys($result->data()));
		$this->assertCount(1, $result->users);
		$this->assertInstanceOf('MongoId', $result->users[0]);
	}

	public function testCastingEmptyValues() {
		$schema = new Schema(array('fields' => array(
			'_id' => array('type' => 'id'),
			'foo' => array('type' => 'string', 'array' => true)
		)));
		$result = $schema->cast(null, null, null, array('database' => $this->_db));
	}
}

?>