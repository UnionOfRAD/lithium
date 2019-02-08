<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test;

use lithium\test\Mocker;

/**
 * @deprecated
 */
class MockerChainTest extends \lithium\test\Unit {

	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);
		Mocker::register();
	}

	public function tearDown() {
		error_reporting($this->_backup);
	}

	public function testStartSuccessful() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$chain = Mocker::chain($mock);

		$this->assertTrue($chain->success());
	}

	public function testStaticSuccessful() {
		$class = '\lithium\tests\mocks\test\mockStdStaticClass\Mock';
		$class::applyFilter(false);
		$chain = Mocker::chain($class);

		$this->assertTrue($chain->success());
	}

	public function testBasicStaticCalled() {
		$class = '\lithium\tests\mocks\test\mockStdStaticClass\Mock';
		$class::applyFilter(false);
		$class::method1();
		$chain = Mocker::chain($class);

		$this->assertTrue($chain->called('method1')->success());
	}

	public function testFunctionSuccessful() {
		Mocker::overwriteFunction('app\extensions\file_get_contents', function() {
			return 'foo';
		});
		\app\extensions\file_get_contents();

		$chain = Mocker::chain('app\extensions\file_get_contents');
		$this->assertTrue($chain->called('app\extensions\file_get_contents')->with()->success());
	}

	public function testBasicNotCalled() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$chain = Mocker::chain($mock);

		$this->assertFalse($chain->called('method1')->success());
	}

	public function testBasicCalled() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$mock->method1();
		$chain = Mocker::chain($mock);

		$this->assertTrue($chain->called('method1')->success());
	}

	public function testCalledWith() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$mock->method1('foo');
		$chain = Mocker::chain($mock);

		$this->assertTrue($chain->called('method1')->success());
		$this->assertFalse($chain->called('method1')->with('bar')->success());
		$this->assertTrue($chain->called('method1')->with('foo')->success());
		$this->assertFalse($chain->called('method1')->with('foo', 'bar')->success());
	}

	public function testMethodCalledBefore() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$mock->method1();
		$mock->method2();
		$mock->method1();
		$chain = Mocker::chain($mock);

		$this->assertTrue($chain->called('method1')
			->called('method2')
			->called('method1')
			->success()
		);
		$this->assertFalse($chain->called('method2')
			->called('method1')
			->called('method1')
			->success()
		);
	}

	public function testMethodWithParamsCalledBefore() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$mock->method1('foo');
		$mock->method2('bar');
		$mock->method1('baz');
		$chain = Mocker::chain($mock);

		$this->assertTrue($chain->called('method1')
			->called('method2')->with('bar')
			->called('method1')
			->success()
		);
		$this->assertFalse($chain->called('method1')->with('bar')
			->called('method2')->with('bar')
			->called('method1')
			->success()
		);
		$this->assertFalse($chain->called('method1')
			->called('method2')->with('bar')
			->called('method1')->with('bar')
			->success()
		);
	}

	public function testMethodCalledSpecificTimes() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$mock->method1();
		$mock->method2();
		$mock->method1();
		$chain = Mocker::chain($mock);

		$this->assertFalse($chain->called('method2')->eq(2)->success());
		$this->assertTrue($chain->called('method1')->eq(2)->success());
		$this->assertTrue($chain->called('method1')->gt(0)->success());
		$this->assertTrue($chain->called('method1')->gte(1)->success());
		$this->assertTrue($chain->called('method1')->lt(3)->success());
		$this->assertTrue($chain->called('method1')->lte(2)->success());
		$this->assertFalse($chain->called('method1')->lte(1)->success());
	}

	public function testMultipleCallsWithArgsAndSpecificCalled() {
		$mock = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$mock->method1('foo', 'bar');
		$mock->method1('foo', 'bar');
		$mock->method1('foo', 'bar');
		$mock->method2('baz');
		$mock->method2('baz');
		$mock->method1();
		$chain = Mocker::chain($mock);

		$this->assertTrue($chain->called('method1')->with('foo', 'bar')->eq(3)->success());
		$this->assertTrue($chain->called('method2')->with('baz')->eq(2)->success());
		$this->assertTrue($chain->called('method1')->with()->eq(1)->success());

		$this->assertTrue($chain->called('method1')->with('foo', 'bar')->eq(3)
			->called('method2')->with('baz')->eq(2)
			->called('method1')->with()->eq(1)->success()
		);
	}

	public function testRespondsToParentCall() {
		$chain = Mocker::chain([]);
		$this->assertTrue($chain->respondsTo('applyFilter'));
		$this->assertFalse($chain->respondsTo('fooBarBaz'));
	}

	public function testRespondsToMagic() {
		$chain = Mocker::chain([]);
		$this->assertTrue($chain->respondsTo('gt'));
		$this->assertTrue($chain->respondsTo('lt'));
		$this->assertFalse($chain->respondsTo('et'));
	}

}

?>