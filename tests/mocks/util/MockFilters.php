<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\util;

/**
 * Mock Filters Class
 *
 * @deprecated In use by deprecated test.
 */
class MockFilters extends \lithium\core\StaticObject {

	public static function filteredMethod() {
		return static::_filter(__FUNCTION__, [], function($params) {
			return 'Working?';
		});
	}
}

?>