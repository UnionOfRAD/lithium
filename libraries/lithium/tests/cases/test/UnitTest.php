<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use lithium\tests\mocks\test\MockUnitTest;
use \Exception;

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

	public function testCompareIsEqual() {
		$expected = true;
		$result = $this->compare('equal', 'string', 'string');
		$this->assertEqual($expected, $result);
	}

	public function testCompareIsIdentical() {
		$expected = true;
		$result = $this->compare('identical', 'string', 'string');
		$this->assertEqual($expected, $result);
	}

	public function testCompareTypes() {
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
		$expected = array(1, 2, 3);
		$result = array(1, 2);
		$this->assertEqual($expected, $result);

		$expected = array(
			'result' => 'fail', 'file' => __FILE__, 'line' => 54,
			'method' => 'testAssertEqualNumericFail', 'assertion' => 'assertEqual',
			'class' => __CLASS__, 'message' =>
				"trace: [2]\nexpected: array (\n  0 => 1,\n  1 => 2,\n  2 => 3,\n)\n"
				. "result: array (\n  0 => 1,\n  1 => 2,\n)\n",
			'data' => array(
				'trace' => '[2]',
				'expected' => array(
				  0 => 1,
				  1 => 2,
				  2 => 3,
				),
				'result' => array(
				  0 => 1,
				  1 => 2,
				)
			)
		);
		$result = $this->_results[7];
		unset($this->_results[7]);
		$this->assertEqual($expected, $result);
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

	public function testAssertEqualThreeDFail() {
		$expected = array(
			array(array(1, 2), array(1, 2)),
			array(array(1, 2), array(1, 2))
		);
		$result = array(
			array(array(1, 2), array(1)),
			array(array(1, 2), array(1))
		);
		$this->assertEqual($expected, $result);

		$expected = array(
			'result' => 'fail', 'file' => __FILE__, 'line' => 101,
			'method' => 'testAssertEqualThreeDFail', 'assertion' => 'assertEqual',
			'class' => __CLASS__, 'message' =>
				"trace: [0][1][1]\nexpected: array (\n  0 => 1,\n  1 => 2,\n)\n"
				. "result: array (\n  0 => 1,\n)\n"
				. "trace: [1][1][1]\nexpected: array (\n  0 => 1,\n  1 => 2,\n)\n"
				. "result: array (\n  0 => 1,\n)\n",
			'data' => array(
				array(
					array(
						'trace' => '[0][1][1]',
						'expected' => array(
						  0 => 1,
						  1 => 2,
						),
						'result' => array(
						  0 => 1,
						)
					),
				),
				array(
					array('trace' => '[1][1][1]',
						'expected' => array(
						  0 => 1,
						  1 => 2,
						),
						'result' => array(
						  0 => 1,
						)
					)
				)
			)
		);
		$result = $this->_results[10];
		unset($this->_results[10]);
		$this->assertEqual($expected, $result);
	}

	public function testAssertWithCustomMessage() {
		$expected = false;
		$result = true;
		$this->assertEqual($expected, $result, 'Custom Message Test');

		$expected = 'Custom Message Test';
		$result = $this->_results[12];
		unset($this->_results[12]);
		$this->assertEqual($expected, $result['message']);
	}

	public function testTestMethods() {
		$expected = array(
			'testBaseAssertions', 'testCompareIsEqual', 'testCompareIsIdentical',
			'testCompareTypes', 'testAssertEqualNumeric',
			'testAssertEqualNumericFail', 'testAssertEqualAssociativeArray',
			'testAssertEqualThreeDFail', 'testAssertWithCustomMessage', 'testTestMethods',
			'testSubject', 'testRun', 'testAssertNotEqual', 'testAssertIdentical',
			'testAssertNull', 'testAssertNoPattern', 'testAssertPattern', 'testAssertTags',
			'testAssertTagsNoClosingTag', 'testAssertTagsMissingAttribute',
			'testIdenticalArrayFail',
			'testCleanUp', 'testCleanUpWithFullPath', 'testCleanUpWithRelativePath',
			'testSkipIf', 'testExpectException', 'testHandleException', 'testResults',
			'testGetTest', 'testAssertCookie'
		);
		$this->assertIdentical($expected, $this->methods());
	}

	public function testSubject() {
		$test = new MockUnitTest();
		$expected = 'lithium\\tests\\mocks\\test\\MockUnit';
		$result = $test->subject();
		$this->assertEqual($expected, $result);
	}

	public function testRun() {
		$test = new MockUnitTest();
		$expected = array(
			'result' => 'pass',
			'file' => LITHIUM_LIBRARY_PATH . '/lithium/tests/mocks/test/MockUnitTest.php',
			'line' => 14,
			'method' => 'testNothing',
			'assertion' => 'assertTrue',
			'class' => 'lithium\\tests\\mocks\\test\\MockUnitTest',
			'message' => "expected: true\nresult: true\n",
			'data' => array('expected' => true, 'result' => true)
		);
		$result = $test->run();
		$this->assertEqual($expected, $result[0]);
	}

	public function testAssertNotEqual() {
		$expected = true;
		$result = true;
		$this->assertNotEqual($expected, $result);

		$expected = 'fail';
		$result = $this->_results[17];
		unset($this->_results[17]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertNotEqual';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertNotEqual';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertIdentical() {
		$expected = true;
		$result = 1;
		$this->assertIdentical($expected, $result);

		$expected = 'fail';
		$result = $this->_results[21];
		unset($this->_results[21]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertIdentical';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertIdentical';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertNull() {
		$expected = null;
		$result = null;
		$this->assertNull($expected, $result);

		$expected = 'pass';
		$result = $this->_results[25];
		unset($this->_results[25]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertNull';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertNull';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertNoPattern() {
		$expected = '/\s/';
		$result = null;
		$this->assertNoPattern($expected, $result);

		$expected = 'pass';
		$result = $this->_results[29];
		unset($this->_results[29]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertNoPattern';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertNoPattern';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertPattern() {
		$expected = '/\s/';
		$result = ' ';
		$this->assertPattern($expected, $result);

		$expected = 'pass';
		$result = $this->_results[33];
		unset($this->_results[33]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertPattern';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertPattern';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertTags() {
		$result = '<input id="test">';
		$this->assertTags($result, array(
			'input' => array('id' => 'test')
		));

		$expected = 'pass';
		$result = $this->_results[37];
		unset($this->_results[37]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertTags';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertTags';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertTagsNoClosingTag() {
		$result = '<span id="test">';
		$this->assertTags($result, array(
			'span' => array('id' => 'test'), '/span'
		));

		$expected = 'fail';
		$result = $this->_results[41];
		unset($this->_results[41]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertTagsNoClosingTag';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertTags';
		$this->assertEqual($expected, $result['assertion']);

		$expected = '- Item #2 / regex #3 failed: Close span tag';
		$this->assertEqual($expected, $result['message']);
	}

	public function testAssertTagsMissingAttribute() {
		$result = '<span></span>';
		$this->assertTags($result, array(
			'span' => array('id' => 'test'), '/span'
		));

		$expected = 'fail';
		$result = $this->_results[46];
		unset($this->_results[46]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertTagsMissingAttribute';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertTags';
		$this->assertEqual($expected, $result['assertion']);

		$expected = '- Item #1 / regex #1 failed: Attribute "id" == "test"';
		$this->assertEqual($expected, $result['message']);
	}

	public function testIdenticalArrayFail() {
		$expected = array('1', '2', '3');
		$result = array(1, '2', '3');;
		$this->assertIdentical($expected, $result);

		$expected = 'fail';
		$result = $this->_results[51];
		unset($this->_results[51]);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testIdenticalArrayFail';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertIdentical';
		$this->assertEqual($expected, $result['assertion']);

		$expected = "trace: [0]\nexpected: 'string'\nresult: 'integer'\n";
		$this->assertEqual($expected, $result['message']);
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

	public function testExpectException() {
		$this->expectException('test expected exception');
		$expected = 'test expected exception';
		$result = $this->_expected[0];
		unset($this->_expected[0]);
		$this->assertEqual($expected, $result);
	}

	public function testHandleException() {
		$this->_handleException(new Exception('test handle exception'));
		$expected = 'test handle exception';
		$result = $this->_results[74];
		unset($this->_results[74]);
		$this->assertEqual($expected, $result['message']);
	}

	public function testResults() {
		$expected = 63;
		$result = count($this->results());
		$this->assertEqual($expected, $result);
	}

	public function testGetTest() {
		$test = static::get('lithium\test\Unit');
		$this->assertEqual($test, __CLASS__);
	}

	public function testAssertCookie() {
		$headers = array(
			'Set-Cookie: name[key]=value; expires=Tue, 04-May-2010 19:02:36 GMT; path=/',
			'Set-Cookie: name[key1]=value1; expires=Tue, 04-May-2010 19:02:36 GMT; path=/',
			'Set-Cookie: name[key2][nested]=value1; expires=Tue, 04-May-2010 19:02:36 GMT; path=/'
		);

		$this->assertCookie(array('key' => 'key', 'value' => 'value'), $headers);
		$this->assertCookie(array('key' => 'key1', 'value' => 'value1'), $headers);
		$this->assertCookie(array('key' => 'key2.nested', 'value' => 'value1'), $headers);

		$this->assertCookie(array(
			'key' => 'key2.nested', 'value' => 'value1',
			'expires' => 'May 04 2010 15:02:36'
		), $headers);
	}
}

?>