<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Data;

use Lithium\Data\Entity;
use Lithium\Tests\Mocks\Data\MockSource;

class SourceTest extends \Lithium\Test\Unit {

	public function testMethods() {
		$source = new MockSource(array('autoConnect' => false));
		$methods = $source->methods();
		$expected = array(
			'connect', 'disconnect', 'sources', 'describe', 'create', 'read', 'update', 'delete',
			'schema', 'result', 'cast', 'relationship', 'calculation', '__construct', '__destruct',
			'_init', 'isConnected', 'name', 'methods', 'configureClass', 'item', 'applyFilter',
			'invokeMethod', '__set_state', '_instance', '_filter', '_parents', '_stop'
		);
		$this->assertEqual($expected, $methods);
	}

	public function testBaseMethods() {
		$source = new MockSource(array('autoConnect' => true));
		$name = '{(\'Li\':"∆")}';
		$this->assertEqual($name, $source->name($name));

		$expected = array('meta' => array('locked' => true, 'key' => 'id'));
		$this->assertEqual($expected, $source->configureClass('Foo'));
	}

	public function testConnection() {
		$source = new MockSource(array('autoConnect' => false));
		$this->assertFalse($source->isConnected());
		$this->assertTrue($source->isConnected(array('autoConnect' => true)));
		$this->assertTrue($source->isConnected());
	}

	public function testItem() {
		$source = new MockSource();
		$entity = $source->item('Foo', array('foo' => 'bar'));
		$this->assertTrue($entity instanceof Entity);
		$this->assertEqual('Foo', $entity->model());
		$this->assertEqual(array('foo' => 'bar'), $entity->data());
	}
}

?>