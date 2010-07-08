<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\data\Connections;
use \lithium\data\model\Query;
use \lithium\data\entity\Record;
use \lithium\tests\mocks\data\MockPostObject;
use \lithium\tests\mocks\data\model\MockDatabase;
use \lithium\tests\mocks\data\model\MockQueryPost;
use \lithium\tests\mocks\data\model\MockQueryComment;

class QueryTest extends \lithium\test\Unit {

	protected $_configs = array();

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
		$this->db = new MockDatabase();
		$this->_configs = Connections::config();

		Connections::reset();
		Connections::config(array('mock-database-connection' => array(
			'object' => &$this->db,
			'adapter' => 'MockDatabase'
		)));

		MockQueryPost::config();
		MockQueryComment::config();
	}

	public function tearDown() {
		Connections::reset();
		Connections::config($this->_configs);
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
		$query = new Query($this->_queryArr);

		$expected = '\lithium\tests\mocks\data\model\MockQueryPost';
		$result = $query->model();
		$this->assertEqual($expected, $result);

		$query->model('\lithium\tests\mocks\data\model\MockQueryComment');

		$expected = '\lithium\tests\mocks\data\model\MockQueryComment';
		$result = $query->model();
		$this->assertEqual($expected, $result);
	}

	public function testFields() {
		$query = new Query($this->_queryArr);

		$expected = array('id','author_id','title');
		$result = $query->fields();
		$this->assertEqual($expected, $result);

		$query->fields('content');

		$expected = array('id','author_id','title','content');
		$result = $query->fields();
		$this->assertEqual($expected, $result);

		$query->fields(array('updated','created'));

		$expected = array('id','author_id','title','content','updated','created');
		$result = $query->fields();
		$this->assertEqual($expected, $result);

		$query->fields(false);
		$query->fields(array('id', 'title'));

		$expected = array('id','title');
		$result = $query->fields();
		$this->assertEqual($expected, $result);
	}

	public function testLimit() {
		$query = new Query($this->_queryArr);

		$expected = 10;
		$result = $query->limit();
		$this->assertEqual($expected, $result);

		$query->limit(5);

		$expected = 5;
		$result = $query->limit();
		$this->assertEqual($expected, $result);
	}

	public function testPage() {
		$query = new Query($this->_queryArr);

		$expected = 1;
		$result = $query->page();
		$this->assertEqual($expected, $result);

		$query->page(5);

		$expected = 5;
		$result = $query->page();
		$this->assertEqual($expected, $result);
	}

	public function testOrder() {
		$query = new Query($this->_queryArr);

		$expected = 'created DESC';
		$result = $query->order();
		$this->assertEqual($expected, $result);

		$query->order('updated ASC');

		$expected = 'updated ASC';
		$result = $query->order();
		$this->assertEqual($expected, $result);
	}

	public function testRecord() {
		$query = new Query($this->_queryArr);

		$result = $query->entity();
		$this->assertNull($result);

		$record = (object) array('id' => 12);
		$record->title = 'Lorem Ipsum';

		$query->entity($record);
		$query_record = $query->entity();

		$expected = 12;
		$result = $query_record->id;
		$this->assertEqual($expected, $result);

		$expected = 'Lorem Ipsum';
		$result = $query_record->title;
		$this->assertEqual($expected, $result);

		$this->assertTrue($record == $query->entity());
	}

	public function testComment() {
		$query = new Query($this->_queryArr);

		$expected = 'Find all posts by author 12';
		$result = $query->comment();
		$this->assertEqual($expected, $result);

		$query->comment('Comment lorem');

		$expected = 'Comment lorem';
		$result = $query->comment();
		$this->assertEqual($expected, $result);
	}

	public function testData() {
		$query = new Query($this->_queryArr);

		$expected = array();
		$result = $query->data();
		$this->assertEqual($expected, $result);

		$record = new Record();
		$record->id = 12;
		$record->title = 'Lorem Ipsum';

		$query->entity($record);

		$expected = array('id' => 12, 'title' => 'Lorem Ipsum');
		$result = $query->data();
		$this->assertEqual($expected, $result);

		$query->data(array('id' => 35, 'title' => 'Nix', 'body' => 'Prix'));

		$expected = array('id' => 35, 'title' => 'Nix', 'body' => 'Prix');
		$result = $query->data();
		$this->assertEqual($expected, $result);
	}

	public function testConditions() {
		$query = new Query($this->_queryArr);

		$expected = array('author_id' => 12);
		$result = $query->conditions();
		$this->assertEqual($expected, $result);

		$query->conditions(array('author_id' => 13, 'title LIKE' => 'Lorem%'));

		$expected = array('author_id' => 13, 'title LIKE' => 'Lorem%');
		$result = $query->conditions();
		$this->assertEqual($expected, $result);
	}

	public function testConditionFromRecord() {
		$entity = new Record();
		$entity->id = 12;
		$query = new Query(compact('entity') + array(
			'model' => '\lithium\tests\mocks\data\model\MockQueryPost',
		));

		$expected = array('id' => 12);
		$result = $query->conditions();
		$this->assertEqual($expected, $result);
	}

	public function testExtra() {
		$object = new MockPostObject(array('id' => 1, 'data' => 'test'));
		$query = new Query(array(
			'conditions' => 'foo', 'extra' => 'value', 'extraObject' => $object
		));
		$this->assertEqual(array('foo'), $query->conditions());
		$this->assertEqual('value', $query->extra());
		$this->assertEqual($object, $query->extraObject());
		$this->assertNull($query->extra2());
	}

	public function testExport() {
		$query = new Query($this->_queryArr);
		$ds = new MockDatabase();
		$export = $query->export($ds);

		$this->assertTrue(is_array($export));
		$this->skipIf(!is_array($export), 'Query::export() does not return an array');

		$expected = array(
			'calculate',
			'comment',
			'conditions',
			'data',
			'fields',
			'group',
			'joins',
			'limit',
			'map',
			'model',
			'name',
			'offset',
			'order',
			'page',
			'source',
			'whitelist'
		);
		$result = array_keys($export);

		sort($expected);
		sort($result);
		$this->assertEqual($expected, $result);

		$expected = 'id, author_id, title';
		$result = $export['fields'];
		$this->assertEqual($expected, $result);

		$expected = MockQueryPost::meta('source');
		$result = $export['source'];
		$this->assertEqual($expected, $result);
	}

	public function testPagination() {
		$query = new Query(array('limit' => 5, 'page' => 1));
		$this->assertEqual(0, $query->offset());

		$query = new Query(array('limit' => 5, 'page' => 2));
		$this->assertEqual(5, $query->offset());

		$query->page(1);
		$this->assertEqual(0, $query->offset());
	}

	public function testJoin() {
		$query = new Query(array('joins' => array(array('foo' => 'bar'))));
		$query->join(array(array('bar' => 'baz')));

		$this->assertEqual($query->join(), array(array('foo' => 'bar'), array('bar' => 'baz')));
	}

	/**
	 * Tests that assigning a whitelist to a query properly restricts the list of data fields that
	 * the query exposes.
	 *
	 * @return void
	 */
	public function testWhitelisting() {
		$data = array('foo' => 1, 'bar' => 2, 'baz' => 3);
		$query = new Query(compact('data'));
		$this->assertEqual($data, $query->data());

		$query = new Query(compact('data') + array('whitelist' => array('foo', 'bar')));
		$this->assertEqual(array('foo' => 1, 'bar' => 2), $query->data());
	}

	/**
	 * Tests basic property accessors and mutators.
	 *
	 * @return void
	 */
	public function testBasicAssignments() {
		$query = new Query();
		$group = array('key' => 'hits', 'reduce' => 'function() {}');
		$calculate = 'count';

		$this->assertNull($query->group());
		$query->group($group);
		$this->assertEqual($group, $query->group());

		$this->assertNull($query->calculate());
		$query->calculate($calculate);
		$this->assertEqual($calculate, $query->calculate());

		$query = new Query(compact('calculate', 'group'));
		$this->assertEqual($group, $query->group());
		$this->assertEqual($calculate, $query->calculate());
	}
}

?>