<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\data\model\Query;
use \lithium\tests\mocks\data\model\MockQueryPost;
use \lithium\tests\mocks\data\model\MockQueryComment;

class QueryTest extends \lithium\test\Unit {

	protected $_queryArr = array(
		'mode' => '\lithium\tests\mocks\data\model\MockQueryPost',
		'type' => 'read',
		'order' => 'created DESC',
		'limit' => 10,
		'page' => 1,
		'fields' => array('id','author_id','title','body','created'),
		'conditions' => array('author_id' => 12)
	);

	public function setUp() {
		MockQueryPost::init();
		MockQueryComment::init();
	}

	/**
	 * Tests that configuration settings are delegating to matching method names
	 *
	 * @return void
	 */
	public function testObjectConstruction() {
		$query = new Query();
		$this->assertFalse($query->conditions());

		$query = new Query(array('conditions' => 'foo', 'fields' => array('id')));
		$this->assertEqual($query->conditions(), array('foo'));
	}

	public function testQueryExport() {
		$query = new Query();
	}

	public function testType() {
		$q = new Query($this->_queryArr);
		$expected = 'read';
		$result = $q->type();
		$this->assertEqual($expected, $result);
	}

	public function testModel() {
		$q = new Query($this->_queryArr);

		$expected = '\lithium\tests\mocks\data\model\MockQueryPost';
		$result = $q->model();
		$this->assertEqual($expected, $result);

		$result = $q->model('\lithium\tests\mocks\data\model\MockQueryComment');

		$expected = '\lithium\tests\mocks\data\model\MockQueryComment';
		$result = $q->model();
		$this->assertEqual($expected, $result);
	}
}

?>