<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source;

/**
 * The `Mock` data source is used behind-the-scenes when a model does not use a backend data source.
 * It implements the necessary methods, but does not support querying and has no storage backend.
 * It can create generic entities for use in forms and elsewhere within the framework. This allows
 * developers to create domain objects with business logic and schemas, without worrying about
 * backend storage.
 */
class Mock extends \lithium\data\Source {

	protected $_classes = [
		'entity' => 'lithium\data\Entity',
		'set' => 'lithium\data\Collection',
		'relationship' => 'lithium\data\model\Relationship',
		'schema' => 'lithium\data\Schema'
	];

	public function connect() {
		return true;
	}

	public function disconnect() {
		return true;
	}

	public static function enabled($feature = null) {
		return false;
	}

	public function sources($class = null) {
		return [];
	}

	public function describe($entity, $fields = [], array $meta = []) {
		return $this->_instance('schema', compact('fields'));
	}

	public function relationship($class, $type, $name, array $options = []) {
		return false;
	}

	public function create($query, array $options = []) {
		return false;
	}

	public function read($query, array $options = []) {
		return false;
	}

	public function update($query, array $options = []) {
		return false;
	}

	public function delete($query, array $options = []) {
		return false;
	}
}

?>