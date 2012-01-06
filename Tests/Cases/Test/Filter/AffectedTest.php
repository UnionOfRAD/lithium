<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Test\Filter;

use Lithium\Test\Filter\Affected;
use Lithium\Test\Group;
use Lithium\Test\Report;

class AffectedTest extends \Lithium\Test\Unit {

	public function setUp() {
		$this->report = new Report();
	}

	public function testSingleTest() {
		$group = new Group();
		$group->add('Lithium\Tests\Cases\G11n\CatalogTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array(
			'Lithium\Tests\Cases\G11n\CatalogTest',
			'Lithium\Tests\Cases\G11n\MessageTest',
			'Lithium\Tests\Cases\Console\Command\G11n\ExtractTest'
		);
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}

	public function testSingleTestWithSingleResult() {
		$group = new Group();
		$group->add('Lithium\Tests\Cases\Core\StaticObjectTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array('Lithium\Tests\Cases\Core\StaticObjectTest');
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}

	public function testMultipleTests() {
		$group = new Group();
		$group->add('Lithium\Tests\Cases\G11n\CatalogTest');
		$group->add('Lithium\Tests\Cases\Analysis\LoggerTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array(
			'Lithium\Tests\Cases\G11n\CatalogTest',
			'Lithium\Tests\Cases\Analysis\LoggerTest',
			'Lithium\Tests\Cases\G11n\MessageTest',
			'Lithium\Tests\Cases\Console\Command\G11n\ExtractTest'
		);
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}

	public function testCyclicDependency() {
		$group = new Group();
		$group->add('Lithium\Tests\Cases\G11n\CatalogTest');
		$group->add('Lithium\Tests\Cases\G11n\MessageTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());

		$expected = array(
			'Lithium\Tests\Cases\G11n\CatalogTest',
			'Lithium\Tests\Cases\G11n\MessageTest',
			'Lithium\Tests\Cases\Console\Command\G11n\ExtractTest'
		);
		$result = $tests->map('get_class', array('collect' => false));
		$this->assertEqual($expected, $result);
	}

	public function testAnalyze() {
		$ns = 'Lithium\Tests\Cases';

		$expected = array(
			'Lithium\G11n\Message' => "{$ns}\g11n\MessageTest",
			'Lithium\Console\Command\G11n\Extract' => "{$ns}\console\command\g11n\ExtractTest"
		);

		$group = new Group();
		$group->add('Lithium\Tests\Cases\G11n\CatalogTest');
		$this->report->group = $group;
		$tests = Affected::apply($this->report, $group->tests());
		$results = Affected::analyze($this->report);

		$this->assertEqual($results, $expected);
	}
}

?>