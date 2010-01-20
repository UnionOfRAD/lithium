<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\analysis;

use \lithium\analysis\Docblock;

class DocblockTest extends \lithium\test\Unit {

	public function testComment() {
		$expected = array(
			'description' => '',
			'text' => null,
			'tags' => array()
		);
		$result = Docblock::comment('');
		$this->assertEqual($expected, $result);

		$comment = "/**\n * Lithium is cool\n * @foo bar\n * @baz qux\n */";
		$expected = array(
			'description' => 'Lithium is cool',
			'text' => '',
			'tags' => array('foo' => 'bar', 'baz' => 'qux')
		);
		$result = Docblock::comment($comment);
		$this->assertEqual($expected, $result);

		$comment = "/**\n * Lithium is cool\n *\n * Very cool\n * @foo bar\n * @baz qux\n */";
		$expected = array(
			'description' => 'Lithium is cool',
			'text' => 'Very cool',
			'tags' => array('foo' => 'bar', 'baz' => 'qux')
		);
		$result = Docblock::comment($comment);
		$this->assertEqual($expected, $result);
	}

	public function testParamTag() {
		$comment = "/**\n * Lithium is cool\n * @param string \$str Some string\n */";
		$expected = array(
			'description' => 'Lithium is cool',
			'text' => '',
			'tags' => array('params' => array(
				'$str' => array('type' => 'string', 'text' => 'Some string')
			))
		);
		$result = Docblock::comment($comment);
		$this->assertEqual($expected, $result);
	}
}

?>