<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\Test;

class UnitTest extends \lithium\test\Unit {

	public function compare($type, $expected, $result = null) {
		return $this->_compare($type, $expected, $result);
	}

	/**
	 * @todo Figure out a way to expect failures
	 * @return void
	 */
	public function testBaseAssertions() {
		$this->assert(true);
		//$this->assert(false);
		$this->assertTrue(true);
		$this->assertFalse(false);
	}

	public function testCompare() {
		$expected = array('trace' => null, 'expected' => 'array', 'result' => 'string');
		$result = $this->compare('equal', array(), 'string');
		$this->assertEqual($expected, $result);
	}

	public function testAssertEqualNumeric() {
		$expected = array(1, 2, 3);
		$result = array(1, 2, 3);
		$this->assertEqual($expected, $result);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @todo See @todo above.
	 */
	public function testAssertEqualNumericFail() {
		$result = array(1, 2);
		$expected = array(1, 2, 3);
		//$this->assertEqual($expected, $result);
	}

	public function testAssertEqualAssociativeArray() {
		$expected = array(
			'expected' => 'array',
			'result' => 'string'
		);
		$result = array(
			'expected' => 'array',
			'result' => 'string'
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @todo See @todo above.
	 */
	public function testAssertEqualThreeDFail() {
		$result = array(
			array(array(1, 2), array(1)),
			array(array(1, 2), array(1))
		);
		$expected = array(
			array(array(1, 2), array(1, 2)),
			array(array(1, 2), array(1, 2))
		);
		//$this->assertEqual($expected, $result);
	}

	public function testAssertIdentical() {

	}

	public function testTestMethods() {
		$expected = array(
			'testBaseAssertions', 'testCompare', 'testAssertEqualNumeric',
			'testAssertEqualNumericFail', 'testAssertEqualAssociativeArray',
			'testAssertEqualThreeDFail', 'testAssertIdentical', 'testTestMethods'
		);
		$this->assertEqual($expected, $this->methods());
	}
}

?>