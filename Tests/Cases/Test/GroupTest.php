<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Test;

use Lithium\Test\Group;
use Lithium\Util\Collection;
use Lithium\Core\Libraries;

class GroupTest extends \Lithium\Test\Unit {

	public function testAdd() {
		$group = new Group();

		$expected = new Collection();
		$result = $group->tests();
		$this->assertEqual($expected, $result);
	}

	public function testAddCaseThroughConstructor() {
		$data = (array) "\Lithium\\Tests\Mocks\\Test";
		$group = new Group(compact('data'));

		$expected = new Collection(array(
			'data' => array(
				new \Lithium\Tests\Mocks\Test\Cases\MockSkipThrowsException(),
				new \Lithium\Tests\Mocks\Test\Cases\MockTest(),
				new \Lithium\Tests\Mocks\Test\Cases\MockTestErrorHandling()
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
		$result = $group->add('Lithium\Tests\Cases\G11n');
		$expected = array(
			'Lithium\Tests\Cases\G11n\CatalogTest',
			'Lithium\Tests\Cases\G11n\LocaleTest',
			'Lithium\Tests\Cases\G11n\MessageTest',
			'Lithium\Tests\Cases\G11n\Catalog\AdapterTest',
			'Lithium\Tests\Cases\G11n\Catalog\Adapter\CodeTest',
			'Lithium\Tests\Cases\G11n\Catalog\Adapter\GettextTest',
			'Lithium\Tests\Cases\G11n\Catalog\Adapter\PhpTest'
		);
		$this->assertEqual($expected, $result);

		$result = $group->add('Lithium\Tests\Cases\Data\ModelTest');
		$expected = array(
			'Lithium\Tests\Cases\G11n\CatalogTest',
			'Lithium\Tests\Cases\G11n\LocaleTest',
			'Lithium\Tests\Cases\G11n\MessageTest',
			'Lithium\Tests\Cases\G11n\Catalog\AdapterTest',
			'Lithium\Tests\Cases\G11n\Catalog\Adapter\CodeTest',
			'Lithium\Tests\Cases\G11n\Catalog\Adapter\GettextTest',
			'Lithium\Tests\Cases\G11n\Catalog\Adapter\PhpTest',
			'Lithium\Tests\Cases\Data\ModelTest'
		);
		$this->assertEqual($expected, $result);
	}

	public function testAddByMixedThroughConstructor() {
		$group = new Group(array('data' => array(
			'Lithium\Tests\Cases\Data\ModelTest',
			new \Lithium\Tests\Cases\Core\ObjectTest()
		)));
		$expected = new Collection(array('data' => array(
			new \Lithium\Tests\Cases\Data\ModelTest(),
			new \Lithium\Tests\Cases\Core\ObjectTest()
		)));
		$result = $group->tests();
		$this->assertEqual($expected, $result);
	}

	public function testTests() {
		$group = new Group();
		$expected = array(
			'Lithium\Tests\Cases\G11n\CatalogTest'
		);
		$result = $group->add('Lithium\Tests\Cases\G11n\CatalogTest');
		$this->assertEqual($expected, $result);

		$results = $group->tests();
		$this->assertTrue(is_a($results, '\Lithium\Util\Collection'));

		$results = $group->tests();
		$this->assertTrue(is_a($results->current(), 'Lithium\Tests\Cases\G11n\CatalogTest'));
	}

	public function testAddEmptyTestsRun() {
		$group = new Group();
		$result = $group->add('Lithium\Tests\Mocks\Test\MockUnitTest');
		$expected = array('Lithium\Tests\Mocks\Test\MockUnitTest');
		$this->assertEqual($expected, $result);

		$results = $group->tests();
		$this->assertTrue(is_a($results, 'Lithium\Util\Collection'));
		$this->assertTrue(is_a($results->current(), 'Lithium\Tests\Mocks\Test\MockUnitTest'));

		$results = $group->tests()->run();

		$expected = 'pass';
		$result = $results[0][0]['result'];
		$this->assertEqual($expected, $result);

		$expected = 'testNothing';
		$result = $results[0][0]['method'];
		$this->assertEqual($expected, $result);

		$expected = 'Lithium\Tests\Mocks\Test\MockUnitTest';
		$result = $results[0][0]['class'];
		$this->assertEqual($expected, $result);

		$expected = str_replace('\\', '/', LITHIUM_LIBRARY_PATH);
		$expected = realpath($expected . '/Lithium/Tests/Mocks/Test/MockUnitTest.php');
		$result = $results[0][0]['file'];
		$this->assertEqual($expected, str_replace('\\', '/', $result));
	}

	public function testGroupAllForLithium() {
		Libraries::cache(false);
		$result = Group::all(array('library' => 'Lithium'));
		$this->assertTrue(count($result) >= 60);
	}

	public function testAddTestAppGroup() {
		$test_app = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($test_app);
		Libraries::add('test_app', array('path' => $test_app));

		mkdir($test_app . '/Tests/Cases/Models', 0777, true);
		file_put_contents($test_app . '/Tests/Cases/Models/UserTest.php',
		"<?php namespace test_app\\tests\\cases\\models;\n
			class UserTest extends \\Lithium\\Test\\Unit { public function testMe() {
				\$this->assertTrue(true);
			}}"
		);
		Libraries::cache(false);

		$expected = (array) Libraries::find('test_app', array(
			'recursive' => true,
			'path' => '/Tests',
			'filter' => '/\\\(Cases|Integration|Functional)\\\/'
		));

		Libraries::cache(false);

		$group = new Group();
		$result = $group->add('test_app');
		$this->assertEqual($expected, $result);

		Libraries::cache(false);
		$this->_cleanUp();
	}

	public function testRunGroupAllForTestApp() {
		$test_app = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($test_app);
		Libraries::add('test_app', array('path' => $test_app));

		mkdir($test_app . '/Tests/Cases/Models', 0777, true);
		file_put_contents($test_app . '/Tests/Cases/Models/UserTest.php',
		"<?php namespace test_app\\tests\\cases\\models;\n
			class UserTest extends \\Lithium\\Test\\Unit { public function testMe() {
				\$this->assertTrue(true);
			}}"
		);
		Libraries::cache(false);

		$expected = array('test_app\\Tests\\Cases\\Models\\UserTest');
		$result = Group::all(array('library' => 'test_app'));
	    $this->assertEqual($expected, $result);

		Libraries::cache(false);
		$this->_cleanUp();
	}

	public function testRunGroupForTestAppModel() {
		$test_app = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($test_app);
		Libraries::add('test_app', array('path' => $test_app));

		mkdir($test_app . '/Tests/Cases/Models', 0777, true);
		file_put_contents($test_app . '/Tests/Cases/Models/UserTest.php',
		"<?php namespace test_app\\Tests\\Cases\\Models;\n
			class UserTest extends \\Lithium\\Test\\Unit { public function testMe() {
				\$this->assertTrue(true);
			}}"
		);
		Libraries::cache(false);

		$group = new Group(array('data' => array('\\test_app\\Tests\\Cases')));

		$expected = array('test_app\\Tests\\Cases\\Models\\UserTest');
		$result = $group->to('array');
	    $this->assertEqual($expected, $result);

		$expected = 'pass';
		$result = $group->tests()->run();
	    $this->assertEqual($expected, $result[0][0]['result']);

		Libraries::cache(false);
		$this->_cleanUp();
	}
}

?>
