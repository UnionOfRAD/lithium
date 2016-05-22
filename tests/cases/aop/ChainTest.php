<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\aop;

use stdClass;
use lithium\aop\Chain;

class ChainTest extends \lithium\test\Unit {

	public function testAllFiltersAreTriggeredInOrder() {
		$message = null;

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $next) use (&$message) {
					$message .= '1';
					return $next($params);
				},
				function($params, $next) use (&$message) {
					$message .= '2';
					return $next($params);
				}
			)
		));
		$subject->run(array(), function($params) use (&$message) {
			$message .= '3';
		});
		$this->assertEqual('123', $message);
	}

	public function testNoNextStopsFurtherFilters() {
		$message = null;

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $next) use (&$message) {
					$message .= '1';
					return $next($params);
				},
				function($params, $next) use (&$message) {
					$message .= '2';
				}
			)
		));
		$subject->run(array(), function($params) use (&$message) {
			$message .= '3';
		});
		$this->assertEqual('12', $message);
	}

	public function testFilterWrappingInOut() {
		$message = null;

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $next) use (&$message) {
					$message .= ' 1BEFORE';
					$result = $next($params);
					$message .= ' 1AFTER';
				},
				function($params, $next) use (&$message) {
					$message .= ' 2BEFORE';
					$result = $next($params);
					$message .= ' 2AFTER';
				}
			)
		));
		$subject->run(array(), function($params) use (&$message) {
			$message .= ' 3BEFORE';
			$message .= ' 3AFTER';
		});
		$this->assertEqual(' 1BEFORE 2BEFORE 3BEFORE 3AFTER 2AFTER 1AFTER', $message);
	}

	public function testRunReturnsReturnValueFromImplementation() {
		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $next) {
					return $next($params);
				}
			)
		));
		$result = $subject->run(array(), function($params) {
			return 'foo';
		});
		$this->assertEqual('foo', $result);
	}

	public function testConsecutiveParamsManipulation() {
		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $next) {
					$params['message'] .= '1';
					return $next($params);
				},
				function($params, $next) {
					$params['message'] .= '2';
					return $next($params);
				}
			)
		));
		$result = $subject->run(array('message' => null), function($params) {
			$params['message'] .= '3';
			return $params['message'];
		});
		$this->assertEqual('123', $result);

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $next) {
					$params['message'] .= '1';
					return $next($params);
				},
				function($params, $next) {
					$params['message'] = null;
					return $next($params);
				}
			)
		));
		$result = $subject->run(array('message' => null), function($params) {
			$params['message'] .= '3';
			return $params['message'];
		});
		$this->assertEqual('3', $result);
	}

	public function testObjectInParamsKeepsRef() {
		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $next) {
					$params['object']->foo = 'bar';
					return $next($params);
				}
			)
		));

		$object = new stdClass();
		$originalHash = spl_object_hash($object);

		$result = $subject->run(array('object' => $object), function($params) {
			return $params['object'];
		});
		$resultHash = spl_object_hash($result);

		$this->assertEqual($originalHash, $resultHash);
	}

	/* Deprecated / BC */

	public function testLegacyFiltersBasicSignature() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($self, $params, $chain) {
					$params['body'] = compact('self', 'params', 'chain');
					return $chain->next($self, $params, $chain);
				}
			)
		));
		$result = $subject->run(array('foo' => 'bar'), function($params) {
			return $params['body'];
		});

		$this->assertEqual('Foo', $result['self']);
		$this->assertEqual(array('foo' => 'bar'), $result['params']);
		$this->assertInstanceOf('\lithium\aop\Chain', $result['chain']);

		error_reporting($original);
	}

	public function testLegacyFiltersParamsModificationWithLegacyNext() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($self, $params, $chain) {
					$params['foo'] .= 'baz';
					return $chain->next($self, $params, $chain);
				}
			)
		));
		$result = $subject->run(array('foo' => 'bar'), function($params) {
			return $params;
		});
		$this->assertEqual('barbaz', $result['foo']);

		error_reporting($original);
	}

	public function testAccessingMethodMethodInsideFilterWithStaticObject() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $chain) {
					$params['result'] = $chain->method();
					return $chain->next($params);
				}
			)
		));
		$result = $subject->run(array('result' => null), function($params) {
			return $params['result'];
		});
		$this->assertEqual('bar', $result);

		$subject = new Chain(array(
			'class' => 'Foo',
			'method' => 'bar',
			'filters' => array(
				function($params, $chain) {
					$params['result'] = $chain->method(true);
					return $chain->next($params);
				}
			)
		));
		$result = $subject->run(array('result' => null), function($params) {
			return $params['result'];
		});
		$this->assertEqual('Foo::bar', $result);

		error_reporting($original);
	}

	public function testAccessingMethodMethodInsideFilterWithInstance() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$subject = new Chain(array(
			'class' => new stdClass(),
			'method' => 'bar',
			'filters' => array(
				function($params, $chain) {
					$params['result'] = $chain->method();
					return $chain->next($params);
				}
			)
		));
		$result = $subject->run(array('result' => null), function($params) {
			return $params['result'];
		});
		$this->assertEqual('bar', $result);

		$subject = new Chain(array(
			'class' => new stdClass(),
			'method' => 'bar',
			'filters' => array(
				function($params, $chain) {
					$params['result'] = $chain->method(true);
					return $chain->next($params);
				}
			)
		));
		$result = $subject->run(array('result' => null), function($params) {
			return $params['result'];
		});
		$this->assertEqual('stdClass::bar', $result);

		error_reporting($original);
	}
}

?>