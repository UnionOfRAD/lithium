<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

use lithium\core\Filterable;

class MockObjectForParents extends \lithium\core\Object {
	use Filterable;

	public static function parents() {
		return static::_parents();
	}
}

?>