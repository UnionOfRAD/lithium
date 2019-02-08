<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data;

use lithium\util\Inflector;

class MockSource extends \lithium\data\Source {

	protected $_classes = [
		'entity' => 'lithium\data\entity\Record',
		'set' => 'lithium\data\collection\RecordSet',
		'relationship' => 'lithium\data\model\Relationship',
		'schema' => 'lithium\data\Schema'
	];

	protected $_mockPosts = [
		'id' => ['type' => 'int', 'length' => '10', 'null' => false, 'default' => null],
		'user_id' => [
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => null
		],
		'title' => [
			'type' => 'varchar', 'length' => '255', 'null' => true, 'default' => null
		],
		'body' => [
			'type' => 'text', 'length' => null, 'null' => true, 'default' => null
		],
		'created' => [
			'type' => 'datetime', 'length' => null, 'null' => true, 'default' => null
		],
		'modified' => [
			'type' => 'datetime', 'length' => null, 'null' => true, 'default' => null
		],
		'status' => [
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => '0'
		]
	];

	protected $_mockComments = [
		'id' => [
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		],
		'comment_type_id' => [
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		],
		'article_id' => [
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		],
		'comment_id' => [
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		],
		'user_id' => [
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		],
		'created' => [
			'type' => 'datetime', 'length' => null, 'null' => false, 'default' => null
		],
		'body' => [
			'type' => 'text', 'length' => null, 'null' => false, 'default' => null
		],
		'subscribed' => [
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => null
		],
		'published' => [
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => null
		]
	];

	protected $_mockTags = [
		'id' => [
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		],
		'linked' => [
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => null
		],
		'name' => [
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => null
		],
		'keyname' => [
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => null
		]
	];

	protected $_postsTags = [
		'id' => ['type' => 'int'],
		'post_id' => ['type' => 'int'],
		'tag_id' => ['type' => 'int'],
	];

	protected $_mockCreators = [
		'id' => ['type' => 'int'],
		'name' => [
			'default' => 'Moe',
			'type' => 'string',
			'null' => false
		],
		'sign' => [
			'default' => 'bar',
			'type' => 'string',
			'null' => false
		],
		'age' => [
			'default' => 0,
			'type' => 'number',
			'null' => false
		]
	];

	public function connect() {
		return ($this->_isConnected = true);
	}

	public function disconnect() {
		return !($this->_isConnected = false);
	}

	public function sources($class = null) {
		return ['mock_posts', 'mock_comments', 'mock_tags', 'posts_tags'];
	}

	public function describe($entity, $schema = [], array $meta = []) {
		$source = '_' . Inflector::camelize($entity, false);
		$fields = isset($this->$source) ? $this->$source : [];
		return $this->_instance('schema', compact('fields'));
	}

	public function create($query, array $options = []) {
		return compact('query', 'options');
	}

	public function read($query, array $options = []) {
		return compact('query', 'options');
	}

	public function update($query, array $options = []) {
		return compact('query', 'options');
	}

	public function delete($query, array $options = []) {
		return compact('query', 'options');
	}

	public function schema($query, $resource = null, $context = null) {

	}

	public function result($type, $resource, $context) {

	}

	public function relationship($class, $type, $name, array $config = []) {
		$field = Inflector::underscore(Inflector::singularize($name));
		$key = "{$field}_id";
		$primary = $class::meta('key');

		if (is_array($primary)) {
			$key = array_combine($primary, $primary);
		} elseif ($type === 'hasMany' || $type === 'hasOne') {
			if ($type === 'hasMany') {
				$field = Inflector::pluralize($field);
			}
			$secondary = Inflector::underscore(Inflector::singularize($class::meta('name')));
			$key = [$primary => "{$secondary}_id"];
		}

		$from = $class;
		$fieldName = $field;
		$config += compact('type', 'name', 'key', 'from', 'fieldName');
		return $this->_instance('relationship', $config);
	}

	public function calculation($type, $query, array $options = []) {
		$query->calculate($type);
		return compact('query', 'options');
	}

	public static function enabled($feature = null) {
		if (!$feature) {
			return true;
		}
		$features = [
			'arrays' => false,
			'transactions' => true,
			'booleans' => true,
			'schema' => true,
			'relationships' => true,
			'sources' => true
		];
		return isset($features[$feature]) ? $features[$feature] : null;
	}
}

?>