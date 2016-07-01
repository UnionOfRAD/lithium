<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\analysis;

use lithium\analysis\Docblock;
use lithium\analysis\Inspector;

class DocblockTest extends \lithium\test\Unit {

	public function testComment() {
		$expected = [
			'description' => '',
			'text' => null,
			'tags' => []
		];
		$result = Docblock::comment('');
		$this->assertEqual($expected, $result);

		$comment = "/**\n * Lithium is cool\n * @foo bar\n * @baz qux\n */";
		$expected = ['description' => 'Lithium is cool', 'text' => '', 'tags' => []];
		$result = Docblock::comment($comment);
		$this->assertEqual($expected, $result);

		Docblock::$tags[] = 'foo';
		Docblock::$tags[] = 'baz';
		$expected['tags'] = ['foo' => 'bar', 'baz' => 'qux'];
		$result = Docblock::comment($comment);
		$this->assertEqual($expected, $result);

		$comment = "/**\n * Lithium is cool\n *\n * Very cool\n * @foo bar\n * @baz qux\n */";
		$expected = [
			'description' => 'Lithium is cool',
			'text' => 'Very cool',
			'tags' => ['foo' => 'bar', 'baz' => 'qux']
		];
		$result = Docblock::comment($comment);
		$this->assertEqual($expected, $result);
	}

	public function testParamTag() {
		$comment = "/**\n * Lithium is cool\n * @param string \$str Some string\n */";
		$expected = [
			'description' => 'Lithium is cool',
			'text' => '',
			'tags' => ['params' => [
				'$str' => ['type' => 'string', 'text' => 'Some string']
			]]
		];
		$result = Docblock::comment($comment);
		$this->assertEqual($expected, $result);
	}

	/**
	 * This is a short description.
	 *
	 * This is a longer description...
	 * That contains
	 * multiple lines
	 *
	 * @deprecated
	 * @important This is a tag that spans a single line.
	 * @discuss This is a tag that
	 *          spans
	 *          several
	 *          lines.
	 * @discuss The second discussion item
	 * @link http://example.com/
	 * @see lithium\analysis\Docblock
	 * @return void This tag contains a email@address.com.
	 */
	public function testTagParsing() {
		$info = Inspector::info(__METHOD__ . '()');
		$result = Docblock::comment($info['comment']);
		$this->assertEqual('This is a short description.', $result['description']);

		$expected = "This is a longer description...\nThat contains\nmultiple lines";
		$this->assertEqual($expected, $result['text']);

		$tags = $result['tags'];
		$expected = ['deprecated', 'important', 'discuss', 'link', 'see', 'return'];
		$this->assertEqual($expected, array_keys($tags));

		$result = "This is a tag that\n         spans\n         several\n         lines.";
		$this->assertEqual($result, $tags['discuss'][0]);
		$this->assertEqual("The second discussion item", $tags['discuss'][1]);

		$this->assertEqual('void This tag contains a email@address.com.', $tags['return']);
		$this->assertEqual([], Docblock::tags(null));

		$this->assertEqual(['params' => []], Docblock::tags("Foobar\n\n@param string"));
	}

	public function testDocblockNewlineHandling() {
		$doc  = " * This line as well as the line below it,\r\n";
		$doc .= " * are part of the description.\r\n *\r\n * This line isn't.";
		$result = Docblock::comment($doc);

		$description = "This line as well as the line below it,\nare part of the description.";
		$this->assertEqual($description, $result['description']);

		$this->assertEqual('This line isn\'t.', $result['text']);
	}

	/**
	 * This docblock has an extra * in the closing element.
	 *
	 */
	public function testBadlyClosedDocblock() {
		$info = Inspector::info(__METHOD__ . '()');
		$description = 'This docblock has an extra * in the closing element.';
		$this->assertEqual($description, $info['description']);
		$this->assertEqual('', $info['text']);
	}
}

?>