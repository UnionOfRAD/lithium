<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

/**
 * @deprecated
 */
class MockMethodFiltering extends \lithium\core\Object {

	public function method($data) {
		$data[] = 'Starting outer method call';
		$result = $this->_filter(__METHOD__, compact('data'), function($self, $params, $chain) {
			$params['data'][] = 'Inside method implementation';
			return $params['data'];
		});
		$result[] = 'Ending outer method call';
		return $result;
	}

	public function method2() {
		$filters =& $this->_methodFilters;
		$method = function($self, $params, $chain) use (&$filters) {
			return $filters;
		};
		return $this->_filter(__METHOD__, [], $method);
	}

	public function manual($filters) {
		$method = function($self, $params, $chain) {
			return "Working";
		};
		return $this->_filter(__METHOD__, [], $method, $filters);
	}

}

?>