<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Util;

class MockFilters extends \Lithium\Core\StaticObject {

	public static function filteredMethod() {
		return static::_filter(__FUNCTION__, array(), function($self, $params) {
			return 'Working?';
		});
	}
}

?>