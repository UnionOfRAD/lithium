<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\util;

/**
 * Mock Filters Class
 *
 * @deprecated In use by deprecated test.
 */
class MockFilters extends \lithium\core\StaticObject {

	public static function filteredMethod() {
		return static::_filter(__FUNCTION__, array(), function($params) {
			return 'Working?';
		});
	}
}

?>