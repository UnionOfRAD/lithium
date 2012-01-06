<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Core;

class MockMethodFiltering extends \Lithium\Core\Object {

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
		return $this->_filter(__METHOD__, array(), $method);
	}
}

?>