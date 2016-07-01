<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\test;

class MockUnitTest extends \lithium\test\Unit {

	public function testNothing() {
		$this->assertTrue(true);
	}

	/**
	 * This method is used in a test and is *line sensitive*. The corresponding
	 * test's expectations needs to be adapted if the line of the `assert()`
	 * call changes.
	 *
	 * @see lithium\tests\cases\test\UnitTest::testAssertBacktraces()
	 */
	public function testSomething() {
		$this->assert(true);
	}

	/**
	 * This method is used in a test to prepare it.
	 *
	 * @see lithium\tests\cases\test\UnitTest::testExpectExceptionNotThrown()
	 */
	public function prepareTestExpectExceptionNotThrown() {
		$this->expectException('test');
	}

	public function compare($type, $expected, $result = null, $trace = null) {
		return parent::_compare($type, $expected, $result, $trace);
	}

	public function handleException($exception, $lineFlag = null) {
		return parent::_handleException($exception, $lineFlag);
	}

	public function expected() {
		return $this->_expected;
	}
}

?>