<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\aop;

use lithium\aop\Filters;

class MockInstanceFiltered {

	protected $_internal = 'secret';

	public function method() {
		return Filters::run($this, __FUNCTION__, [], function($params) {
			return 'method';
		});
	}

	public function methodTracing(array $trace = []) {
		$trace[] = 'Starting outer method call';

		$result = Filters::run($this, __FUNCTION__, compact('trace'), function($params) {
			$params['trace'][] = 'Inside method implementation';
			return $params['trace'];
		});
		$result[] = 'Ending outer method call';
		return $result;
	}

	public function tamper() {
		return Filters::run($this, __FUNCTION__, [], function() {
			$this->_internal = 'tampered';
			return true;
		});
	}

	public function internal() {
		return $this->_internal;
	}
}

?>