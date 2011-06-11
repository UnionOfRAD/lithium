<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\entity\Record;
use lithium\tests\mocks\data\MockPostObject;
use lithium\tests\mocks\data\model\MockDatabase;
use lithium\tests\mocks\data\model\MockQueryPost;
use lithium\tests\mocks\data\model\MockQueryComment;

class QueryTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockQueryPost';

	protected $_configs = array();

	protected $_queryArr = array(
		'model' => 'lithium\tests\mocks\data\model\MockQueryPost',
		'type' => 'read',
		'order' => 'created DESC',
		'limit' => 10,
		'page' => 1,
		'fields' => array('id', 'author_id', 'title'),
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
		$this->assertEqual($this->_model, $query->model());

		$query->model('lithium\tests\mocks\data\model\MockQueryComment');

		$expected = 'lithium\tests\mocks\data\model\MockQueryComment';
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

		$query->limit(false);
		$this->assertNull($query->limit());
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
			'model' => $this->_model
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
		MockQueryPost::meta('source', 'foo');
		$query = new Query($this->_queryArr);
		$ds = new MockDatabase();
		$export = $query->export($ds);

		$this->assertTrue(is_array($export));
		$this->skipIf(!is_array($export), 'Query::export() does not return an array');

		$expected = array(
			'alias',
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
			'type',
			'whitelist',
			'relationships'
		);
		$result = array_keys($export);

		sort($expected);
		sort($result);
		$this->assertEqual($expected, $result);

		$expected = 'MockQueryPost.id, MockQueryPost.author_id, MockQueryPost.title';
		$result = $export['fields'];
		$this->assertEqual($expected, $result);

		$result = $export['source'];
		$this->assertEqual("{foo}", $result);
	}

	public function testRestrictedKeyExport() {
		$options = array(
			'type' => 'update',
			'data' => array('title' => 'Bar'),
			'conditions' => array('title' => 'Foo'),
			'model' => $this->_model
		);
		$query = new Query($options);

		$result = $query->export(Connections::get('mock-database-connection'), array(
			'keys' => array('data', 'conditions')
		));
		$expected = array(
			'type' => 'update',
			'data' => array('title' => 'Bar'),
			'conditions' => "WHERE {title} = 'Foo'"
		);
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
		$query->join(array('bar' => 'baz'));
		$expected = array(array('foo' => 'bar'), array('bar' => 'baz'));
		$joins = $query->join();

		$this->assertEqual('bar', $joins[0]->foo());
		$this->assertNull($joins[0]->bar());

		$this->assertEqual('baz', $joins[1]->bar());
		$this->assertNull($joins[1]->foo());

		$query->join('zim', array('dib' => 'gir'));
		$this->assertEqual(3, count($query->join()));

		$expected = array(
			array('foo' => 'bar'),
			array('bar' => 'baz'),
			'zim' => array('dib' => 'gir')
		);
		$this->assertEqual(3, count($query->join()));
		$this->assertEqual('gir', $query->join('zim')->dib());
	}

	public function testWithAssociation() {
		$model = $this->_model;
		$model::bind('hasMany', 'MockQueryComment', array(
			'class' => 'lithium\tests\mocks\data\model\MockQueryComment'
		));

		$query = new Query(compact('model') + array('with' => 'MockQueryComment'));
		$export = $query->export(new MockDatabase());
		$expected = array(
			'MockQueryComment' => array(
				'type' => 'hasMany',
				'model' => 'lithium\tests\mocks\data\model\MockQueryComment',
				'fieldName' => 'mock_query_comments'
			)
		);
		$keyExists = isset($export['relationships']);
		$this->assertTrue($keyExists);
		$this->skipIf(!$keyExists);
		$this->assertEqual($expected, $export['relationships']);

		$query = new Query(array(
				'model' => $this->_model,
				'with' => 'MockQueryComment',
				'limit' => 3,
				'order' => 'author_id ASC',
				'group' => 'author_id'
			));
		$expected = 'SELECT MockQueryPost.id FROM {foo} AS {MockQueryPost} LEFT JOIN AS ';
		$expected .= '{MockQueryComment} ON {MockQueryPost}.{id} = {MockQueryComment}';
		$expected .= '.{mock_query_post_id} GROUP BY MockQueryPost.id ORDER BY author_id ASC ';
		$expected .= 'LIMIT 3;';
		$this->assertEqual($expected, $this->db->sql);
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

		$query->group(false);
		$this->assertNull($query->group());
	}

	public function testInstantiationWithConditionsAndData() {
		$options = array(
			'type' => 'update',
			'data' => array('title' => '..'),
			'conditions' => array('title' => 'FML'),
			'model' => 'lithium\tests\mocks\data\model\MockQueryPost'
		);
		$query = new Query($options);
		$result = $query->export(Connections::get('mock-database-connection'));

		$this->assertEqual(array('title' => '..'), $result['data']);
		$this->assertEqual("WHERE {title} = 'FML'", $result['conditions']);
	}

	public function testEntityConditions() {
		$entity = new Record(array('model' => $this->_model, 'exists' => true));
		$entity->id = 13;
		$query = new Query(compact('entity'));
		$this->assertEqual(array('id' => 13), $query->conditions());
	}

	public function testInvalidEntityCondition() {
		$entity = new Record(array('model' => $this->_model, 'exists' => true));
		$entity->_id = 13;
		$query = new Query(compact('entity'));
		$this->expectException('/No matching primary key found/');
		$query->conditions();
	}

	public function testAutomaticAliasing() {
		$query = new Query(array('model' => $this->_model));
		$this->assertEqual('MockQueryPost', $query->alias());
	}

	public function testFluentInterface() {
		$query = new Query();
		$conditions = array('foo' => 'bar');
		$fields = array('foo', 'bar', 'baz', 'created');
		$order = array('created' => 'ASC');

		$result = $query->conditions($conditions)->fields($fields)->order($order);
		$this->assertEqual($result, $query);
		$this->assertEqual($conditions, $query->conditions());
		$this->assertEqual($fields, $query->fields());
		$this->assertEqual($order, $query->order());
	}

	public function testRenderArrayJoin() {
		$model = 'lithium\tests\mocks\data\model\MockQueryComment';

		$query = new Query(compact('model') + array(
			'type' => 'read',
			'source' => 'comments',
			'alias' => 'Comment',
			'conditions' => array('Comment.id' => 1),
			'joins' => array(array(
				'type' => 'INNER',
				'source' => 'posts',
				'alias' => 'Post',
				'constraint' => array('Comment.post_id' => 'Post.id')
			))
		));

		$expected = "SELECT * FROM AS {Comment} INNER JOIN {posts} AS {Post} ON ";
		$expected .= "{Comment}.{post_id} = {Post}.{id} WHERE Comment.id = 1;";
		$result = Connections::get('mock-database-connection')->renderCommand($query);
		$this->assertEqual($expected, $result);
	}
}

?>