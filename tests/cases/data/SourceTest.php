<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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