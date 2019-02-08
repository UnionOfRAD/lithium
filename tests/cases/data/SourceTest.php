<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data;

use lithium\tests\mocks\data\MockSource;

class SourceTest extends \lithium\test\Unit {

	public function testMethods() {
		$source = new MockSource(['autoConnect' => false]);
		$methods = $source->methods();
		$expected = [
			'connect', 'disconnect', 'sources', 'describe', 'create', 'read', 'update', 'delete',
			'schema', 'result', 'cast', 'relationship', 'calculation', '__construct', '__destruct',
			'_init', 'isConnected', 'name', 'methods', 'configureClass', 'applyStrategy',
			'applyFilter', 'invokeMethod', '__set_state', '_instance', '_filter', '_parents',
			'_stop'
		];
		$this->assertEqual(sort($expected), sort($methods));
	}

	public function testBaseMethods() {
		$source = new MockSource(['autoConnect' => true]);
		$name = '{(\'Li\':"∆")}';
		$this->assertEqual($name, $source->name($name));

		$expected = [
			'classes' => [
				'entity' => 'lithium\data\entity\Record',
				'set' => 'lithium\data\collection\RecordSet',
				'relationship' => 'lithium\data\model\Relationship',
				'schema' => 'lithium\data\Schema'
			],
			'meta' => ['locked' => true, 'key' => 'id']
		];
		$this->assertEqual($expected, $source->configureClass('Foo'));
	}

	public function testConnection() {
		$source = new MockSource(['autoConnect' => false]);
		$this->assertFalse($source->isConnected());
		$this->assertTrue($source->isConnected(['autoConnect' => true]));
		$this->assertTrue($source->isConnected());
	}
}

?>