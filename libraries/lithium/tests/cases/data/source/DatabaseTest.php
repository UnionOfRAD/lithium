<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\model\Query;
use \lithium\data\source\Database;
use \lithium\tests\mocks\data\model\MockDatabase;
use \lithium\tests\mocks\data\model\MockDatabasePost;
use \lithium\tests\mocks\data\model\MockDatabaseComment;

class DatabaseTest extends \lithium\test\Unit {

	public $db = null;

	public function setUp() {
		$this->db = new MockDatabase();
		MockDatabasePost::__init();
		MockDatabaseComment::__init();
	}

	public function testColumnMapping() {
		$result = $this->db->columns(new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost'
		)));
		$expected = array(
			'lithium\tests\mocks\data\model\MockDatabasePost' => array('id', 'title', 'created')
		);
		$this->assertEqual($expected, $result);

		$query = new Query(array(
			'model' =>  'lithium\tests\mocks\data\model\MockDatabasePost', 'fields' => array('*')
		));
		$result = $this->db->columns($query);
		$this->assertEqual($expected, $result);

		$fields = array('MockDatabaseComment');
		$query = new Query(array(
			'model' => 'lithium\tests\mocks\data\model\MockDatabasePost', 'fields' => $fields
		));
		$result = $this->db->columns($query);
		$expected = array(
			'lithium\tests\mocks\data\model\MockDatabaseComment' => array_keys(
				MockDatabaseComment::schema()
			)
		);
		$this->assertEqual($expected, $result);
	}
}

?>