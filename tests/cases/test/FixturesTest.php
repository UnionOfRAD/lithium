<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test;

use lithium\tests\mocks\core\MockLogCall;
use lithium\test\Fixtures;

class FixturesTest extends \lithium\test\Unit {

	protected $_callable;

	public function setUp() {
		Fixtures::reset();
		$this->_callable = new MockLogCall();
		Fixtures::config([
			'fixture_test' => [
				'object' => $this->_callable,
				'fixtures' => [
					'image' => 'name\spa\ce',
					'gallery' => 'na\mespac\e',
				]
			]
		]);
	}

	public function tearDown() {
		$this->_callable->__clear();
		Fixtures::reset();
	}

	public function testConstructPassedParams() {
		Fixtures::reset();
		$config = [
			'adapter' => 'lithium\tests\mocks\core\MockLogCall',
			'fixtures' => [
				'image' => 'name\spa\ce',
				'gallery' => 'na\mespac\e',
			]
		];
		Fixtures::config([
			'fixture_test' => $config
		]);
		$callable = Fixtures::adapter('fixture_test');
		$expected = $config;
		$this->assertEqual($expected, $callable->construct[0]);
	}

	public function testCallStatic() {
		$result = Fixtures::methodName('fixture_test', ['parameter' => 'value'], 'param');
		$expected = [
			'method' => 'methodName',
			'params' => [
				[
					'parameter' => 'value',
				],
				'param'
			]
		];
		$this->assertEqual($expected, $this->_callable->call[0]);
	}
}

?>