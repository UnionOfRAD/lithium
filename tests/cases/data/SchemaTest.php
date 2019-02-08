<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data;

use lithium\data\Schema;

class SchemaTest extends \lithium\test\Unit {

	public function testShortHandTypeDefinitions() {
		$schema = new Schema(['fields' => [
			'id' => 'int',
			'name' => 'string',
			'active' => ['type' => 'boolean', 'default' => true]
		]]);

		$this->assertEqual('int', $schema->type('id'));
		$this->assertEqual('string', $schema->type('name'));
		$this->assertEqual('boolean', $schema->type('active'));
		$this->assertEqual(['type' => 'int'], $schema->fields('id'));
		$this->assertEqual(['id', 'name', 'active'], $schema->names());

		$expected = [
			'id' => ['type' => 'int'],
			'name' => ['type' => 'string'],
			'active' => ['type' => 'boolean', 'default' => true]
		];
		$this->assertEqual($expected, $schema->fields());
	}
}

?>