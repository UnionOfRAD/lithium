<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\collection;

use lithium\data\collection\DocumentArray;

class DocumentArrayTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\data\model\MockDocumentPost';

	public function testInitialCasting() {
		$array = new DocumentArray(array(
			'model' => $this->_model,
			'pathKey' => 'foo.bar',
			'data' => array('5', '6', '7')
		));
		foreach ($array as $value) {
			$this->assertTrue(is_int($value));
		}
	}

	public function testAddValueAndExport() {
		$array = new DocumentArray(array(
			'model' => $this->_model,
			'pathKey' => 'foo',
			'data' => array('bar')
		));
		$array[] = 'baz';

		$expected = array('bar', 'baz');
		$result = $array->data();
		$this->assertEqual($expected, $result);
	}

	public function testUnsetInForeach() {
		$data = array(
			'Hello',
			'Delete me',
			'Delete me',
			'Delete me',
			'Delete me',
			'Delete me',
			'Hello again!',
			'Delete me'
		);
		$doc = new DocumentArray(array('data' => $data));

		$this->assertIdentical($data, $doc->data());

		foreach ($doc as $i => $word) {
			if ($word == 'Delete me') {
				unset($doc->$i);
			}
		}

		$expected = array(0 => 'Hello', 6 => 'Hello again!');
		$this->assertIdentical($expected, $doc->data());
	}
}

?>