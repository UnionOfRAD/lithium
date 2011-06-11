<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use Exception;
use lithium\core\Libraries;
use lithium\tests\mocks\test\MockUnitTest;
use lithium\tests\mocks\test\cases\MockSkipThrowsException;
use lithium\tests\mocks\test\cases\MockTestErrorHandling;

class UnitTest extends \lithium\test\Unit {

	public function compare($type, $expected, $result = null) {
		return $this->_compare($type, $expected, $result);
	}

	public function testBaseAssertions() {
		$this->assert(true);
		$this->assert(false);
		$result = array_pop($this->_results);
		$this->assertEqual('fail', $result['result']);
		$this->assertTrue(true);
		$this->assertFalse(false);
	}

	public function testCompareIsEqual() {
		$result = $this->compare('equal', 'string', 'string');
		$this->assertTrue($result);
	}

	public function testCompareIsIdentical() {
		$result = $this->compare('identical', 'string', 'string');
		$this->assertTrue($result);
	}

	public function testCompareTypes() {
		$expected = array(
			'trace' => null,
			'expected' => "(array) Array\n(\n)",
			'result' => "(string) string"
		);
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
			'result' => 'fail', 'file' => __FILE__, 'line' => __LINE__ - 3,
			'method' => 'testAssertEqualNumericFail', 'assertion' => 'assertEqual',
			'class' => __CLASS__, 'message' =>
				"trace: [2]\nexpected: 3\n"
				. "result: NULL\n",
			'data' => array(
				'trace' => '[2]',
				'expected' => 3,
				'result' => null
			)
		);
		$result = array_pop($this->_results);
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
			'result' => 'fail', 'file' => __FILE__, 'line' => __LINE__ - 3,
			'method' => 'testAssertEqualThreeDFail', 'assertion' => 'assertEqual',
			'class' => __CLASS__, 'message' =>
				"trace: [0][1][1]\nexpected: 2\n"
				. "result: NULL\n"
				. "trace: [1][1][1]\nexpected: 2\n"
				. "result: NULL\n",
			'data' => array(
				array(
					array(
						'trace' => '[0][1][1]',
						'expected' => 2,
						'result' => null
					)
				),
				array(
					array('trace' => '[1][1][1]',
						'expected' => 2,
						'result' => null
					)
				)
			)
		);
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result);
	}

	public function testAssertWithCustomMessage() {
		$expected = false;
		$result = true;
		$this->assertEqual($expected, $result, 'Custom Message Test');

		$expected = 'Custom Message Test';
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['message']);
	}

	public function testSubject() {
		$test = new MockUnitTest();
		$expected = 'lithium\\tests\\mocks\\test\\MockUnit';
		$result = $test->subject();
		$this->assertEqual($expected, $result);
	}

	public function testRun() {
		$file = realpath(LITHIUM_LIBRARY_PATH) . '/lithium/tests/mocks/test/MockUnitTest.php';
		$test = new MockUnitTest();
		$expected = array(
			'result' => 'pass',
			'file' => realpath($file),
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
		$result = array_pop($this->_results);
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
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertIdentical';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertIdentical';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertIdenticalArray() {
		$expected = array('1', '2', '3');
		$result = array('1', '3', '4');
		$this->assertIdentical($expected, $result);

		$expected = 'fail';
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertIdenticalArray';
		$this->assertEqual($expected, $result['method']);

		$expected = "trace: [1]\nexpected: '2'\nresult: '3'\n";
		$this->assertEqual($expected, $result['message']);
	}

	public function testAssertNull() {
		$expected = null;
		$result = null;
		$this->assertNull($expected, $result);

		$expected = 'pass';
		$result = array_pop($this->_results);
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
		$result = array_pop($this->_results);
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
		$result = array_pop($this->_results);
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
		$result = array_pop($this->_results);
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
		$result = array_pop($this->_results);
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
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertTagsMissingAttribute';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertTags';
		$this->assertEqual($expected, $result['assertion']);

		$expected = '- Item #1 / regex #1 failed: Attribute "id" == "test"';
		$this->assertEqual($expected, $result['message']);
	}

	public function testAssertTagsString() {
		$result = '<span>ok</span>';
		$this->assertTags($result, array('<span'));

		$expected = 'pass';
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertTagsString';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertTags';
		$this->assertEqual($expected, $result['assertion']);
	}

	public function testAssertTagsFailTextEqual() {
		$result = '<span>ok</span>';
		$this->assertTags($result, array('span'));

		$expected = 'fail';
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testAssertTagsFailTextEqual';
		$this->assertEqual($expected, $result['method']);

		$expected = '- Item #1 / regex #0 failed: Text equals "span"';
		$this->assertEqual($expected, $result['message']);
	}

	public function testIdenticalArrayFail() {
		$expected = array('1', '2', '3');
		$result = array(1, '2', '3');;
		$this->assertIdentical($expected, $result);

		$expected = 'fail';
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['result']);

		$expected = 'testIdenticalArrayFail';
		$this->assertEqual($expected, $result['method']);

		$expected = 'assertIdentical';
		$this->assertEqual($expected, $result['assertion']);

		$expected = "trace: [0]\nexpected: '(string) 1'\nresult: '(integer) 1'\n";
		$this->assertEqual($expected, $result['message']);
	}

	public function testCleanUp() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		$this->assertTrue(mkdir("{$base}/cleanup_test"));
		$this->assertTrue(touch("{$base}/cleanup_test/file"));
		$this->assertTrue(touch("{$base}/cleanup_test/.hideme"));

		$this->_cleanUp();
		$this->assertFalse(file_exists("{$base}/cleanup_test"));
	}

	public function testCleanUpWithFullPath() {
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
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
		$base = Libraries::get(true, 'resources') . '/tmp/tests';
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
		} catch (Exception $e) {
			$result = $e->getMessage();
		}
		$expected = 'skip me';
		$this->assertEqual($expected, $result);
	}

	public function testExpectException() {
		$this->expectException('test expected exception');
		$expected = 'test expected exception';
		$result = array_pop($this->_expected);
		$this->assertEqual($expected, $result);
	}

	public function testHandleException() {
		$this->_handleException(new Exception('test handle exception'));
		$expected = 'test handle exception';
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['message']);
	}

	public function testExpectExceptionRegex() {
		$this->expectException('/test handle exception/');
		$this->_handleException(new Exception('test handle exception'));
		$expected = 'test handle exception';
		$this->assertTrue(empty($this->_expected));
	}

	public function testGetTest() {
		$test = static::get('lithium\test\Unit');
		$this->assertEqual($test, __CLASS__);
	}

	public function testAssertCookie() {
		$expected = array(
			'key' => 'key2.nested', 'value' => 'value1', 'expires' => 'May 04 2010 14:02:36 EST'
		);
		$this->assertCookie($expected);

		$expected = 'fail';
		$result = array_pop($this->_results);
		$this->assertEqual($expected, $result['result']);

		$expected = '/not found in headers./';
		$this->assertPattern($expected, $result['message']);
	}

	public function testAssertCookieWithHeaders() {
		$headers = array(
			'Set-Cookie: name[key]=value; expires=Tue, 04-May-2010 19:02:36 GMT; path=/',
			'Set-Cookie: name[key1]=value1; expires=Tue, 04-May-2010 19:02:36 GMT; path=/',
			'Set-Cookie: name[key2][nested]=value1; expires=Tue, 04-May-2010 19:02:36 GMT; path=/'
		);

		$this->assertCookie(array('key' => 'key', 'value' => 'value'), $headers);
		$this->assertCookie(array('key' => 'key1', 'value' => 'value1'), $headers);
		$this->assertCookie(array('key' => 'key2.nested', 'value' => 'value1'), $headers);

		$expected = array(
			'key' => 'key2.nested', 'value' => 'value1', 'expires' => 'May 04 2010 14:02:36 EST'
		);
		$this->assertCookie($expected, $headers);
	}

	public function testCompareWithEmptyResult() {
		$result = $this->compare('equal', array('key' => array('val1', 'val2')), array());
		$expected = array(
			'trace' => '[key]',
			'expected' => array('val1', 'val2'),
			'result' => array()
		);
		$this->assertEqual($expected, $result);
	}

	public function testExceptionCatching() {
		$test = new MockSkipThrowsException();
		$test->run();
		$expected = 'skip throws exception';
		$results = $test->results();
		$this->assertEqual($expected, $results[0]['message']);
	}

	public function testErrorHandling() {
		$test = new MockTestErrorHandling();
		$test->run();
		$expected = '/Missing argument 1/';
		$results = $test->results();
		$this->assertPattern($expected, $results[0]['message']);

		$expected = '/Unit::_arrayPermute()/';
		$this->assertPattern($expected, $results[0]['message']);
	}

	public function testAssertObjects() {
		$expected = (object) array('one' => 'two');
		$result = (object) array('one' => 'not-two');
		$this->assertEqual($expected, $result);

		$result = array_pop($this->_results);
		$expected = "one";
		$this->assertEqual($expected, $result['data']['trace']);
	}

	public function testAssertArrayIdentical() {
		$expected = array('one' => array('one'));
		$result = array('one' => array());
		$this->assertIdentical($expected, $result);

		$result = array_pop($this->_results);
		$expected = "[one]";
		$this->assertEqual($expected, $result['data']['trace']);
	}

	public function testCompareIdenticalArray() {
		$expected = array(
			'trace' => null,
			'expected' => array(),
			'result' => array('two', 'values')
		);
		$result = $this->compare('identical', array(), array('two', 'values'));
		$this->assertEqual($expected, $result);
	}

	public function imethods() {
		return array('testCompareIdenticalArray');
	}

	public function testCompareEqualNullArray() {
		$expected = array('trace' =>  null, 'expected' => array(), 'result' => array(null));
		$result = $this->compare('equal', array(), array(null));
		$this->assertEqual($expected, $result);
	}

	public function testCompareIdenticalNullArray() {
		$expected = array('trace' => null, 'expected' => array(), 'result' => array(null));
		$result = $this->compare('identical', array(), array(null));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Always keep second to last.
	 *
	 */
	public function testResults() {
		$expected = 89;
		$result = count($this->results());
		$this->assertEqual($expected, $result);
	}

	/**
	 * Always keep last.
	 *
	 */
	public function testTestMethods() {
		$expected = array(
			'testBaseAssertions', 'testCompareIsEqual', 'testCompareIsIdentical',
			'testCompareTypes', 'testAssertEqualNumeric',
			'testAssertEqualNumericFail', 'testAssertEqualAssociativeArray',
			'testAssertEqualThreeDFail', 'testAssertWithCustomMessage',
			'testSubject', 'testRun', 'testAssertNotEqual', 'testAssertIdentical',
			'testAssertIdenticalArray',
			'testAssertNull', 'testAssertNoPattern', 'testAssertPattern', 'testAssertTags',
			'testAssertTagsNoClosingTag', 'testAssertTagsMissingAttribute',
			'testAssertTagsString', 'testAssertTagsFailTextEqual', 'testIdenticalArrayFail',
			'testCleanUp', 'testCleanUpWithFullPath', 'testCleanUpWithRelativePath',
			'testSkipIf', 'testExpectException', 'testHandleException', 'testExpectExceptionRegex',
			'testGetTest', 'testAssertCookie', 'testAssertCookieWithHeaders',
			'testCompareWithEmptyResult',
			'testExceptionCatching', 'testErrorHandling', 'testAssertObjects',
			'testAssertArrayIdentical', 'testCompareIdenticalArray',
			'testCompareEqualNullArray', 'testCompareIdenticalNullArray',
			'testResults', 'testTestMethods'
		);
		$this->assertIdentical($expected, $this->methods());
	}
}

?>