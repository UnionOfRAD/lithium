<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockSource extends \lithium\data\Source {

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public function entities($class = null) {
		return array('mock_posts', 'mock_comments', 'mock_tags', 'posts_tags');
	}

	public function describe($entity, $meta = array()) {
		$var = "__{$entity}";
		if ($this->{$var}) {
			return $this->{$var};
		}
		return array();
	}

	public function create($query, $options) {
		return array($query => $options);
	}

	public function read($query, $options) {
		return compact('query', 'options');
	}

	public function update($query, $options) {
		return compact('query', 'options');
	}

	public function delete($query, $options) {
		return compact('query', 'options');
	}

	public function columns($query, $resource = null, $context = null) {

	}

	public function result($type, $resource, $context) {

	}

	private $__mock_posts = array(
		'id' => array('type' => 'int', 'length' => '10', 'null' => false, 'default' => NULL),
		'user_id' => array(
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => NULL
		),
		'title' => array(
			'type' => 'varchar', 'length' => '255', 'null' => true, 'default' => NULL
		),
		'body' => array(
			'type' => 'text', 'length' => NULL, 'null' => true, 'default' => NULL
		),
		'created' => array(
			'type' => 'datetime', 'length' => NULL, 'null' => true, 'default' => NULL
		),
		'modified' => array(
			'type' => 'datetime', 'length' => NULL, 'null' => true, 'default' => NULL
		),
		'status' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => '0'
		)
	);

	private $__mock_comments = array(
		'id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => NULL,
		),
		'comment_type_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => NULL,
		),
		'article_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => NULL,
		),
		'comment_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => NULL,
		),
		'user_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => NULL,
		),
		'created' => array(
			'type' => 'datetime', 'length' => NULL, 'null' => false, 'default' => NULL,
		),
		'body' => array(
			'type' => 'text', 'length' => NULL, 'null' => false, 'default' => NULL,
		),
		'subscribed' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => NULL,
		),
		'published' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => NULL,
		),
	);

	private $__mock_tags = array(
		'id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => NULL,
		),
		'linked' => array(
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => NULL,
		),
		'name' => array(
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => NULL,
		),
		'keyname' => array(
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => NULL,
		),
	);
}

?>