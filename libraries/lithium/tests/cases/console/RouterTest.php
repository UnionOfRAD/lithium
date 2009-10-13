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

namespace lithium\tests\cases\console;

use \lithium\console\Router;
use \lithium\console\Request;

class RouterTest extends \lithium\test\Unit {

	public function testParseNoOptions() {
		$expected = array(
			'command' => null, 'action' => 'run',
			'passed' => array(), 'named' => array()
		);
		$result = Router::parse();
		$this->assertEqual($expected, $result);
	}

	public function testParseWithPassed() {
		$expected = array(
			'command' => 'test',
			'action' => 'action',
			'passed' => array('param'),
			'named' => array()
		);
		$result = Router::parse(new Request(array(
			'args' => array(
				'test', 'action', 'param'
			)
		)));
		$this->assertEqual($expected, $result);
	}

	public function testParseWithNamed() {
		$expected = array(
			'command' => 'test',
			'action' => 'run',
			'passed' => array(),
			'named' => array(
				'case' => 'lithium.tests.cases.console.RouterTest'
			)
		);
		$result = Router::parse(new Request(array(
			'args' => array(
				'test',
				'-case', 'lithium.tests.cases.console.RouterTest'
			)
		)));
		$this->assertEqual($expected, $result);
	}

	public function testParseWithDoubleNamed() {
		$expected = array(
			'command' => 'test',
			'action' => 'run',
			'passed' => array(),
			'named' => array(
				'case' => 'lithium.tests.cases.console.RouterTest'
			)
		);
		$result = Router::parse(new Request(array(
			'args' => array(
				'test',
				'--case=lithium.tests.cases.console.RouterTest'
			)
		)));

		$this->assertEqual($expected, $result);
	}
}
?>