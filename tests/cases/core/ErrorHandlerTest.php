<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

use Closure;
use Exception;
use UnexpectedValueException;
use lithium\core\ErrorHandler;
use lithium\aop\Filters;
use lithium\tests\mocks\core\MockStatic;
use lithium\tests\mocks\core\MockErrorHandler;

class ErrorHandlerTest extends \lithium\test\Unit {

	public $errors = [];

	public function setUp() {
		if (!ErrorHandler::isRunning()) {
			ErrorHandler::run();
		}
		ErrorHandler::reset();
		$this->errors = [];
	}

	public function tearDown() {
		if (ErrorHandler::isRunning()) {
			ErrorHandler::stop();
		}
	}

	public function testExceptionCatching() {
		$self = $this;
		ErrorHandler::config([[
			'type' => 'Exception',
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			}
		]]);

		ErrorHandler::handle(new Exception('Test!'));

		$this->assertCount(1, $this->errors);
		$result = end($this->errors);
		$expected = 'Test!';
		$this->assertEqual($expected, $result['message']);

		$backup = error_reporting();
		error_reporting($backup | E_WARNING);

		$this->assertException('/Test/', function() {
			trigger_error('Test warning!', E_USER_WARNING);
		});
		$this->assertCount(1, $this->errors);

		error_reporting($backup);
	}

	public function testExceptionSubclassCatching() {
		$self = $this;
		ErrorHandler::config([[
			'type' => 'Exception',
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			}
		]]);
		ErrorHandler::handle(new UnexpectedValueException('Test subclass'));

		$this->assertCount(1, $this->errors);
		$result = end($this->errors);
		$expected = 'Test subclass';
		$this->assertEqual($expected, $result['message']);
	}

	public function testErrorCatching() {
		$this->skipIf(true, 'Refactoring original error-handling iteration.');

		$self = $this;
		ErrorHandler::config([[
			'code' => E_WARNING | E_USER_WARNING,
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			}
		]]);

		file_get_contents(false);
		$this->assertCount(1, $this->errors);

		$result = end($this->errors);
		$this->assertPattern('/Filename cannot be empty/', $result['message']);

		trigger_error('Test warning', E_USER_WARNING);
		$this->assertCount(2, $this->errors);

		$result = end($this->errors);
		$this->assertEqual('Test warning', $result['message']);

		trigger_error('Test notice', E_USER_NOTICE);
		$this->assertCount(2, $this->errors);
	}

	public function testApply() {
		$class = 'lithium\tests\mocks\core\MockStatic';

		ErrorHandler::apply("{$class}::throwException", [], function($details) {
			return $details['exception']->getMessage();
		});
		$this->assertEqual('foo', MockStatic::throwException());

		Filters::clear('lithium\tests\mocks\core\MockStatic');
	}

	public function testTrace() {
		$current = debug_backtrace();
		$results = ErrorHandler::trace($current);
		$this->assertEqual(count($current), count($results));
		$this->assertEqual($results[0], 'lithium\tests\cases\core\ErrorHandlerTest::testTrace');
	}

	public function testRun() {
		ErrorHandler::stop();
		$this->assertEqual(ErrorHandler::isRunning(), false);
		ErrorHandler::run();
		$this->assertEqual(ErrorHandler::isRunning(), true);
		$result = ErrorHandler::run();
		$this->assertEqual(ErrorHandler::isRunning(), true);
		$this->assertNull($result);
		ErrorHandler::stop();
		$this->assertEqual(ErrorHandler::isRunning(), false);
	}

	public function testReset() {
		$checks = MockErrorHandler::checks();

		$defaultChecks = 4;
		$this->assertEqual($defaultChecks, count($checks));
		$this->assertInstanceOf('Closure', $checks['type']);

		$checks = MockErrorHandler::checks(['foo' => 'bar']);
		$this->assertCount(1, $checks);
		$this->assertFalse(isset($checks['type']));

		MockErrorHandler::reset();

		$checks = MockErrorHandler::checks();
		$this->assertEqual($defaultChecks, count($checks));
		$this->assertInstanceOf('Closure', $checks['type']);
	}

	public function testErrorTrapping() {
		ErrorHandler::stop();
		$self = $this;
		ErrorHandler::config([[
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			}]
		]);
		ErrorHandler::run(['trapErrors' => true]);

		$this->assertCount(0, $this->errors);
		list($foo, $bar) = ['baz'];
		$this->assertCount(1, $this->errors);
	}

	public function testRenderedOutput() {
		$class = 'lithium\tests\mocks\core\MockStatic';

		ob_start();
		echo 'Some Output';
		ErrorHandler::apply("{$class}::throwException", [], function($details) {});
		MockStatic::throwException();
		$this->assertEmpty(ob_get_length());
	}
}

?>