<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\storage\session\strategy;

class MockEncrypt extends \lithium\storage\session\strategy\Encrypt {
	public function decrypt($encrypted) {
		return parent::_decrypt($encrypted);
	}
}

?>
