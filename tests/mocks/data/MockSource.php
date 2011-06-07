<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

use lithium\util\Inflector;

class MockSource extends \lithium\data\Source {

	protected $_classes = array(
		'entity' => 'lithium\data\entity\Record',
		'set' => 'lithium\data\collection\RecordSet',
		'relationship' => 'lithium\data\model\Relationship'
	);

	private $_mockPosts = array(
		'id' => array('type' => 'int', 'length' => '10', 'null' => false, 'default' => null),
		'user_id' => array(
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => null
		),
		'title' => array(
			'type' => 'varchar', 'length' => '255', 'null' => true, 'default' => null
		),
		'body' => array(
			'type' => 'text', 'length' => null, 'null' => true, 'default' => null
		),
		'created' => array(
			'type' => 'datetime', 'length' => null, 'null' => true, 'default' => null
		),
		'modified' => array(
			'type' => 'datetime', 'length' => null, 'null' => true, 'default' => null
		),
		'status' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => '0'
		)
	);

	private $_mockComments = array(
		'id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'comment_type_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'article_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'comment_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'user_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'created' => array(
			'type' => 'datetime', 'length' => null, 'null' => false, 'default' => null
		),
		'body' => array(
			'type' => 'text', 'length' => null, 'null' => false, 'default' => null
		),
		'subscribed' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => null
		),
		'published' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => null
		)
	);

	private $_mockTags = array(
		'id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'linked' => array(
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => null
		),
		'name' => array(
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => null
		),
		'keyname' => array(
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => null
		)
	);

	public function connect() {
		return ($this->_isConnected = true);
	}

	public function disconnect() {
		return !($this->_isConnected = false);
	}

	public function entities($class = null) {
		return array('mock_posts', 'mock_comments', 'mock_tags', 'posts_tags');
	}

	public function describe($entity, array $meta = array()) {
		$var = "_" . Inflector::camelize($entity, false);
		if ($this->{$var}) {
			return $this->{$var};
		}
		return array();
	}

	public function create($query, array $options = array()) {
		return compact('query', 'options');
	}

	public function read($query, array $options = array()) {
		return compact('query', 'options');
	}

	public function update($query, array $options = array()) {
		return compact('query', 'options');
	}

	public function delete($query, array $options = array()) {
		return compact('query', 'options');
	}

	public function schema($query, $resource = null, $context = null) {

	}

	public function result($type, $resource, $context) {

	}

	public function cast($entity, array $data = array(), array $options = array()) {
		$defaults = array('first' => false);
		$options += $defaults;
		return $options['first'] ? reset($data) : $data;
	}

	public function relationship($class, $type, $name, array $options = array()) {
		$keys = Inflector::underscore($type == 'belongsTo' ? $name : $class::meta('name')) . '_id';

		$options += compact('name', 'type', 'keys');
		$options['from'] = $class;

		$relationship = $this->_classes['relationship'];
		return new $relationship($options);
	}

	public function calculation($type, $query, array $options = array()) {
		$query->calculate($type);
		return compact('query', 'options');
	}
}

?>