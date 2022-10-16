<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\analysis;

use lithium\core\Libraries;
use lithium\analysis\Parser;

class ParserTest extends \lithium\test\Unit {

	/**
	 * Tests that PHP code snippets properly resolve to their corresponding tokens.
	 */
	public function testSingleTokenization() {
		$result = Parser::token('static');
		$this->assertEqual('T_STATIC', $result);

		$result = Parser::token('=>');
		$this->assertEqual('T_DOUBLE_ARROW', $result);

		$result = Parser::token(' =>');
		$this->assertEqual('T_WHITESPACE', $result);

		$result = Parser::token('static =>');
		$this->assertEqual('T_STATIC', $result);

		$result = Parser::token("\nstatic =>");
		$this->assertEqual('T_WHITESPACE', $result);

		$this->assertFalse(Parser::token(''));

		$result = Parser::token(';');
		$this->assertEqual(';', $result);

		$result = Parser::token('"string"');
		$this->assertEqual('T_CONSTANT_ENCAPSED_STRING', $result);

		$result = Parser::token('1');
		$this->assertEqual('T_LNUMBER', $result);

		$result = Parser::token('0');
		$this->assertEqual('T_LNUMBER', $result);

		$result = Parser::token('0');
		$this->assertEqual('T_LNUMBER', $result);
	}

	public function testFullTokenization() {
		$result = Parser::tokenize('$foo = function() {};');
		$this->assertCount(11, $result);

		$expected = [
			'id' => T_VARIABLE,
			'name' => 'T_VARIABLE',
			'content' => '$foo',
			'line' => 1
		];
		$this->assertEqual($expected, $result[0]);

		$expected = ['id' => null, 'name' => ';', 'content' => ';', 'line' => 1];
		$this->assertEqual($expected, $result[10]);

		$code = '$defaults = ["id" => "foo", "name" => "bar", \'count\' => 5];';
		$result = Parser::tokenize($code);

		$this->assertCount(26, $result);
		$this->assertEqual('T_VARIABLE', $result[0]['name']);
		$this->assertEqual('$defaults', $result[0]['content']);
	}

	public function testTokenPatternMatching() {
		$code = '$defaults = ["id" => "foo", "name" => "bar", \'count\' => 5];';

		$result = Parser::match($code, ['"string"'], ['return' => 'content']);
		$expected = ['"id"', '"foo"', '"name"', '"bar"', '\'count\''];
		$this->assertEqual($expected, $result);

		$result = Parser::match(
			$code,
			['"string"' => ['before' => '=>'], '1' => ['before' => '=>']],
			['return' => 'content']
		);
		$expected = ['"foo"', '"bar"', '5'];
		$this->assertEqual($expected, $result);

		$result = Parser::match($code, ['"string"' => ['after' => '=>']], [
			'return' => 'content'
		]);
		$expected = ['"id"', '"name"', '\'count\''];
		$this->assertEqual($expected, $result);
	}

	public function testFilteredTokenization() {
		$code = 'while (isset($countRugen)) { if ($inigoMontoya->is("alive")) { ' . "\n";
		$code .= '$inigoMontoya->say(["hello", "name", "accusation", "die"]); ' . "\n";
		$code .= 'try { $inigoMontoya->kill($countRugen); } catch (Exception $e) { continue; } } }';

		$result = Parser::tokenize($code, ['include' => ['T_IF', 'T_WHILE', 'T_CATCH']]);
		$expected = [
			['id' => T_WHILE, 'name' => 'T_WHILE', 'content' => 'while', 'line' => 1],
			['id' => T_IF, 'name' => 'T_IF', 'content' => 'if', 'line' => 1],
			['id' => T_CATCH, 'name' => 'T_CATCH', 'content' => 'catch', 'line' => 3]
		];
		$this->assertEqual($expected, $result);
	}

	public function testFindingTokenPatterns() {
		$code = file_get_contents(Libraries::path('lithium\analysis\Parser'));

		$expected = ['tokenize', 'matchToken', '_prepareMatchParams', 'token'];
		$results = array_values(array_unique(array_map(function($i) { return $i[0]; }, Parser::find(
			$code, 'static::_(*)', ['capture' => ['T_STRING'], 'return' => 'content']
		))));

		$this->assertEqual($expected, $results);

		$expected = ['lithium\util\Set', 'lithium\util\Collection'];
		$results = array_map(
			function ($i) { return join('', $i); },
			$results = Parser::find($code, 'use *;', [
				'return'      => 'content',
				'lineBreaks'  => true,
				'startOfLine' => true,
				'capture'     => ['T_NAME_QUALIFIED', 'T_NAME_FULLY_QUALIFIED']
			])
		);
		$this->assertEqual($expected, $results);

		$code = 'function test($options) { return function($foo) use ($options) {';
		$code .= ' ClassName::method($options); ' . "\n" . ' $foo->method($options); }; }';
		list($results) = Parser::find($code, '_::_(', [
			'capture' => ['T_STRING'], 'return' => 'content'
		]);
		$expected = ['ClassName', 'method'];
		$this->assertEqual($expected, $results);
	}

	public function testParserGuessesLineBleed() {
		$code = <<<EOD
if (false) {
	return true;
}
EOD;
		$tokens = Parser::tokenize($code);
		$this->assertIdentical('}', $tokens[13]['content']);
		$this->assertIdentical(3, $tokens[13]['line']);
	}

	public function testParserGuessesLineBleedWithNonWhitespace() {
		$code = <<<EOD
if (false) {
	// hello world
}
EOD;
		$tokens = Parser::tokenize($code);
		$this->assertIdentical('}', $tokens[10]['content']);
		$this->assertIdentical(3, $tokens[10]['line']);
	}

}

?>