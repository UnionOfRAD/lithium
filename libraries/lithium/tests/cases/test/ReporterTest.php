<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use \lithium\test\Reporter;

class ReporterTest extends \lithium\test\Unit {

	public function setUp() {
		$this->reporter = new Reporter();
	}

	public function testMenu() {
		$tests = array('lithium\tests\cases\test\reporter\HtmlTest');
		$expected = "";
		$result = $this->reporter->menu($tests);
		$this->assertEqual($expected, $result);
	}

	public function testMenuTree() {
		$tests = array('lithium\tests\cases\test\reporter\HtmlTest');
		$expected = "";
		$result = $this->reporter->menu($tests, array('tree' => true));
		$this->assertEqual($expected, $result);
	}

	public function testStats() {
		$stats = array(
			'asserts' => 1,
			'passes' => array(array(
				'line' => 23, 'method' => 'testNothing',
				'assertion' => 'assertEqual', 'message' => 'the message',
				'class' => 'lithium\tests\cases\test\reporter\BaseTest'
			)),
			'fails' => array(array('method' => 'testNothing')),
			'errors' => array(),
			'exceptions' => array(),
		);
		$expected = "";
		$result = $this->reporter->stats($stats);
		$this->assertEqual($expected, $result);
	}

	public function testStatsWithError() {
		$stats = array(
			'asserts' => 1,
			'passes' => array(array(
				'line' => 23, 'method' => 'testNothing',
				'assertion' => 'assertEqual', 'message' => 'the message',
				'class' => 'lithium\tests\cases\test\reporter\BaseTest'
			)),
			'fails' => array(array('method' => 'testNothing')),
			'errors' => array(array('result' => 'fail')),
			'exceptions' => array(),
		);
		$expected = "";
		$result = $this->reporter->stats($stats);
		$this->assertEqual($expected, $result);
	}
}

?>