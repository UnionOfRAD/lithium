<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\aop;

use lithium\aop\Filters;
use lithium\tests\mocks\aop\MockInstanceFiltered;
use lithium\tests\mocks\aop\MockStaticFiltered;
use lithium\tests\mocks\aop\MockStaticFilteredSubclass;

class FiltersTest extends \lithium\test\Unit {

	public function tearDown() {
		Filters::clear('foo\Bar');
		Filters::clear('lithium\tests\mocks\aop\MockStaticFiltered');
		Filters::clear('lithium\tests\mocks\aop\MockInstanceFiltered');
	}

	public function testApplyAndRun() {
		$params = array('message' => 'This ');

		Filters::apply('foo\Bar', __FUNCTION__, function($params, $next) {
			$params['message'] .= 'is a filter chain ';
			return $next($params);
		});
		Filters::apply('foo\Bar', __FUNCTION__, function($params, $next) {
			$params['message'] .= 'in a method ';
			return $next($params);
		});
		$result = Filters::run('foo\Bar', __FUNCTION__, $params, function($params) {
			return $params['message'] . 'of the ' . get_class($this) . ' class.';
		});

		$expected = 'This is a filter chain in a method of the';
		$expected .= ' lithium\tests\cases\aop\FiltersTest class.';
		$this->assertEqual($expected, $result);
	}

	public function testApplyWithLeadingNamespaceBackslash() {
		Filters::apply('\\' . 'foo\Bar', __FUNCTION__, function($params, $next) {
			return $next($params) . 'bar';
		});
		$result = Filters::run('foo\Bar', __FUNCTION__, array(), function($params) {
			return 'foo';
		});
		$this->assertEqual('foobar', $result);
	}

	public function testRunWithLeadingNamespaceBackslash() {
		Filters::apply('foo\Bar', __FUNCTION__, function($params, $next) {
			return $next($params) . 'bar';
		});
		$result = Filters::run('\\' . 'foo\Bar', __FUNCTION__, array(), function($params) {
			return 'foo';
		});
		$this->assertEqual('foobar', $result);
	}

	public function testChainCachingRunAgain() {
		$count = 0;

		Filters::apply('foo\Bar', __FUNCTION__, function($params, $next) use (&$count) {
			$count++;
			return $next($params);
		});
		Filters::run('foo\Bar', __FUNCTION__, array(), function() {});
		Filters::run('foo\Bar', __FUNCTION__, array(), function() {});
		Filters::run('foo\Bar', __FUNCTION__, array(), function() {});

		$this->assertEqual(3, $count);
	}

	public function testChainCachingRunAgainParamsDiffer() {
		Filters::apply('foo\Bar', __FUNCTION__, function($params, $next) {
			return $next($params);
		});
		$result = Filters::run('foo\Bar', __FUNCTION__, array('foo' => 'bar'), function($params) {
			return $params;
		});
		$this->assertEqual(array('foo' => 'bar'), $result);

		$result = Filters::run('foo\Bar', __FUNCTION__, array('foo' => 'baz'), function($params) {
			return $params;
		});
		$this->assertEqual(array('foo' => 'baz'), $result);

		$result = Filters::run('foo\Bar', __FUNCTION__, array('qux' => 'foo'), function($params) {
			return $params;
		});
		$this->assertEqual(array('qux' => 'foo'), $result);
	}

	public function testDirectImplementationCall() {
		$result = Filters::run('foo\Bar', __FUNCTION__, array('foo' => 'bar'), function() {
			return true;
		});
		$this->assertTrue($result);
	}

	/**
	 * Tests that calling a filter-able method with no filters added does not trigger an error.
	 */
	public function testNoFiltersOnFilterable() {
		$class = 'lithium\tests\mocks\aop\MockStaticFiltered';

		$result = MockStaticFiltered::method();
		$expected = 'method';
		$this->assertEqual($expected, $result);
	}

	public function testRunWithStatic() {
		$class = 'lithium\tests\mocks\aop\MockStaticFiltered';

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		$expected = 'method-filtered';
		$result = MockStaticFiltered::method();
		$this->assertEqual($expected, $result);
	}

