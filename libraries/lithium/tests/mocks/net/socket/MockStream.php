<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\net\socket;

class MockStream extends \lithium\net\socket\Stream {

	public function resource() {
		return $this->_resource;
	}
}

?>