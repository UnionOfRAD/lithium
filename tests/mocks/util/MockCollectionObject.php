<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\util;

use lithium\core\Filterable;

class MockCollectionObject extends \lithium\core\Object {
	use Filterable;

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