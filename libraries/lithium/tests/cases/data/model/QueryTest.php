<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\data\model\Record;
use \lithium\data\model\Query;
use \lithium\tests\mocks\data\model\MockDatabase;
use \lithium\tests\mocks\data\model\MockQueryPost;
use \lithium\tests\mocks\data\model\MockQueryComment;

class QueryTest extends \lithium\test\Unit {

	protected $_queryArr = array(
		'model' => '\lithium\tests\mocks\data\model\MockQueryPost',
		'type' => 'read',
		'order' => 'created DESC',
		'limit' => 10,
		'page' => 1,
		'fields' => array('id','author_id','title'),
		'conditions' => array('author_id' => 12),
		'comment' => 'Find all posts by author 12'
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

	public function testModel() {
		$q = new Query($this->_queryArr);

		$expected = '\lithium\tests\mocks\data\model\MockQueryPost';
		$result = $q->model();
		$this->assertEqual($expected, $result);

		$q->model('\lithium\tests\mocks\data\model\MockQueryComment');

		$expected = '\lithium\tests\mocks\data\model\MockQueryComment';
		$result = $q->model();
		$this->assertEqual($expected, $result);
	}

	public function testFields() {
		$q = new Query($this->_queryArr);

		$expected = array('id','author_id','title');
		$result = $q->fields();
		$this->assertEqual($expected, $result);

		$q->fields('content');

		$expected = array('id','author_id','title','content');
		$result = $q->fields();
		$this->assertEqual($expected, $result);

		$q->fields(array('updated','created'));

		$expected = array('id','author_id','title','content','updated','created');
		$result = $q->fields();
		$this->assertEqual($expected, $result);

		$q->fields(false);
		$q->fields(array('id', 'title'));

		$expected = array('id','title');
		$result = $q->fields();
		$this->assertEqual($expected, $result);
	}

	public function testLimit() {
		$q = new Query($this->_queryArr);

		$expected = 10;
		$result = $q->limit();
		$this->assertEqual($expected, $result);

		$q->limit(5);

		$expected = 5;
		$result = $q->limit();
		$this->assertEqual($expected, $result);
	}

	public function testPage() {
		$q = new Query($this->_queryArr);

		$expected = 1;
		$result = $q->page();
		$this->assertEqual($expected, $result);

		$q->page(5);

		$expected = 5;
		$result = $q->page();
		$this->assertEqual($expected, $result);
	}

	public function testOrder() {
		$q = new Query($this->_queryArr);

		$expected = 'created DESC';
		$result = $q->order();
		$this->assertEqual($expected, $result);

		$q->order('updated ASC');

		$expected = 'updated ASC';
		$result = $q->order();
		$this->assertEqual($expected, $result);
	}

	public function testRecord() {
		$q = new Query($this->_queryArr);

		$result = $q->record();
		$this->assertNull($result);

		$record = (object) array('id' => 12);
		$record->title = 'Lorem Ipsum';

		$q->record($record);

		$query_record = $q->record();

		$expected = 12;
		$result = $query_record->id;
		$this->assertEqual($expected, $result);

		$expected = 'Lorem Ipsum';
		$result = $query_record->title;
		$this->assertEqual($expected, $result);

		$this->assertTrue($record == $q->record());
	}

	public function testComment() {
		$q = new Query($this->_queryArr);

		$expected = 'Find all posts by author 12';
		$result = $q->comment();
		$this->assertEqual($expected, $result);

		$q->comment('Comment lorem');

		$expected = 'Comment lorem';
		$result = $q->comment();
		$this->assertEqual($expected, $result);
	}

	public function testData() {
		$q = new Query($this->_queryArr);

		$expected = array();
		$result = $q->data();
		$this->assertEqual($expected, $result);

		$record = new Record();
		$record->id = 12;
		$record->title = 'Lorem Ipsum';

		$q->record($record);

		$expected = array('id' => 12, 'title' => 'Lorem Ipsum');
		$result = $q->data();
		$this->assertEqual($expected, $result);

		$q->data(array('id' => 35, 'title' => 'Nix', 'body' => 'Prix'));

		$expected = array('id' => 35, 'title' => 'Nix', 'body' => 'Prix');
		$result = $q->data();
		$this->assertEqual($expected, $result);
	}

	public function testConditions() {
		$q = new Query($this->_queryArr);

		$expected = array('author_id' => 12);
		$result = $q->conditions();
		$this->assertEqual($expected, $result);

		$q->conditions(array('author_id' => 13, 'title LIKE' => 'Lorem%'));

		$expected = array('author_id' => 13, 'title LIKE' => 'Lorem%');
		$result = $q->conditions();
		$this->assertEqual($expected, $result);
	}

	public function testConditionFromRecord() {
		$r = new Record();
		$r->id = 12;
		$q = new Query(array(
			'model' => '\lithium\tests\mocks\data\model\MockQueryPost',
			'record' => $r
		));

		$expected = array('id' => 12);
		$result = $q->conditions();
		$this->assertEqual($expected, $result);
	}

	public function testExport() {
		$q = new Query($this->_queryArr);

		$ds = new MockDatabase();

		$export = $q->export($ds);

		$this->assertTrue(is_array($export));
		$this->skipIf(!is_array($export), '`Query`::export() does not return an array');

		$expected = array(
			'conditions',
			'fields',
			'order',
			'limit',
			'table',
			'comment',
			'model',
			'page'
		);
		$result = array_keys($export);
		$this->assertEqual($expected, $result);

		$expected = 'id, author_id, title';
		$result = $export['fields'];
		$this->assertEqual($expected, $result);

		$expected = MockQueryPost::meta('source');
		$result = $export['table'];
		$this->assertEqual($expected, $result);
	}
}

?>