<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace lithium\tests\cases\data;

use \lithium\data\collection\DocumentSet;

class CollectionTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockQueryPost';

	public function testGetStats() {
		$collection = new DocumentSet(array('stats' => array('foo' => 'bar')));
		$this->assertNull($collection->stats('bar'));
		$this->assertEqual('bar', $collection->stats('foo'));
		$this->assertEqual(array('foo' => 'bar'), $collection->stats());
	}

	public function testInvalidData() {
		$this->expectException('Error creating new Collection instance; data format invalid.');
		$collection = new DocumentSet(array('data' => 'foo'));
	}

	public function testAccessorMethods() {
		$model = $this->_model;
		$model::config(array('connection' => false));
		$collection = new DocumentSet(compact('model'));
		$this->assertEqual($model, $collection->model());
		$this->assertEqual(compact('model'), $collection->meta());
	}
}

?>