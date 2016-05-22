<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\aop;

use lithium\aop\Filters;

class MockInstanceFiltered {

	protected $_internal = 'secret';

	public function method() {
		return Filters::run($this, __FUNCTION__, array(), function($params) {
			return 'method';
		});
	}

	public function methodTracing(array $trace = array()) {
		$trace[] = 'Starting outer method call';

		$result = Filters::run($this, __FUNCTION__, compact('trace'), function($params) {
			$params['trace'][] = 'Inside method implementation';
			return $params['trace'];
		});
		$result[] = 'Ending outer method call';
		return $result;
	}

	public function tamper() {
		return Filters::run($this, __FUNCTION__, array(), function() {
			$this->_internal = 'tampered';
			return true;
		});
	}

	public function internal() {
		return $this->_internal;
	}
}

?>