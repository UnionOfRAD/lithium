<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test\filter;

use lithium\test\filter\Affected;
use lithium\test\Group;
use lithium\tests\cases\g11n\CatalogTest;
use lithium\test\Report;

class AffectedTest extends \lithium\test\Unit {

	public function setUp() {
		$this->report = new Report();
	}

	public function testSingleTest() {
		$group = new Group();
		$group->add('lithium\tests\cases\g11n\CatalogTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array(
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\console\command\g11n\ExtractTest'
		);
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}

	public function testSingleTestWithSingleResult() {
		$group = new Group();
		$group->add('lithium\tests\cases\core\StaticObjectTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array('lithium\tests\cases\core\StaticObjectTest');
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}

	public function testMultipleTests() {
		$group = new Group();
		$group->add('lithium\tests\cases\g11n\CatalogTest');
		$group->add('lithium\tests\cases\analysis\LoggerTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array(
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\analysis\LoggerTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\console\command\g11n\ExtractTest'
		);
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}

	public function testCyclicDependency() {
		$group = new Group();
		$group->add('lithium\tests\cases\g11n\CatalogTest');
		$group->add('lithium\tests\cases\g11n\MessageTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array(
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\console\command\g11n\ExtractTest'
		);
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}
}

?>