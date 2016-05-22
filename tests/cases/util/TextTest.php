<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\util;

use lithium\util\Text;
use lithium\tests\mocks\util\MockTextObject;

class TextTest extends \lithium\test\Unit {

	public function testUuidGeneration() {
		$result = Text::uuid();
		$pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[8-9a-b][a-f0-9]{3}-[a-f0-9]{12}$/";
		$this->assertPattern($pattern, $result);

		$result = Text::uuid();
		$this->assertPattern($pattern, $result);
	}

	public function testMultipleUuidGeneration() {
		$check = array();
		$count = 50;
		$pattern = "/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[8-9a-b][a-f0-9]{3}-[a-f0-9]{12}$/";

		for ($i = 0; $i < $count; $i++) {
			$result = Text::uuid();
			$match = preg_match($pattern, $result);
			$this->assertNotEmpty($match);
			$this->assertFalse(in_array($result, $check));
			$check[] = $result;
		}
	}

	public function testInsert() {
		$string = '2 + 2 = {:sum}. Lithium is {:adjective}.';
		$expected = '2 + 2 = 4. Lithium is yummy.';
		$result = Text::insert($string, array('sum' => '4', 'adjective' => 'yummy'));
		$this->assertEqual($expected, $result);

		$string = '2 + 2 = %sum. Lithium is %adjective.';
		$result = Text::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array(
			'before' => '%', 'after' => ''
		));
		$this->assertEqual($expected, $result);

