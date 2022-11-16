<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test;

use lithium\test\Group;
use lithium\core\Libraries;
use lithium\util\Collection;
use lithium\tests\cases\data\ModelTest;
use lithium\tests\mocks\test\cases\MockTest;
use lithium\tests\mocks\test\cases\MockErrorHandlingTest;
use lithium\tests\mocks\test\cases\MockSkipThrowsExceptionTest;
use lithium\tests\mocks\test\cases\MockSetUpThrowsExceptionTest;
use lithium\tests\mocks\test\cases\MockTearDownThrowsExceptionTest;

class GroupTest extends \lithium\test\Unit {

	public function testAdd() {
		$group = new Group();

		$expected = new Collection();
		$result = $group->tests();
		$this->assertEqual($expected, $result);
	}

	public function testAddCaseThroughConstructor() {
		$data = (array) 'lithium\tests\mocks\test';
		$group = new Group(compact('data'));

		$expected = new Collection(['data' => [
			new MockErrorHandlingTest(),
			new MockSetUpThrowsExceptionTest(),
			new MockSkipThrowsExceptionTest(),
			new MockTearDownThrowsExceptionTest(),
			new MockTest()
		]]);
		$result = $group->tests();

		$this->assertEqual($expected, $result);
	}

	public function testAddEmpty() {
		$group = new Group();
		$group->add('');
		$group->add('\\');
		$group->add('foobar');
		$this->assertEmpty($group->items());
	}

	public function testAddByString() {
		$group = new Group();
		$result = $group->add('lithium\tests\cases\g11n');
		$expected = [
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\LocaleTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\g11n\MultibyteTest',
			'lithium\tests\cases\g11n\multibyte\adapter\IconvTest',
			'lithium\tests\cases\g11n\multibyte\adapter\IntlTest',
			'lithium\tests\cases\g11n\multibyte\adapter\MbstringTest',
			'lithium\tests\cases\g11n\catalog\AdapterTest',
			'lithium\tests\cases\g11n\catalog\adapter\CodeTest',
			'lithium\tests\cases\g11n\catalog\adapter\GettextTest',
			'lithium\tests\cases\g11n\catalog\adapter\MemoryTest',
			'lithium\tests\cases\g11n\catalog\adapter\PhpTest'
		];
		$this->assertEqual($expected, $result);

		$result = $group->add('lithium\tests\cases\data\ModelTest');
		$expected = [
			'lithium\tests\cases\g11n\CatalogTest',
			'lithium\tests\cases\g11n\LocaleTest',
			'lithium\tests\cases\g11n\MessageTest',
			'lithium\tests\cases\g11n\MultibyteTest',
			'lithium\tests\cases\g11n\multibyte\adapter\IconvTest',
			'lithium\tests\cases\g11n\multibyte\adapter\IntlTest',
			'lithium\tests\cases\g11n\multibyte\adapter\MbstringTest',
			'lithium\tests\cases\g11n\catalog\AdapterTest',
			'lithium\tests\cases\g11n\catalog\adapter\CodeTest',
			'lithium\tests\cases\g11n\catalog\adapter\GettextTest',
			'lithium\tests\cases\g11n\catalog\adapter\MemoryTest',
			'lithium\tests\cases\g11n\catalog\adapter\PhpTest',
			'lithium\tests\cases\data\ModelTest'
		];
		$this->assertEqual($expected, $result);
	}

	public function testAddByMixedThroughConstructor() {
		$group = new Group(['data' => [
			'lithium\tests\cases\data\ModelTest', new MockTest()
		]]);
		$expected = new Collection(['data' => [new ModelTest(), new MockTest()]]);
		$result = $group->tests();
		$this->assertEqual($expected, $result);
	}

	public function testTests() {
		$group = new Group();
		$expected = [
			'lithium\tests\cases\g11n\CatalogTest'
		];
		$result = $group->add('lithium\tests\cases\g11n\CatalogTest');
		$this->assertEqual($expected, $result);

		$results = $group->tests();
		$this->assertInstanceOf('lithium\util\Collection', $results);

		$results = $group->tests();
		$this->assertInstanceOf('lithium\tests\cases\g11n\CatalogTest', $results->current());
	}

	public function testAddEmptyTestsRun() {
		$group = new Group();
		$result = $group->add('lithium\tests\mocks\test\MockUnitTest');
		$expected = ['lithium\tests\mocks\test\MockUnitTest'];
		$this->assertEqual($expected, $result);

		$results = $group->tests();
		$this->assertInstanceOf('lithium\util\Collection', $results);
		$this->assertInstanceOf('lithium\tests\mocks\test\MockUnitTest', $results->current());

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
		$this->assertEqual($expected, $result);
	}

	public function testGroupAllForLithium() {
		Libraries::cache(false);
		$result = Group::all(['library' => 'lithium']);
		$this->assertTrue(count($result) >= 60);
	}

	public function testAddTestAppGroup() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp, 0777, true);
		Libraries::add('test_app', ['path' => $testApp]);

		mkdir($testApp . '/tests/cases/models', 0777, true);
		file_put_contents($testApp . '/tests/cases/models/UserTest.php',
			"<?php namespace test_app\\tests\\cases\\models;\n
			class UserTest extends \\lithium\\test\\Unit { public function testMe() {
				\$this->assertTrue(true);
			}}"
		);
		Libraries::cache(false);

		$expected = (array) Libraries::find('test_app', [
			'recursive' => true,
			'path' => '/tests',
			'filter' => '/cases|integration|functional/'
		]);

		Libraries::cache(false);

		$group = new Group();
		$result = $group->add('test_app');
		$this->assertEqual($expected, $result);

		Libraries::cache(false);
		$this->_cleanUp();
	}

	public function testRunGroupAllForTestApp() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp);
		Libraries::add('test_app', ['path' => $testApp]);

		mkdir($testApp . '/tests/cases/models', 0777, true);
		file_put_contents($testApp . '/tests/cases/models/UserTest.php',
			"<?php namespace test_app\\tests\\cases\\models;\n
			class UserTest extends \\lithium\\test\\Unit { public function testMe() {
				\$this->assertTrue(true);
			}}"
		);
		Libraries::cache(false);

		$expected = ['test_app\tests\cases\models\UserTest'];
		$result = Group::all(['library' => 'test_app']);
		$this->assertEqual($expected, $result);

		Libraries::cache(false);
		$this->_cleanUp();
	}

	public function testRunGroupForTestAppModel() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp);
		Libraries::add('test_app', ['path' => $testApp]);

		mkdir($testApp . '/tests/cases/models', 0777, true);
		file_put_contents($testApp . '/tests/cases/models/UserTest.php',
			"<?php namespace test_app\\tests\\cases\\models;\n
			class UserTest extends \\lithium\\test\\Unit { public function testMe() {
				\$this->assertTrue(true);
			}}"
		);
		Libraries::cache(false);

		$group = new Group(['data' => ['test_app\tests\cases']]);

		$expected = ['test_app\tests\cases\models\UserTest'];
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