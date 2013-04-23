<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use Closure;
use Exception;
use UnexpectedValueException;
use lithium\core\ErrorHandler;
use lithium\tests\mocks\core\MockErrorHandler;

class ErrorHandlerTest extends \lithium\test\Unit {

	public $errors = array();

	public function setUp() {
		if (!ErrorHandler::isRunning()) {
			ErrorHandler::run();
		}
		ErrorHandler::reset();
		$this->errors = array();
	}

	public function tearDown() {
		if (ErrorHandler::isRunning()) {
			ErrorHandler::stop();
		}
	}

	public function testExceptionCatching() {
		$self = $this;
		ErrorHandler::config(array(array(
			'type' => 'Exception',
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			}
		)));

		ErrorHandler::handle(new Exception('Test!'));

		$this->assertCount(1, $this->errors);
		$result = end($this->errors);
		$expected = 'Test!';
		$this->assertEqual($expected, $result['message']);

		$this->expectException('/Test/');
		trigger_error('Test warning!', E_USER_WARNING);
		$this->assertCount(1, $this->errors);
	}

	public function testExceptionSubclassCatching() {
		$self = $this;
		ErrorHandler::config(array(array(
			'type' => 'Exception',
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			}
		)));
		ErrorHandler::handle(new UnexpectedValueException('Test subclass'));

		$this->assertCount(1, $this->errors);
		$result = end($this->errors);
		$expected = 'Test subclass';
		$this->assertEqual($expected, $result['message']);
	}

	public function testErrorCatching() {
		$this->skipIf(true, 'Refactoring original error-handling iteration.');

		$self = $this;
		ErrorHandler::config(array(array(
			'code' => E_WARNING | E_USER_WARNING,
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			}
		)));

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
		$subject = new ErrorHandlerTest();
		ErrorHandler::apply(array($subject, 'throwException'), array(), function($details) {
			return $details['exception']->getMessage();
		});
		$this->assertEqual('foo', $subject->throwException());
	}

	public function throwException() {
		return $this->_filter(__METHOD__, array(), function($self, $params) {
			throw new Exception('foo');
			return 'bar';
		});
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

		$checks = MockErrorHandler::checks(array('foo' => 'bar'));
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
		ErrorHandler::config(array(array(
			'handler' => function($info) use ($self) {
				$self->errors[] = $info;
			})
		));
		ErrorHandler::run(array('trapErrors' => true));

		$this->assertCount(0, $this->errors);
		list($foo, $bar) = array('baz');
		$this->assertCount(1, $this->errors);
	}

	public function testRenderedOutput() {
		ob_start();
		echo 'Some Output';
		$subject = new ErrorHandlerTest();
		ErrorHandler::apply(array($subject, 'throwException'), array(), function($details) {});
		$subject->throwException();
		$this->assertEmpty(ob_get_length());
	}
}

?>