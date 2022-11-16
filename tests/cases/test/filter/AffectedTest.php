<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test\filter;

use lithium\test\filter\Affected;
use lithium\test\Group;
use lithium\test\Report;

class AffectedTest extends \lithium\test\Unit {

	public $report;

	public function setUp() {
		$this->report = new Report();
	}

	public function testSingleTest() {
		$group = new Group();
		$group->add('lithium\tests\cases\g11n\CatalogTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = [
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\console\command\g11n\ExtractTest'
		];
		$result = $tests->map('get_class', ['collect' => false]);
		$this->assertEqual($expected, $result);
	}

	public function testSingleTestWithSingleResult() {
		$group = new Group();
		$group->add('lithium\tests\cases\core\EmptyTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = ['lithium\tests\cases\core\EmptyTest'];
		$result = $tests->map('get_class', ['collect' => false]);
		$this->assertEqual($expected, $result);
	}

	public function testMultipleTests() {
		$group = new Group();
		$group->add('lithium\tests\cases\g11n\CatalogTest');
		$group->add('lithium\tests\cases\analysis\LoggerTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = [
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\analysis\LoggerTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\console\command\g11n\ExtractTest'
		];
		$result = $tests->map('get_class', ['collect' => false]);
		$this->assertEqual($expected, $result);
	}

	public function testCyclicDependency() {
		$group = new Group();
		$group->add('lithium\tests\cases\g11n\CatalogTest');
		$group->add('lithium\tests\cases\g11n\MessageTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = [
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\console\command\g11n\ExtractTest'
		];
		$result = $tests->map('get_class', ['collect' => false]);
		$this->assertEqual($expected, $result);
	}

	public function testAnalyze() {
		$ns = 'lithium\tests\cases';

		$expected = [
			'lithium\g11n\Message' => "{$ns}\g11n\MessageTest",
			'lithium\console\command\g11n\Extract' => "{$ns}\console\command\g11n\ExtractTest"
		];

		$group = new Group();
		$group->add('lithium\tests\cases\g11n\CatalogTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());
		$results = Affected::analyze($this->report);

		$this->assertEqual($results, $expected);
	}
}

?>