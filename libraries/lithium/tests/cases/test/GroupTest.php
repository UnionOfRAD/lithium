<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use \lithium\test\Group;
use \lithium\util\Collection;

class GroupTest extends \lithium\test\Unit {

	public function testAdd() {
		$group = new Group();
		$result = $group->add('\g11n');
		$expected = array(
		  'lithium\tests\cases\g11n\CatalogTest',
		  'lithium\tests\cases\g11n\LocaleTest',
		  'lithium\tests\cases\g11n\MessageTest',
		  'lithium\tests\cases\g11n\catalog\adapters\CldrTest',
		  'lithium\tests\cases\g11n\catalog\adapters\CodeTest',
		  'lithium\tests\cases\g11n\catalog\adapters\GettextTest',
		);
		$this->assertEqual($expected, $result);

		$result = $group->add('data\ModelTest');
		$expected = array(
		  'lithium\tests\cases\g11n\CatalogTest',
		  'lithium\tests\cases\g11n\LocaleTest',
		  'lithium\tests\cases\g11n\MessageTest',
		  'lithium\tests\cases\g11n\catalog\adapters\CldrTest',
		  'lithium\tests\cases\g11n\catalog\adapters\CodeTest',
		  'lithium\tests\cases\g11n\catalog\adapters\GettextTest',
		  'lithium\tests\cases\data\ModelTest'
		);
		$this->assertEqual($expected, $result);

		$group = new Group();
		$result = $group->add();
		$this->assertEqual($group->tests(), new Collection());

		$expected = new Collection(array('items' => array(
			new \lithium\tests\cases\data\ModelTest(),
			new \lithium\tests\cases\core\ObjectTest()
		)));

		$group = new Group(array('items' => array(
			'data\ModelTest',
			new \lithium\tests\cases\core\ObjectTest()
		)));
		$this->assertEqual($expected, $group->tests());

		$group = new Group(array('items' => array(array(
			'Data\ModelTest',
			new \lithium\tests\cases\core\ObjectTest()
		))));
		$this->assertEqual($group->tests(), $expected);
	}

	public function testTests() {
		$group = new Group();
		$result = $group->add('g11n\CatalogTest');
		$expected = array(
			'lithium\tests\cases\g11n\CatalogTest',
		);
		$this->assertEqual($expected, $result);

		$results = $group->tests();
		$this->assertTrue(is_a($results, '\lithium\util\Collection'));

		$results = $group->tests();
		$this->assertTrue(is_a($results->current(), 'lithium\tests\cases\g11n\CatalogTest'));
	}

	public function testTestsRun() {
		$group = new Group();
		$result = $group->add('test\MockTestInGroupTest');
		$expected = array(
			'lithium\tests\cases\test\MockTestInGroupTest',
		);
		$this->assertEqual($expected, $result);

		$results = $group->tests();
		$this->assertTrue(is_a($results, '\lithium\util\Collection'));

		$results = $group->tests();
		$this->assertTrue(is_a($results->current(), 'lithium\tests\cases\test\MockTestInGroupTest'));

		$results = $group->tests()->run();
		$this->assertEqual($results[0][0]['result'], 'pass');
		$this->assertEqual($results[0][0]['method'], 'testNothing');
		$this->assertEqual($results[0][0]['file'], __FILE__);
		$this->assertEqual($results[0][0]['class'], 'lithium\tests\cases\test\MockTestInGroupTest');
	}

	public function testQueryAllTests() {
		$result = Group::all(array('library' => 'lithium'));
		$this->assertEqual(60, count($result));
	}
}

class MockTestInGroupTest extends \lithium\test\Unit {

	public function testNothing() {
		$this->assertTrue(true);
	}
}

?>