<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

class UnitTest extends \lithium\test\Unit {

	public function compare($type, $expected, $result = null) {
		return $this->_compare($type, $expected, $result);
	}

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
			'testAssertEqualThreeDFail', 'testAssertIdentical', 'testTestMethods',
			'testCleanUp', 'testCleanUpWithFullPath', 'testCleanUpWithRelativePath',
			'testSkipIf'
		);
		$this->assertEqual($expected, $this->methods());
	}

	public function testCleanUp() {
		$base = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		$this->assertTrue(mkdir("{$base}/cleanup_test"));
		$this->assertTrue(touch("{$base}/cleanup_test/file"));
		$this->assertTrue(touch("{$base}/cleanup_test/.hideme"));

		$this->_cleanUp();
		$this->assertFalse(file_exists("{$base}/cleanup_test"));
	}

	public function testCleanUpWithFullPath() {
		$base = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		$this->assertTrue(mkdir("{$base}/cleanup_test"));
		$this->assertTrue(touch("{$base}/cleanup_test/file"));
		$this->assertTrue(touch("{$base}/cleanup_test/.hideme"));

		$this->_cleanUp("{$base}/cleanup_test");
		$this->assertTrue(file_exists("{$base}/cleanup_test"));
		$this->assertFalse(file_exists("{$base}/cleanup_test/file"));
		$this->assertFalse(file_exists("{$base}/cleanup_test/.hideme"));

		$this->_cleanUp();
	}

	public function testCleanUpWithRelativePath() {
		$base = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		$this->assertTrue(mkdir("{$base}/cleanup_test"));
		$this->assertTrue(touch("{$base}/cleanup_test/file"));
		$this->assertTrue(touch("{$base}/cleanup_test/.hideme"));

		$this->_cleanUp("tests/cleanup_test");
		$this->assertTrue(file_exists("{$base}/cleanup_test"));
		$this->assertFalse(file_exists("{$base}/cleanup_test/file"));
		$this->assertFalse(file_exists("{$base}/cleanup_test/.hideme"));

		$this->_cleanUp();
	}

	public function testSkipIf() {
		try {
			$this->skipIf(true, 'skip me');
		} catch (\Exception $e) {
			$result = $e->getMessage();
		}
		$expected = 'skip me';
		$this->assertEqual($expected, $result);
	}
}

?>