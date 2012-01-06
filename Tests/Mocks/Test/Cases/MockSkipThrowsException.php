<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Test\Cases;

use Exception;

class MockSkipThrowsException extends \Lithium\Test\Unit {

	public function skip() {
		throw new Exception('skip throws exception');
	}
}

?>