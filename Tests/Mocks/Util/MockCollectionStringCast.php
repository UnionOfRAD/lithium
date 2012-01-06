<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Util;

class MockCollectionStringCast {
	protected $data = array(1 => 2, 2 => 3);

	public function __toString() {
		return json_encode($this->data);
	}
}

?>