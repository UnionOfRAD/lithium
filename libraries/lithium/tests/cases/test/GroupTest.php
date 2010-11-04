<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use \lithium\test\Group;
use \lithium\util\Collection;

class GroupTest extends \lithium\test\Unit {

	public function testAdd() {
		$group = new Group();

		$expected = new Collection();
		$result = $group->tests();
		$this->assertEqual($expected, $result);
	}

	public function testAddCaseThroughConstructor() {
		$data = (array) "\lithium\\tests\mocks\\test";
		$group = new Group(compact('data'));

		$expected = new Collection(array(
			'data' => array(
				new \lithium\tests\mocks\test\cases\MockSkipThrowsException(),
				new \lithium\tests\mocks\test\cases\MockTest(),
				new \lithium\tests\mocks\test\cases\MockTestErrorHandling()
			)
		));
		$result = $group->tests();
		$this->assertEqual($expected, $result);
	}

	public function testAddEmpty() {
		$group = new Group();
		$group->add('');
		$group->add('\\');
		$group->add('foobar');
		$this->assertFalse($group->items());
	}

	public function testAddByString() {
		$group = new Group();
		$result = $group->add('g11n');
		$expected = array(
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\LocaleTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\g11n\catalog\AdapterTest',
			'lithium\tests\cases\g11n\catalog\adapter\CodeTest',
			'lithium\tests\cases\g11n\catalog\adapter\GettextTest',
			'lithium\tests\cases\g11n\catalog\adapter\PhpTest',
		);
		$this->assertEqual($expected, $result);

		$result = $group->add('data\ModelTest');
		$expected = array(
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\LocaleTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\g11n\catalog\AdapterTest',
			'lithium\tests\cases\g11n\catalog\adapter\CodeTest',
			'lithium\tests\cases\g11n\catalog\adapter\GettextTest',
			'lithium\tests\cases\g11n\catalog\adapter\PhpTest',
			'lithium\tests\cases\data\ModelTest'
		);
		$this->assertEqual($expected, $result);
	}

	public function testAddByMixedThroughConstructor() {
		$expected = new Collection(array('data' => array(
			new \lithium\tests\cases\data\ModelTest(),
			new \lithium\tests\cases\core\ObjectTest()
		)));

		$group = new Group(array('data' => array(
			'data\ModelTest',
			new \lithium\tests\cases\core\ObjectTest()
		)));
		$this->assertEqual($expected, $group->tests());

		$group = new Group(array('data' => array(array(
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
		$result = $group->add('lithium\tests\mocks\test\MockUnitTest');
		$expected = array('lithium\tests\mocks\test\MockUnitTest');
		$this->assertEqual($expected, $result);

		$results = $group->tests();
		$this->assertTrue(is_a($results, 'lithium\util\Collection'));

		$results = $group->tests();
		$this->assertTrue(is_a($results->current(), 'lithium\tests\mocks\test\MockUnitTest'));

		$results = $group->tests()->run();

		$expected = 'pass';
		$result = $results[0][0]['result'];
		$this->assertEqual($expected, $result);

		$expected = 'testNothing';
		$result = $results[0][0]['method'];
		$this->assertEqual($expected, $result);

		$expected = 'lithium\tests\mocks\test\MockUnitTest';
		$result = $results[0][0]['class'];
		$this->assertEqual($expected, $result);

		$expected = str_replace('\\', '/', LITHIUM_LIBRARY_PATH);
		$expected = realpath($expected . '/lithium/tests/mocks/test/MockUnitTest.php');
		$result = $results[0][0]['file'];
		$this->assertEqual($expected, str_replace('\\', '/', $result));
	}

	public function testQueryAllTests() {
		$result = Group::all(array('library' => 'lithium'));
		$this->assertTrue(count($result) >= 60);
	}
}

?>