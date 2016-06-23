<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console;

use lithium\console\Router;
use lithium\console\Request;

class RouterTest extends \lithium\test\Unit {

	protected $_backup;

	public function setUp() {
		$this->_backup = $_SERVER;
		$_SERVER['argv'] = [];
	}

	public function tearDown() {
		$_SERVER = $this->_backup;
	}

	public function testParseNoArgumentsNoOptions() {
		$expected = [
			'command' => null, 'action' => 'run', 'args' => []
		];
		$result = Router::parse();
		$this->assertEqual($expected, $result);
	}

	public function testParseArguments() {
		$expected = [
			'command' => 'test', 'action' => 'action',
			'args' => ['param']
		];
		$result = Router::parse(new Request([
			'args' => ['test', 'action', 'param']
		]));
		$this->assertEqual($expected, $result);
	}

	public function testParseZeroArgument() {
		$expected = [
			'command' => 'test', 'action' => 'action',
			'args' => ['0', '1']
		];
		$result = Router::parse(new Request([
			'args' => ['test', 'action', '0', '1']
		]));
		$this->assertEqual($expected, $result);
	}

	public function testParseGnuStyleLongOptions() {
		$expected = [
			'command' => 'test', 'action' => 'run', 'args' => [],
			'case' => 'lithium.tests.cases.console.RouterTest'
		];
		$result = Router::parse(new Request([
			'args' => [
				'test', 'run',
				'--case=lithium.tests.cases.console.RouterTest'
			]
		]));
		$this->assertEqual($expected, $result);

		$expected = [
			'command' => 'test', 'action' => 'run', 'args' => [],
			'case' => 'lithium.tests.cases.console.RouterTest',
			'phase' => 'drowning'
		];
		$result = Router::parse(new Request([
			'args' => [
				'test',
				'--case=lithium.tests.cases.console.RouterTest',
				'--phase=drowning'
			]
		]));
		$this->assertEqual($expected, $result);
	}

	public function testParseGnuStyleLongOptionsContainingDash() {
		$expected = [
			'command' => 'test', 'action' => 'run', 'args' => [],
			'fooBar' => 'something',
			'foo-bar' => 'something'
		];
		$result = Router::parse(new Request([
			'args' => [
				'test', 'run',
				'--foo-bar=something'
			]
		]));
		$this->assertEqual($expected, $result);
	}

	public function testParseShortOption() {
		$expected = [
			'command' => 'test', 'action' => 'action', 'args' => [],
			'i' => true
		];
		$result = Router::parse(new Request([
			'args' => ['test', 'action', '-i']
		]));
		$this->assertEqual($expected, $result);

		$expected = [
			'command' => 'test', 'action' => 'action', 'args' => ['something'],
			'i' => true
		];
		$result = Router::parse(new Request([
			'args' => ['test', 'action', '-i', 'something']
		]));
		$this->assertEqual($expected, $result);
	}

	public function testParseShortOptionAsFirst() {
		$expected = [
			'command' => 'test', 'action' => 'action', 'args' => [],
			'i' => true
		];
		$result = Router::parse(new Request([
			'args' => ['-i', 'test', 'action']
		]));
		$this->assertEqual($expected, $result);

		$expected = [
			'command' => 'test', 'action' => 'action', 'args' => ['something'],
			'i' => true
		];
		$result = Router::parse(new Request([
			'args' => ['-i', 'test', 'action', 'something']
		]));
		$this->assertEqual($expected, $result);
	}

	public function testParseGnuStyleLongOptionAsFirst() {
		$expected = [
			'command' => 'test', 'action' => 'action', 'long' => 'something', 'i' => true,
			'args' => []
		];
		$result = Router::parse(new Request([
			'args' => ['--long=something', 'test', 'action', '-i']
		]));
		$this->assertEqual($expected, $result);
	}
}

?>