<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\model\Query;
use \lithium\data\source\Database;

class MyDatabasePost extends \lithium\data\Model {

	public $hasMany = array('MyDatabaseComment');

	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'title' => array('type' => 'string'),
		'created' => array('type' => 'datetime')
	);
}

class MyDatabaseComment extends \lithium\data\Model {

	public $belongsTo = array('MyDatabasePost');

	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'post_id' => array('type' => 'integer'),
		'author_id' => array('type' => 'integer'),
		'body' => array('type' => 'text'),
		'created' => array('type' => 'datetime')
	);
}

class MyDatabase extends Database {

	public function connect() {
	}

	public function disconnect() {}

	public function entities($class = null) {}

	public function encoding($encoding = null) {}

	public function result($type, $resource, $context) {}

	public function describe($entity, $meta = array()) {}
}

class DatabaseTest extends \lithium\test\Unit {

	public $db = null;

	public function setUp() {
		$this->db = new MyDatabase();
		MyDatabasePost::__init();
		MyDatabaseComment::__init();
	}

	public function testColumnMapping() {
		$ns = __NAMESPACE__;

		$result = $this->db->columns(new Query(array('model' =>  "{$ns}\MyDatabasePost")));
		$expected = array("{$ns}\MyDatabasePost" => array('id', 'title', 'created'));
		$this->assertEqual($expected, $result);

		$query = new Query(array('model' =>  "{$ns}\MyDatabasePost", 'fields' => array('*')));
		$result = $this->db->columns($query);
		$this->assertEqual($expected, $result);

		$fields = array('MyDatabaseComment');
		$query = new Query(array('model' => "{$ns}\MyDatabasePost", 'fields' => $fields));
		$result = $this->db->columns($query);
		$expected = array("{$ns}\MyDatabaseComment" => array_keys(MyDatabaseComment::schema()));
		$this->assertEqual($expected, $result);
	}
}

?>