	public function testRunWithStaticAndTracing() {
		$class = 'lithium\tests\mocks\aop\MockStaticFiltered';

		$result = MockStaticFiltered::methodTracing(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			"Inside method implementation of {$class}",
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		Filters::apply($class, 'methodTracing', function($params, $next) {
			$params['trace'][] = 'Starting filter';
			$result = $next($params);
			$result[] = 'Ending filter';
			return $result;
		});

		$result = $class::methodTracing(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			"Inside method implementation of {$class}",
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		Filters::apply($class, 'methodTracing', function($params, $next) {
			$params['trace'][] = 'Starting inner filter';
			$result = $next($params);
			$result[] = 'Ending inner filter';
			return $result;
		});
		$result = $class::methodTracing(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Starting inner filter',
			"Inside method implementation of {$class}",
			'Ending inner filter',
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);
	}

	public function testRunWithInstance() {
		$instance = new MockInstanceFiltered();

		Filters::apply($instance, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		$expected = 'method-filtered';
		$result = $instance->method();
		$this->assertEqual($expected, $result);

		Filters::clear($instance);
	}

	public function testRunWithInstanceAndTracing() {
		$instance = new MockInstanceFiltered();

		$result = $instance->methodTracing(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			"Inside method implementation",
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		Filters::apply($instance, 'methodTracing', function($params, $next) {
			$params['trace'][] = 'Starting filter';
			$result = $next($params);
			$result[] = 'Ending filter';
			return $result;
		});

		$result = $instance->methodTracing(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			"Inside method implementation",
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);

		Filters::apply($instance, 'methodTracing', function($params, $next) {
			$params['trace'][] = 'Starting inner filter';
			$result = $next($params);
			$result[] = 'Ending inner filter';
			return $result;
		});
		$result = $instance->methodTracing(array('Starting test'));
		$expected = array(
			'Starting test',
			'Starting outer method call',
			'Starting filter',
			'Starting inner filter',
			"Inside method implementation",
			'Ending inner filter',
			'Ending filter',
			'Ending outer method call'
		);
		$this->assertEqual($expected, $result);
	}

	public function testRunWithAllInstancesOf() {
		$class = 'lithium\tests\mocks\aop\MockInstanceFiltered';
		$instance = new MockInstanceFiltered();

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		$expected = 'method-filtered';
		$result = $instance->method();
		$this->assertEqual($expected, $result);

		Filters::clear($instance);
	}

	public function testRunWithMixedOnInstanceOrderIsInstanceThenAll() {
		$class = 'lithium\tests\mocks\aop\MockInstanceFiltered';
		$instance = new MockInstanceFiltered();

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-all';
		});
		Filters::apply($instance, 'method', function($params, $next) {
			return $next($params) . '-instance';
		});

		$expected = 'method-instance-all';
		$result = $instance->method();
		$this->assertEqual($expected, $result);

		Filters::clear($class);
		Filters::clear($instance);

		Filters::apply($instance, 'method', function($params, $next) {
			return $next($params) . '-instance';
		});
		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-all';
		});

		$expected = 'method-instance-all';
		$result = $instance->method();
		$this->assertEqual($expected, $result);

		Filters::clear($instance);
	}

	public function testNotUniqueFiltersPerClassMethod() {
		$filter = function($params, $next) {
			return $next($params) . '-filtered';
		};
		Filters::apply('lithium\tests\mocks\aop\MockStaticFiltered', 'method', $filter);
		Filters::apply('lithium\tests\mocks\aop\MockStaticFiltered', 'method', $filter);

		$expected = 'method-filtered-filtered';
		$result = MockStaticFiltered::method();
		$this->assertEqual($expected, $result);
	}

	public function testClearStatic() {
		$class = 'lithium\tests\mocks\aop\MockStaticFiltered';

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($class, 'method');

		$expected = 'method';
		$result = MockStaticFiltered::method();
		$this->assertEqual($expected, $result);

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($class);

		$expected = 'method';
		$result = MockStaticFiltered::method();
		$this->assertEqual($expected, $result);
	}

	public function testClearAnyInstance() {
		$class = 'lithium\tests\mocks\aop\MockInstanceFiltered';
		$instance = new MockInstanceFiltered();

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($class, 'method');

		$expected = 'method';
		$result = $instance->method();
		$this->assertEqual($expected, $result);

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($class);

		$expected = 'method';
		$result = $instance->method();
		$this->assertEqual($expected, $result);
	}

	public function testClearInstance() {
		$instance = new MockInstanceFiltered();

		Filters::apply($instance, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($instance, 'method');

		$expected = 'method';
		$result = $instance->method();
		$this->assertEqual($expected, $result);

		Filters::apply($instance, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($instance);

		$expected = 'method';
		$result = $instance->method();
		$this->assertEqual($expected, $result);
	}

	public function testClearAnyInstanceAlsoClearsInstance() {
		$class = 'lithium\tests\mocks\aop\MockInstanceFiltered';
		$instance = new MockInstanceFiltered();

		Filters::apply($instance, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($class);

		$expected = 'method';
		$result = $instance->method();
		$this->assertEqual($expected, $result);
	}

	public function testClearInstanceDoesNotClearAnyInstance() {
		$class = 'lithium\tests\mocks\aop\MockInstanceFiltered';
		$instance = new MockInstanceFiltered();

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($instance);

		$expected = 'method-filtered';
		$result = $instance->method();
		$this->assertEqual($expected, $result);
	}

	public function testClearStaticClassAndMethodDoesNotClearAllMethods() {
		$class = 'lithium\tests\mocks\aop\MockStaticFiltered';

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});
		Filters::apply($class, 'method2', function($params, $next) {
			return $next($params) . '-filtered';
		});

		Filters::clear($class, 'method');

		$expected = 'method';
		$result = $class::method();
		$this->assertEqual($expected, $result);

		$expected = 'method2-filtered';
		$result = $class::method2();
		$this->assertEqual($expected, $result);
	}

	public function testHasApplied() {
		$class = 'lithium\tests\mocks\aop\MockStaticFiltered';

		$result = Filters::hasApplied($class, 'method');
		$this->assertFalse($result);

		Filters::apply($class, 'method', function($params, $next) {
			return $next($params) . '-filtered';
		});

		$result = Filters::hasApplied($class, 'method');
		$this->assertTrue($result);
	}

	/**
	 * Tests that filtered methods in parent classes can call methods in subclasses.
	 */
	public function testCallingSubclassMethodsInFilteredMethods() {
		$this->assertEqual('Working', MockStaticFilteredSubclass::callSubclassMethod());
	}

	/**
	 * Verifies protected properties of an instance can be accesed and modified
	 * within a filtered method.
	 */
	public function testFilteringWithProtectedAccess() {
		$instance = new MockInstanceFiltered();

		$this->assertEqual($instance->internal(), 'secret');
		$this->assertTrue($instance->tamper());
		$this->assertEqual($instance->internal(), 'tampered');
	}
}

?>