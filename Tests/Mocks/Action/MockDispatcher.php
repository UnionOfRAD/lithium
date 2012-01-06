<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Action;

use stdClass;

class MockDispatcher extends \Lithium\Action\Dispatcher {

	public static $dispatched = array();

	protected static function _callable($request, $params, $options) {
		$callable = new stdClass();
		$callable->params = $params;
		return $callable;
	}

	protected static function _call($callable, $request, $params) {
		if (is_callable($callable->params['controller'])) {
			return parent::_call($callable->params['controller'], $request, $params);
		}
		static::$dispatched[] = $callable;
	}
}

?>