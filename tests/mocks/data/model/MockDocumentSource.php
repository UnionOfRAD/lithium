<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model;

class MockDocumentSource extends \lithium\data\Source {

	protected $_classes = [
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
		'relationship' => 'lithium\data\model\Relationship',
		'schema' => 'lithium\data\source\mongo_db\Schema'
	];

	public function connect() {}
	public function disconnect() {}
	public function sources($class = null) {}
	public function describe($entity, $schema = [], array $meta = []) {
		return $this->_instance('schema');
	}
	public function create($query, array $options = []) {}
	public function update($query, array $options = []) {}
	public function delete($query, array $options = []) {}

	public $point = 0;
	public $result = null;

	public function read($query = null, array $options = []) {
		$this->point = 0;
		$this->result = [
			['id' => 1, 'name' => 'Joe'],
			['id' => 2, 'name' => 'Moe'],
			['id' => 3, 'name' => 'Roe']
		];
	}

	public function getNext() {
		return $this->result[$this->point++];
	}

	public function result($type, $resource, $context) {
		switch ($type) {
			case 'next':
				$result = $resource->hasNext() ? $resource->getNext() : null;
			break;
			case 'close':
				unset($resource);
				$result = null;
			break;
		}
		return $result;
	}

	public function relationship($class, $type, $name, array $options = []) {
		$key = Inflector::camelize($type === 'belongsTo' ? $name : $class::meta('name'));

		$options += compact('name', 'type', 'key');
		$options['from'] = $class;

		$relationship = $this->_classes['relationship'];
		return new $relationship($options);
	}
}

?>