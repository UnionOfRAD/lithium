<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\util\socket;

class MockCurl extends \lithium\util\socket\Curl {

	public function resource() {
		return $this->_resource;
	}
}


?>