		$string = '2 + 2 = 2sum2. Lithium is 9adjective9.';
		$result = Text::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array(
			'format' => '/([\d])%s\\1/'
		));
		$this->assertEqual($expected, $result);

		$string = '2 + 2 = 12sum21. Lithium is 23adjective45.';
		$expected = '2 + 2 = 4. Lithium is 23adjective45.';
		$result = Text::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array(
			'format' => '/([\d])([\d])%s\\2\\1/'
		));
		$this->assertEqual($expected, $result);

		$string = '{:web} {:web_site}';
		$expected = 'www http';
		$result = Text::insert($string, array('web' => 'www', 'web_site' => 'http'));
		$this->assertEqual($expected, $result);

		$string = '2 + 2 = <sum. Lithium is <adjective>.';
		$expected = '2 + 2 = <sum. Lithium is yummy.';
		$result = Text::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array(
			'before' => '<', 'after' => '>'
		));
		$this->assertEqual($expected, $result);

		$string = '2 + 2 = \:sum. Lithium is :adjective.';
		$expected = '2 + 2 = :sum. Lithium is yummy.';
		$result = Text::insert(
			$string,
			array('sum' => '4', 'adjective' => 'yummy'),
			array('before' => ':', 'after' => null, 'escape' => '\\')
		);
		$this->assertEqual($expected, $result);

		$string = '2 + 2 = !:sum. Lithium is :adjective.';
		$result = Text::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array(
			'escape' => '!', 'before' => ':', 'after' => ''
		));
		$this->assertEqual($expected, $result);

		$string = '2 + 2 = \%sum. Lithium is %adjective.';
		$expected = '2 + 2 = %sum. Lithium is yummy.';
		$result = Text::insert($string, array('sum' => '4', 'adjective' => 'yummy'), array(
			'before' => '%', 'after' => '', 'escape' => '\\'
		));
		$this->assertEqual($expected, $result);

		$string = ':a :b \:a :a';
		$expected = '1 2 :a 1';
		$result = Text::insert($string, array('a' => 1, 'b' => 2), array(
			'before' => ':', 'after' => '', 'escape' => '\\'
		));
		$this->assertEqual($expected, $result);

		$string = '{:a} {:b} {:c}';
		$expected = '2 3';
		$result = Text::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($expected, $result);

		$string = '{:a} {:b} {:c}';
		$expected = '1 3';
		$result = Text::insert($string, array('a' => 1, 'c' => 3), array('clean' => true));
		$this->assertEqual($expected, $result);

		$string = '{:a} {:b} {:c}';
		$expected = '2 3';
		$result = Text::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($expected, $result);

		$string = ':a, :b and :c';
		$expected = '2 and 3';
		$result = Text::insert($string, array('b' => 2, 'c' => 3), array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual($expected, $result);

		$string = '{:a}, {:b} and {:c}';
		$expected = '2 and 3';
		$result = Text::insert($string, array('b' => 2, 'c' => 3), array('clean' => true));
		$this->assertEqual($expected, $result);

		$string = '"{:a}, {:b} and {:c}"';
		$expected = '"1, 2"';
		$result = Text::insert($string, array('a' => 1, 'b' => 2), array('clean' => true));
		$this->assertEqual($expected, $result);

		$string = '"${a}, ${b} and ${c}"';
		$expected = '"1, 2"';
		$result = Text::insert($string, array('a' => 1, 'b' => 2), array(
			'before' => '${', 'after' => '}', 'clean' => true
		));
		$this->assertEqual($expected, $result);

		$string = '<img src="{:src}" alt="{:alt}" class="foo {:extra} bar"/>';
		$expected = '<img src="foo" class="foo bar"/>';
		$result = Text::insert($string, array('src' => 'foo'), array('clean' => 'html'));
		$this->assertEqual($expected, $result);

		$string = '<img src=":src" class=":no :extra"/>';
		$expected = '<img src="foo"/>';
		$result = Text::insert($string, array('src' => 'foo'), array(
			'clean' => 'html', 'before' => ':', 'after' => ''
		));
		$this->assertEqual($expected, $result);

		$string = '<img src="{:src}" class="{:no} {:extra}"/>';
		$expected = '<img src="foo" class="bar"/>';
		$result = Text::insert($string, array('src' => 'foo', 'extra' => 'bar'), array(
			'clean' => 'html'
		));
		$this->assertEqual($expected, $result);

		$result = Text::insert("this is a ? string", array("test"));
		$expected = "this is a test string";
		$this->assertEqual($expected, $result);

		$result = Text::insert("this is a ? string with a ? ? ?", array(
			'long', 'few?', 'params', 'you know'
		));
		$expected = "this is a long string with a few? params you know";
		$this->assertEqual($expected, $result);

		$result = Text::insert(
			'update saved_urls set url = :url where id = :id',
			array('url' => 'http://testurl.com/param1:url/param2:id', 'id' => 1),
			array('before' => ':', 'after' => '')
		);
		$expected = "update saved_urls set url = http://testurl.com/param1:url/param2:id ";
		$expected .= "where id = 1";
		$this->assertEqual($expected, $result);

		$result = Text::insert(
			'update saved_urls set url = :url where id = :id',
			array('id' => 1, 'url' => 'http://www.testurl.com/param1:url/param2:id'),
			array('before' => ':', 'after' => '')
		);
		$expected = "update saved_urls set url = http://www.testurl.com/param1:url/param2:id ";
		$expected .= "where id = 1";
		$this->assertEqual($expected, $result);

		$result = Text::insert('{:me} lithium. {:subject} {:verb} fantastic.', array(
			'me' => 'I :verb', 'subject' => 'lithium', 'verb' => 'is'
		));
		$expected = "I :verb lithium. lithium is fantastic.";
		$this->assertEqual($expected, $result);

		$result = Text::insert(':I.am: :not.yet: passing.', array('I.am' => 'We are'), array(
			'before' => ':', 'after' => ':', 'clean' => array(
				'replacement' => ' of course', 'method' => 'text'
			)
		));
		$expected = "We are of course passing.";
		$this->assertEqual($expected, $result);

		$result = Text::insert(
			':I.am: :not.yet: passing.',
			array('I.am' => 'We are'),
			array('before' => ':', 'after' => ':', 'clean' => true)
		);
		$expected = "We are passing.";
		$this->assertEqual($expected, $result);

		$result = Text::insert('?-pended result', array('Pre'));
		$expected = "Pre-pended result";
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that text replacements with `Text::insert()` using key/value pairs are not
	 * mis-handled if numeric keys are present in the array (only if they appear first).
	 */
	public function testInsertWithUnusedNumericKey() {
		$result = Text::insert("Hey, what are you tryin' to {:action} on us?", array(
			'action' => 'push', '!'
		));
		$expected = "Hey, what are you tryin' to push on us?";
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests casting/inserting of custom objects with `Text::insert()`.
	 */
	public function testInsertWithObject() {
		$foo = new MockTextObject();
		$result = Text::insert('This is a {:foo}', compact('foo'));
		$expected = 'This is a custom object';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Test that an empty array is not added to the string
	 */
	public function testInsertWithEmptyArray() {
		$result = Text::insert("Hey, what are you tryin' to {:action} on us?",
			array('action' => array())
		);
		$expected = "Hey, what are you tryin' to  on us?";
		$this->assertEqual($expected, $result);
	}

	public function testCleanInsert() {
		$result = Text::clean(':incomplete', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual('', $result);

		$result = Text::clean(':incomplete', array(
			'clean' => array('method' => 'text', 'replacement' => 'complete'),
			'before' => ':', 'after' => '')
		);
		$this->assertEqual('complete', $result);

		$result = Text::clean(':in.complete', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual('', $result);

		$result = Text::clean(':in.complete and', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual('', $result);

		$result = Text::clean(':in.complete or stuff', array(
			'clean' => true, 'before' => ':', 'after' => ''
		));
		$this->assertEqual('stuff', $result);

		$result = Text::clean(
			'<p class=":missing" id=":missing">Text here</p>',
			array('clean' => 'html', 'before' => ':', 'after' => '')
		);
		$this->assertEqual('<p>Text here</p>', $result);

		$string = ':a 2 3';
		$result = Text::clean($string, array('clean' => true, 'before' => ':', 'after' => ''));
		$this->assertEqual('2 3', $result);
	}

	public function testTokenize() {
		$result = Text::tokenize('A,(short,boring test)');
		$expected = array('A', '(short,boring test)');
		$this->assertEqual($expected, $result);

		$result = Text::tokenize('A,(short,more interesting( test)');
		$expected = array('A', '(short,more interesting( test)');
		$this->assertEqual($expected, $result);

		$result = Text::tokenize('A,(short,very interesting( test))');
		$expected = array('A', '(short,very interesting( test))');
		$this->assertEqual($expected, $result);

		$result = Text::tokenize('"single tag"', array(
			'separator' => ' ', 'leftBound' => '"', 'rightBound' => '"'
		));
		$expected = array('"single tag"');
		$this->assertEqual($expected, $result);

		$result = Text::tokenize('tagA "single tag" tagB', array(
			'separator' => ' ', 'leftBound' => '"', 'rightBound' => '"'
		));
		$expected = array('tagA', '"single tag"', 'tagB');
		$this->assertEqual($expected, $result);

		$result = Text::tokenize(array());
		$expected = array();
		$this->assertEqual($expected, $result);

		$result = Text::tokenize(null);
		$this->assertNull($result);
	}

	/**
	 * Tests the `Text::extract()` regex helper method.
	 */
	public function testTextExtraction() {
		$result = Text::extract('/string/', 'whole string');
		$this->assertEqual('string', $result);

		$this->assertFalse(Text::extract('/not/', 'whole string'));
		$this->assertEqual('part', Text::extract('/\w+\s*(\w+)/', 'second part', 1));
		$this->assertNull(Text::extract('/\w+\s*(\w+)/', 'second part', 2));
	}

	public function testTextInsertWithQuestionMark() {
		$result = Text::insert('some string with a ?', array());
		$this->assertEqual('some string with a ?', $result);

		$result = Text::insert('some {:param}string with a ?', array('param' => null));
		$this->assertEqual('some string with a ?', $result);
	}

	/**
	 * Verifies that `Text::insert()` doesn't completely ignore empty values.
	 */
	public function testInsertingEmptyValues() {
		$this->assertEqual('value="0"', Text::insert('value="{:value}"', array('value' => 0)));
	}
}

?>