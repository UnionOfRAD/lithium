<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\util\collection;

use lithium\util\collection\Filters;
use lithium\aop\Filters as NewFilters;

/**
 * Filters Test
 *
 * @deprecated
 */
class FiltersTest extends \lithium\test\Unit {

	public function tearDown() {
		NewFilters::clear('lithium\tests\mocks\util\MockFilters');
		NewFilters::clear('foo\Bar');
	}

	public function testRun() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$options = ['method' => __FUNCTION__, 'class' => __CLASS__, 'data' => [
			function($self, $params, $chain) {
				$params['message'] .= 'is a filter chain ';
				return $chain->next($self, $params, $chain);
			},
			function($self, $params, $chain) {
				$params['message'] .= 'in the ' . $chain->method() . ' method ';
				return $chain->next($self, $params, $chain);
			},
			function($self, $params, $chain) {
				return $params['message'] . 'of the ' . $self . ' class.';
			}
		]];
		$result = Filters::run('foo\Bar', ['message' => 'This '], $options);
		$expected = 'This is a filter chain in the testRun method of the';
		$expected .= ' foo\Bar class.';
		$this->assertEqual($expected, $result);

		error_reporting($original);
	}

	public function testRunWithoutChain() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$options = ['method' => __FUNCTION__, 'class' => __CLASS__, 'data' => [
			function($self, $params, $chain) {
				return $chain->next($self, $params, null);
			},
			function() {
				return 'This is a filter chain that calls $chain->next() without the $chain argument.';
			}
		]];
		$result = Filters::run('foo\Bar', [], $options);
		$expected = 'This is a filter chain that calls $chain->next() without the $chain argument.';
		$this->assertEqual($expected, $result);

		error_reporting($original);
	}

	public function testLazyApply() {
		error_reporting(($original = error_reporting()) & ~E_USER_DEPRECATED);

		$class = 'lithium\tests\mocks\util\MockFilters';

		Filters::apply($class, 'filteredMethod', function($self, $params, $chain) {
			return md5($chain->next($self, $params, $chain));
		});

		$expected = md5('Working?');
		$result = $class::filteredMethod();
		$this->assertEqual($expected, $result);

		Filters::apply($class, 'filteredMethod', function($self, $params, $chain) {
			return sha1($chain->next($self, $params, $chain));
		});

		$expected = md5(sha1('Working?'));
		$result = $class::filteredMethod();
		$this->assertEqual($expected, $result);

		error_reporting($original);
	}
}

?>