<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Analysis;

class MockLoggerAdapter extends \Lithium\Core\Object {

	public function write($name, $value) {
		return function($self, $params, $chain) {
			return true;
		};
	}
}

?>