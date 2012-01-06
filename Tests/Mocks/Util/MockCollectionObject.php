<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Util;

class MockCollectionObject extends \Lithium\Core\Object {

	public $data = array(1 => 2);

	public function testFoo() {
		return 'testFoo';
	}

	public function to($format, array $options = array()) {
		switch ($format) {
			case 'array':
				return $this->data + array(2 => 3);
		}
	}
}

?>