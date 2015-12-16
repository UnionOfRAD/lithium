<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\analysis;

use lithium\core\Filterable;

class MockLoggerAdapter extends \lithium\core\Object {
	use Filterable;

	public function write($name, $value) {
		return function($self, $params, $chain) {
			return true;
		};
	}
}

?>