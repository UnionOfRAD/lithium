<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

use Throwable;
use Exception;
use ErrorException;
use ReflectionClass;
use InvalidArgumentException;
use lithium\aop\Filters;
use lithium\util\Text;
use lithium\core\Libraries;
use lithium\util\Validator;
use lithium\analysis\Debugger;
use lithium\analysis\Inspector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use lithium\core\AutoConfigurable;

/**
 * This is the base class for all test cases. Test are performed using an assertion method.
 * If the assertion is correct, the test passes, otherwise it fails. Most assertions take an
 * expected result, a received result, and a message (to describe the failure) as parameters.
 *
 * Unit tests are used to check a small unit of functionality, such as if a method returns an
 * expected result for a known input, or whether an adapter can successfully open a connection.
 *
 * Available assertions are (see `assert<assertion-name>` methods for details): Equal, False,
 * Identical, NoPattern, NotEqual, Null, Pattern, Tags, True.
 *
 * If an assertion is expected to produce an exception, `assertException()` should be used.
 *
 * @see lithium\test\Unit::assertException()
 */
class Unit {

	use AutoConfigurable;

	/**
	 * The Reference to a test reporter class.
	 *
	 * @var object
	 */
	protected $_reporter = null;

	/**
	 * The list of test results.
	 *
	 * @var string
	 */
	protected $_results = [];

	/**
	 * Internal types and how to test for them
	 *
	 * @var array
	 */
	protected static $_internalTypes = [
		'array' => 'is_array',
		'bool' => 'is_bool',
		'boolean' => 'is_bool',
		'callable' => 'is_callable',
		'double' => 'is_double',
		'float' => 'is_float',
		'int' => 'is_int',
		'integer' => 'is_integer',
		'long' => 'is_long',
		'null' => 'is_null',
		'numeric' => 'is_numeric',
		'object' => 'is_object',
		'real' => 'is_real',
		'resource' => 'is_resource',
		'scalar' => 'is_scalar',
		'string' => 'is_string'
	];

	/**
	 * Finds the test case for the corresponding class name.
	 *
	 * @param string $class A fully-namespaced class reference for which to find a test case.
	 * @return string Returns the class name of a test case for `$class`, or `null` if none exists.
	 */
	public static function get($class) {
		$parts = explode('\\', $class);

		$library = array_shift($parts);
		$name = array_pop($parts);
		$type = 'tests.cases.' . implode('.', $parts);

		return Libraries::locate($type, $name, compact('library'));
	}

	/**
	 * Setup method run before every test method. Override in subclasses.
	 *
	 * @return void
	 */
	public function setUp() {}

	/**
	 * Teardown method run after every test method. Override in subclasses.
	 *
	 * @return void
	 */
	public function tearDown() {}

	/**
	 * Subclasses should use this method to set conditions that, if failed, terminate further
	 * testing.
	 *
	 * For example:
	 * ```
	 * public function skip() {
	 *     $connection = Connections::get('test', ['config' => true]);
	 *     $this->skipIf(!$connection, 'Test database is unavailable.');
	 * }
	 * ```
	 */
	public function skip() {}

	/**
	 * Skips test(s) if the condition is met.
	 *
	 * When used within a subclass' `skip` method, all tests are ignored if the condition is
	 * met, otherwise processing continues as normal. For other methods, only the remainder of
	 * the method is skipped, when the condition is met.
	 *
	 * @throws Exception
	 * @param boolean $condition
	 * @param string|boolean $message Message to pass if the condition is met.
	 * @return mixed
	 */
	public function skipIf($condition, $message = false) {
		if ($condition) {
			throw new Exception(is_string($message) ? $message : null);
		}
	}

	/**
	 * Returns the class name that is the subject under test for this test case.
	 *
	 * @return string
	 */
	public function subject() {
		return preg_replace('/Test$/', '', str_replace('tests\\cases\\', '', get_class($this)));
	}

	/**
	 * Return test methods to run.
	 *
	 * @return array
	 */
	public function methods() {
		return array_values(preg_grep('/^test/', get_class_methods($this)));
	}

	/**
	 * Returns the current results.
	 *
	 * @return array The Results... currently.
	 */
	public function results() {
		return $this->_results;
	}

	/**
	 * Runs the test methods in this test case, with the given options.
	 *
	 * Installs a temporary error handler that will convert regular errors to
	 * exceptions in order to make both errors and exceptions be handled
	 * in a unified way. ErrorExceptions created like this, will get the
	 * error's code as their severity. As this comes closest to their meaning.
	 *
	 * The error handler honors the PHP `error_level` and will not convert errors
	 * to exceptions if they are masked by the `error_level`. This allows test
	 * methods to run assertions against i.e. deprecated functions. Usually
	 * the error_level is set by the test runner so that all errors are converted.
	 *
	 * @link http://php.net/manual/function.error-reporting.php
	 * @param array $options The options to use when running the test. Available options are:
	 *             - `'methods'`: An arbitrary array of method names to execute. If
	 *                unspecified, all methods starting with 'test' are run.
	 *             - `'reporter'`: A closure which gets called after each test result,
	 *                which may modify the results presented.
	 *             - `'handler'`: A closure which gets registered as the temporary error handler.
	 * @return array
	 */
	public function run(array $options = []) {
		$defaults = [
			'methods' => $this->methods(),
			'reporter' => $this->_reporter,
			'handler' => function($code, $message, $file = null, $line = null) {
				if (error_reporting() & $code) {
					throw new ErrorException($message, 0, $code, $file, $line);
				}
			}
		];
		$options += $defaults;
		$this->_results = [];
		$this->_reporter = $options['reporter'];

		try {
			$this->skip();
		} catch (Throwable $e) {
			$this->_handleException($e);
			return $this->_results;
		}

		set_error_handler($options['handler']);
		foreach ($options['methods'] as $method) {
			if ($this->_runTestMethod($method, $options) === false) {
				break;
			}
		}
		restore_error_handler();
		return $this->_results;
	}

	/**
	 * General assert method used by others for common output.
	 *
	 * @param boolean $expression
	 * @param string|boolean $message The message to output. If the message is not a string,
	 *        then it will be converted to '{:message}'. Use '{:message}' in the string and it
	 *        will use the `$data` to format the message with `Text::insert()`.
	 * @param array $data
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assert($expression, $message = false, $data = []) {
		if (!is_string($message)) {
			$message = '{:message}';
		}
		if (strpos($message, "{:message}") !== false) {
			$params = $data;
			$params['message'] = $this->_message($params);
			$message = Text::insert($message, $params);
		}
		$trace = Debugger::trace([
			'start' => 1, 'depth' => 4, 'format' => 'array', 'closures' => !$expression
		]);
		$methods = $this->methods();
		$i = 1;

		while ($i < count($trace)) {
			if (in_array($trace[$i]['function'], $methods) && $trace[$i - 1]['object'] == $this) {
				break;
			}
			$i++;
		}
		$class = isset($trace[$i - 1]['object']) ? get_class($trace[$i - 1]['object']) : null;
		$method = isset($trace[$i]) ? $trace[$i]['function'] : $trace[$i - 1]['function'];

		$result = compact('class', 'method', 'message', 'data') + [
			'file'      => $trace[$i - 1]['file'],
			'line'      => $trace[$i - 1]['line'],
			'assertion' => $trace[$i - 1]['function']
		];
		$this->_result($expression ? 'pass' : 'fail', $result);
		return $expression;
	}

	/**
	 * Generates a failed test with the given message.
	 *
	 * @param string $message
	 */
	public function fail($message = false) {
		$this->assert(false, $message);
	}

	/**
	 * Assert that the actual result is equal, but not neccessarily identical, to the expected
	 * result.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertEqual($expected, $result, $message = '{:message}') {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		$data = ($expected != $result) ? $this->_compare('equal', $expected, $result) : null;
		return $this->assert($expected == $result, $message, $data);
	}

	/**
	 * Assert that the actual result and the expected result are *not* equal to each other.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotEqual($expected, $result, $message = '{:message}') {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		return $this->assert($result != $expected, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that the actual result and the expected result are identical using a strict
	 * comparison.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertIdentical($expected, $result, $message = '{:message}') {
		$data = ($expected !== $result) ? $this->_compare('identical', $expected, $result) : null;
		return $this->assert($expected === $result, $message, $data);
	}

	/**
	 * Assert that the actual result and the expected result are *not* identical using a strict
	 * comparison.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotIdentical($expected, $result, $message = '{:message}') {
		return $this->assert($expected !== $result, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that the result is strictly `true`.
	 *
	 * ```
	 * $this->assertTrue(true, 'Boolean true'); // succeeds
	 * $this->assertTrue('false', 'String has content'); // fails
	 * $this->assertTrue(10, 'Non-Zero value'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $result
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertTrue($result, $message = '{:message}') {
		$expected = true;
		return $this->assert($result === $expected, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that the result strictly is `false`.
	 *
	 * ```
	 * $this->assertFalse(false, 'Boolean false'); // succeeds
	 * $this->assertFalse('', 'String is empty'); // fails
	 * $this->assertFalse(0, 'Zero value'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $result
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertFalse($result, $message = '{:message}') {
		$expected = false;
		return $this->assert($result === $expected, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that the result is strictly `null`.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $result
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNull($result, $message = '{:message}') {
		$expected = null;
		return $this->assert($result === null, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that the result is *not* strictly `null`.
	 *
	 * ```
	 * $this->assertNotNull(1); // succeeds
	 * $this->assertNotNull(null); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $result
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotNull($actual, $message = '{:message}') {
		return $this->assert($actual !== null, $message, [
			'expected' => null,
			'actual' => gettype($actual)
		]);
	}

	/**
	 * Assert that given result is empty.
	 *
	 * ```
	 * $this->assertEmpty(''); // succeeds
	 * $this->assertEmpty(0); // succeeds
	 * $this->assertEmpty(0.0); // succeeds
	 * $this->assertEmpty('0'); // succeeds
	 * $this->assertEmpty(null); // succeeds
	 * $this->assertEmpty(false); // succeeds
	 * $this->assertEmpty([]); // succeeds
	 * $this->assertEmpty(1); // fails
	 * ```
	 *
	 * @link http://php.net/empty
	 * @see lithium\test\Unit::assert()
	 * @param string $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertEmpty($actual, $message = '{:message}') {
		return $this->assert(empty($actual), $message, [
			'expected' => $actual,
			'result' => empty($actual)
		]);
	}

	/**
	 * Assert that given result is *not* empty.
	 *
	 * ```
	 * $this->assertNotEmpty(1); // succeeds
	 * $this->assertNotEmpty([]); // fails
	 * ```
	 *
	 * @link http://php.net/empty
	 * @see lithium\test\Unit::assert()
	 * @param string $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotEmpty($actual, $message = '{:message}') {
		return $this->assert(!empty($actual), $message, [
			'expected' => $actual,
			'result' => !empty($actual)
		]);
	}

	/**
	 * Assert that the code passed in a closure throws an exception or raises a PHP error. The
	 * first argument to this method specifies which class name or message the exception must
	 * have in order to make the assertion successful.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected A string indicating what the error text is expected to be.  This can
	 *              be an exact string, a /-delimited regular expression, or true, indicating that
	 *              any error text is acceptable.
	 * @param \Closure $closure A closure containing the code that should throw the exception.
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertException($expected, $closure, $message = '{:message}') {
		$result = null;

		try {
			$closure();
			$message = sprintf('An exception "%s" was expected but not thrown.', $expected);
			return $this->assert(false, $message, compact('expected', 'result'));
		} catch (Throwable $e) {
			// fallthrough
		}
		$class = get_class($e);
		$eMessage = $e->getMessage();

		if (get_class($e) === $expected) {
			$result = $class;
			return $this->assert(true, $message, compact('expected', 'result'));
		}
		if ($eMessage === $expected) {
			$result = $eMessage;
			return $this->assert(true, $message, compact('expected', 'result'));
		}
		if (Validator::isRegex($expected) && preg_match($expected, $eMessage)) {
			$result = $eMessage;
			return $this->assert(true, $message, compact('expected', 'result'));
		}

		$message = sprintf(
			'Exception "%s" was expected. Exception "%s" with message "%s" was thrown instead.',
			$expected, get_class($e), $eMessage
		);
		return $this->assert(false, $message);
	}

	/**
	 * Assert that the code passed in a closure does not throw an exception matching the passed
	 * expected exception.
	 *
	 * The value passed to `exepected` is either an exception class name or the expected message.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected A string indicating what the error text is not expected to be. This
	 *              can be an exact string, a /-delimited regular expression, or true, indicating
	 *              that any error text is acceptable.
	 * @param \Closure $closure A closure containing the code that should throw the exception.
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotException($expected, $closure, $message = '{:message}') {
		$result = null;

		try {
			$closure();
		} catch (Exception $e) {
			$class = get_class($e);
			$eMessage = $e->getMessage();
			if (is_a($e, $expected)) {
				$result = $class;
				return $this->assert(false, $message, compact('expected', 'result'));
			}
			if ($eMessage === $expected) {
				$result = $eMessage;
				return $this->assert(false, $message, compact('expected', 'result'));
			}
			if (Validator::isRegex($expected) && preg_match($expected, $eMessage)) {
				$result = $eMessage;
				return $this->assert(false, $message, compact('expected', 'result'));
			}
		}
		$message = sprintf('Exception "%s" was not expected.', $expected);
		return $this->assert(true, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that the regular expression `$expected` is matched in the result.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertPattern($expected, $result, $message = '{:message}') {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		$params = compact('expected', 'result');
		return $this->assert(!!preg_match($expected, $result), $message, $params);
	}

	/**
	 * Assert that the regular expression `$expected` is *not* matched in the result.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotPattern($expected, $result, $message = '{:message}') {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		$params = compact('expected', 'result');
		return $this->assert(!preg_match($expected, $result ?? ''), $message, $params);
	}

	/**
	 * Assert that given value matches the `sprintf` format.
	 *
	 * ```
	 * $this->assertStringMatchesFormat('%d', '10'); // succeeds
	 * $this->assertStringMatchesFormat('%d', '10.555'); // fails
	 * ```
	 *
	 * @link http://php.net/sprintf
	 * @link http://php.net/sscanf
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Expected format using sscanf's format.
	 * @param string $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertStringMatchesFormat($expected, $actual, $message = '{:message}') {
		$result = sscanf($actual, $expected);
		return $this->assert($result[0] == $actual, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that given value does *not* matche the `sprintf` format.
	 *
	 * ```
	 * $this->assertStringNotMatchesFormat('%d', '10.555'); // succeeds
	 * $this->assertStringNotMatchesFormat('%d', '10'); // fails
	 * ```
	 *
	 * @link http://php.net/sprintf
	 * @link http://php.net/sscanf
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Expected format using sscanf's format.
	 * @param string $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertStringNotMatchesFormat($expected, $actual, $message = '{:message}') {
		$result = sscanf($actual, $expected);
		return $this->assert($result[0] != $actual, $message, compact('expected', 'result'));
	}

	/**
	 * Assert given result string has given suffix.
	 *
	 * ```
	 * $this->assertStringEndsWith('bar', 'foobar'); // succeeds
	 * $this->assertStringEndsWith('foo', 'foobar'); // fails
	 * ```
	 *
	 * @param string $expected The suffix to check for.
	 * @param string $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertStringEndsWith($expected, $actual, $message = '{:message}') {
		return $this->assert(preg_match("/$expected$/", $actual, $matches) === 1, $message, [
			'expected' => $expected,
			'result' => $actual
		]);
	}

	/**
	 * Assert given result string has given prefix.
	 *
	 * ```
	 * $this->assertStringStartsWith('foo', 'foobar'); // succeeds
	 * $this->assertStringStartsWith('bar', 'foobar'); // fails
	 * ```
	 *
	 * @param string $expected The prefix to check for.
	 * @param string $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertStringStartsWith($expected, $actual, $message = '{:message}') {
		return $this->assert(preg_match("/^$expected/", $actual, $matches) === 1, $message, [
			'expected' => $expected,
			'result' => $actual
		]);
	}

	/**
	 * Takes an array $expected and generates a regex from it to match the provided $string.
	 * Samples for $expected:
	 *
	 * Checks for an input tag with a name attribute (contains any non-empty value) and an id
	 * attribute that contains 'my-input':
	 * ```
	 *     ['input' => ['name', 'id' => 'my-input']]
	 * ```
	 *
	 * Checks for two p elements with some text in them:
	 * ```
	 * [
	 *     ['p' => true],
	 *     'textA',
	 *     '/p',
	 *     ['p' => true],
	 *     'textB',
	 *     '/p'
	 * ]
	 * ```
	 *
	 * You can also specify a pattern expression as part of the attribute values, or the tag
	 * being defined, if you prepend the value with preg: and enclose it with slashes, like so:
	 * ```
	 * [
	 *     ['input' => ['name', 'id' => 'preg:/FieldName\d+/']],
	 *     'preg:/My\s+field/'
	 * ]
	 * ```
	 *
	 * Important: This function is very forgiving about whitespace and also accepts any
	 * permutation of attribute order. It will also allow whitespaces between specified tags.
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $string An HTML/XHTML/XML string
	 * @param array $expected An array, see above
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertTags($string, $expected) {
		$regex = [];
		$normalized = [];

		foreach ((array) $expected as $key => $val) {
			if (!is_numeric($key)) {
				$normalized[] = [$key => $val];
			} else {
				$normalized[] = $val;
			}
		}
		$i = 0;

		foreach ($normalized as $tags) {
			$i++;
			if (is_string($tags) && $tags[0] === '<') {
				$tags = [substr($tags, 1) => []];
			} elseif (is_string($tags)) {
				$tagsTrimmed = preg_replace('/\s+/m', '', $tags);

				if (preg_match('/^\*?\//', $tags, $match) && $tagsTrimmed !== '//') {
					$prefix = [null, null];

					if ($match[0] === '*/') {
						$prefix = ['Anything, ', '.*?'];
					}
					$regex[] = [
						sprintf('%sClose %s tag', $prefix[0], substr($tags, strlen($match[0]))),
						sprintf('%s<[\s]*\/[\s]*%s[\s]*>[\n\r]*', $prefix[1], substr(
							$tags, strlen($match[0])
						)),
						$i
					];
					continue;
				}

				if (!empty($tags) && preg_match('/^regex\:\/(.+)\/$/i', $tags, $matches)) {
					$tags = $matches[1];
					$type = 'Regex matches';
				} else {
					$tags = preg_quote($tags, '/');
					$type = 'Text equals';
				}
				$regex[] = [sprintf('%s "%s"', $type, $tags), $tags, $i];
				continue;
			}
			foreach ($tags as $tag => $attributes) {
				$regex[] = [
					sprintf('Open %s tag', $tag),
					sprintf('[\s]*<%s', preg_quote($tag, '/')),
					$i
				];
				if ($attributes === true) {
					$attributes = [];
				}
				$attrs = [];
				$explanations = [];

				foreach ($attributes as $attr => $val) {
					if (is_numeric($attr) && preg_match('/^regex\:\/(.+)\/$/i', $val, $matches)) {
						$attrs[] = $matches[1];
						$explanations[] = sprintf('Regex "%s" matches', $matches[1]);
						continue;
					} else {
						$quotes = '"';

						if (is_numeric($attr)) {
							$attr = $val;
							$val = '.+?';
							$explanations[] = sprintf('Attribute "%s" present', $attr);
						} elseif (
							!empty($val) && preg_match('/^regex\:\/(.+)\/$/i', $val, $matches)
						) {
							$quotes = '"?';
							$val = $matches[1];
							$explanations[] = sprintf('Attribute "%s" matches "%s"', $attr, $val);
						} else {
							$explanations[] = sprintf('Attribute "%s" == "%s"', $attr, $val);
							$val = preg_quote($val, '/');
						}
						$attrs[] = '[\s]+' . preg_quote($attr, '/') . "={$quotes}{$val}{$quotes}";
					}
				}
				if ($attrs) {
					$permutations = $this->_arrayPermute($attrs);
					$permutationTokens = [];
					foreach ($permutations as $permutation) {
						$permutationTokens[] = join('', $permutation);
					}
					$regex[] = [
						sprintf('%s', join(', ', $explanations)),
						$permutationTokens,
						$i
					];
				}
				$regex[] = [sprintf('End %s tag', $tag), '[\s]*\/?[\s]*>[\n\r]*', $i];
			}
		}

		foreach ($regex as $i => $assertation) {
			list($description, $expressions, $itemNum) = $assertation;
			$matches = false;

			foreach ((array) $expressions as $expression) {
				if (preg_match(sprintf('/^%s/s', $expression), $string, $match)) {
					$matches = true;
					$string = substr($string, strlen($match[0]));
					break;
				}
			}

			if (!$matches) {
				$this->assert(false, sprintf(
					'- Item #%d / regex #%d failed: %s', $itemNum, $i, $description
				));
				return false;
			}
		}
		return $this->assert(true);
	}

	/**
	 * Assert Cookie data is properly set in headers.
	 *
	 * The value passed to `exepected` is an array of the cookie data, with at least the key and
	 * value expected, but can support any of the following keys:
	 * 	- `key`: the expected key
	 * 	- `value`: the expected value
	 * 	- `path`: optionally specifiy a path
	 * 	- `name`: optionally specify the cookie name
	 * 	- `expires`: optionally assert a specific expire time
	 *
	 * @see lithium\test\Unit::assert()
	 * @param array $expected
	 * @param array $headers When empty, value of `headers_list()` is used.
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertCookie($expected, $headers = null) {
		$result = null;
		$matched = $this->_cookieMatch($expected, $headers);
		if (!$matched['match']) {
			$message = sprintf('%s - Cookie not found in headers.', $matched['pattern']);
			return $this->assert(false, $message, compact('expected', 'result'));
		}
		return $this->assert(true, '%s');
	}

	/**
	 * Assert Cookie data is *not* set in headers.
	 *
	 * The value passed to `expected` is an array of the cookie data, with at least the key and
	 * value expected, but can support any of the following keys:
	 * 	- `key`: the expected key
	 * 	- `value`: the expected value
	 * 	- `path`: optionally specify a path
	 * 	- `name`: optionally specify the cookie name
	 * 	- `expires`: optionally assert a specific expire time
	 *
	 * @see lithium\test\Unit::assert()
	 * @param array $expected
	 * @param array $headers When empty, value of `headers_list()` is used.
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNoCookie($expected, $headers = null) {
		$matched = $this->_cookieMatch($expected, $headers);
		if ($matched['match']) {
			$message = sprintf('%s - Cookie found in headers.', $matched['pattern']);
			return $this->assert(false, $message, compact('expected', 'result'));
		}
		return $this->assert(true, '%s');
	}

	/**
	 * Match an `$expected` cookie with the given headers. If no headers are provided, then
	 * the value of `headers_list()` will be used.
	 *
	 * @param array $expected
	 * @param array $headers When empty, value of `headers_list()` will be used.
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	protected function _cookieMatch($expected, $headers) {
		$defaults = ['path' => '/', 'name' => '[\w.-]+'];
		$expected += $defaults;

		$headers = ($headers) ?: headers_list();
		$value = preg_quote(rawurlencode($expected['value']), '/');

		$key = explode('.', $expected['key']);
		$key = (count($key) === 1) ? '[' . current($key) . ']' : ('[' . join('][', $key) . ']');
		$key = preg_quote($key, '/');

		if (isset($expected['expires'])) {
			$expectedExpires = strtotime($expected['expires']);

			$expires = gmdate('D, d[\- ]M[\- ]Y', $expectedExpires);
			$expires .= ' ' . gmdate('H:i:s', $expectedExpires) . ' GMT';
			$maxAge = $expectedExpires - time();
		} else {
			$expires = '(?:.+?)';
			$maxAge = '([0-9]+)';
		}
		$path = preg_quote($expected['path'], '/');
		$pattern  = "/^Set-Cookie:\s{$expected['name']}{$key}={$value};";
		$pattern .= "\sexpires={$expires};";
		$pattern .= "\sMax-Age={$maxAge};";
		$pattern .= "\spath={$path}/";
		$match = false;

		foreach ($headers as $header) {
			if (preg_match($pattern, $header)) {
				$match = true;
				continue;
			}
		}
		return compact('match', 'pattern');
	}

	/**
	 * Assert that the passed result array has expected number of elements.
	 *
	 * ```
	 * $this->assertCount(1, ['foo']); // succeeds
	 * $this->assertCount(2, ['foo', 'bar', 'bar']); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param integer $expected
	 * @param array $array
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertCount($expected, $array, $message = '{:message}') {
		return $this->assert($expected === ($result = count($array)), $message, [
			'expected' => $expected,
			'result' => $result
		]);
	}

	/**
	 * Assert that the passed result array has *not* the expected number of elements.
	 *
	 * ```
	 * $this->assertNotCount(2, ['foo', 'bar', 'bar']); // succeeds
	 * $this->assertNotCount(1, ['foo']); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param integer $expected
	 * @param array $array
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotCount($expected, $array, $message = '{:message}') {
		return $this->assert($expected !== ($result = count($array)), $message, [
			'expected' => $expected,
			'result' => $result
		]);
	}

	/**
	 * Assert that the result array has given key.
	 *
	 * ```
	 * $this->assertArrayHasKey('bar', ['bar' => 'baz']); // succeeds
	 * $this->assertArrayHasKey('foo', ['bar' => 'baz']); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param array $array
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertArrayHasKey($key, $array, $message = '{:message}') {
		if (is_object($array) && $array instanceof \ArrayAccess) {
			$result = isset($array[$key]);
		} else {
			$result = array_key_exists($key, $array);
		}

		return $this->assert($result, $message, [
			'expected' => $key,
			'result' => $array
		]);
	}

	/**
	 * Assert that the result array does *not* have given key.
	 *
	 * ```
	 * $this->assertArrayNotHasKey('foo', ['bar' => 'baz']); // succeeds
	 * $this->assertArrayNotHasKey('bar', ['bar' => 'baz']); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $expected
	 * @param array $array
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertArrayNotHasKey($key, $array, $message = '{:message}') {
		if (is_object($array) && $array instanceof \ArrayAccess) {
			$result = isset($array[$key]);
		} else {
			$result = array_key_exists($key, $array);
		}

		return $this->assert(!$result, $message, [
			'expected' => $key,
			'result' => $array
		]);
	}

	/**
	 * Assert that `$haystack` contains `$needle` as a value.
	 *
	 * ```
	 * $this->assertContains('foo', ['foo', 'bar', 'baz']); // succeeds
	 * $this->assertContains(4, [1,2,3]); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $needle   The needle you are looking for.
	 * @param mixed $haystack An array, iterable object, or string.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertContains($needle, $haystack, $message = '{:message}') {
		if (is_string($haystack)) {
			return $this->assert(strpos($haystack, $needle) !== false, $message, [
				'expected' => $needle,
				'result' => $haystack
			]);
		}
		foreach ($haystack as $key => $value) {
			if ($value === $needle) {
				return $this->assert(true, $message, [
					'expected' => $needle,
					'result' => $haystack
				]);
			}
		}
		return $this->assert(false, $message, [
			'expected' => $needle,
			'result' => $haystack
		]);
	}

	/**
	 * Assert that `$haystack` does *not* contain `$needle` as a value.
	 *
	 * ```
	 * $this->assertNotContains(4, [1,2,3]); // succeeds
	 * $this->assertNotContains('foo', ['foo', 'bar', 'baz']); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $needle   The needle you are looking for.
	 * @param miexed $haystack Array or iterable object or a string.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotContains($needle, $haystack, $message = '{:message}') {
		if (is_string($haystack)) {
			return $this->assert(strpos($haystack, $needle) === false, $message, [
				'expected' => $needle,
				'result' => $haystack
			]);
		}
		foreach ($haystack as $key => $value) {
			if ($value === $needle) {
				return $this->assert(false, $message, [
					'expected' => $needle,
					'result' => $haystack
				]);
			}
		}
		return $this->assert(true, $message, [
			'expected' => $needle,
			'result' => $haystack
		]);
	}

	/**
	 * Assert that `$haystack` does only contain item of given type.
	 *
	 * ```
	 * $this->assertContainsOnly('integer', [1,2,3]); // succeeds
	 * $this->assertContainsOnly('integer', ['foo', 'bar', 'baz']); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::$_internalTypes
	 * @see lithium\test\Unit::assert()
	 * @param string $type
	 * @param array|object $haystack Array or iterable object.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertContainsOnly($type, $haystack, $message = '{:message}') {
		$method = static::$_internalTypes[$type];
		foreach ($haystack as $key => $value) {
			if (!$method($value)) {
				return $this->assert(false, $message, [
					'expected' => $type,
					'result' => $haystack
				]);
			}
		}
		return $this->assert(true, $message, [
			'expected' => $type,
			'result' => $haystack
		]);
	}

	/**
	 * Assert that `$haystack` hasn't any items of given type.
	 *
	 * ```
	 * $this->assertNotContainsOnly('integer', ['foo', 'bar', 'baz']); // succeeds
	 * $this->assertNotContainsOnly('integer', [1,2,3]); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::$_internalTypes
	 * @see lithium\test\Unit::assert()
	 * @param string $type
	 * @param array|object $haystack Array or iterable object.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotContainsOnly($type, $haystack, $message = '{:message}') {
		$method = static::$_internalTypes[$type];
		foreach ($haystack as $key => $value) {
			if (!$method($value)) {
				return $this->assert(true, $message, [
					'expected' => $type,
					'result' => $haystack
				]);
			}
		}
		return $this->assert(false, $message, [
			'expected' => $type,
			'result' => $haystack
		]);
	}

	/**
	 * Assert that `$haystack` contains only instances of given class.
	 *
	 * ```
	 * $this->assertContainsOnlyInstancesOf('stdClass', [new \stdClass]); // succeeds
	 * $this->assertContainsOnlyInstancesOf('stdClass', [new \lithium\test\Unit]); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $class
	 * @param array|object $haystack Array or iterable object.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertContainsOnlyInstancesOf($class, $haystack, $message = '{:message}') {
		$result = [];
		foreach ($haystack as $key => &$value) {
			if (!is_a($value, $class)) {
				$result[$key] =& $value;
				break;
			}
		}
		return $this->assert(empty($result), $message, [
			'expected' => $class,
			'result' => $result
		]);
	}

	/**
	 * Assert that `$expected` is greater than `$actual`.
	 *
	 * ```
	 * $this->assertGreaterThan(5, 3); // succeeds
	 * $this->assertGreaterThan(3, 5); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param float|integer $expected
	 * @param float|integer $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertGreaterThan($expected, $actual, $message = '{:message}') {
		return $this->assert($expected > $actual, $message, [
			'expected' => $expected,
			'result' => $actual
		]);
	}

	/**
	 * Assert that `$expected` is greater than or equal to `$actual`.
	 *
	 * ```
	 * $this->assertGreaterThanOrEqual(5, 5); // succeeds
	 * $this->assertGreaterThanOrEqual(3, 5); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param float|integer $expected
	 * @param float|integer $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertGreaterThanOrEqual($expected, $actual, $message = '{:message}') {
		return $this->assert($expected >= $actual, $message, [
			'expected' => $expected,
			'result' => $actual
		]);
	}

	/**
	 * Assert that `$expected` is less than `$actual`.
	 *
	 * ```
	 * $this->assertLessThan(3, 5); // succeeds
	 * $this->assertLessThan(5, 3); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param float|integer $expected
	 * @param float|integer $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertLessThan($expected, $actual, $message = '{:message}') {
		return $this->assert($expected < $actual, $message, [
			'expected' => $expected,
			'result' => $actual
		]);
	}

	/**
	 * Assert that `$expected` is less than or equal to `$actual`.
	 *
	 * ```
	 * $this->assertLessThanOrEqual(5, 5); // succeeds
	 * $this->assertLessThanOrEqual(5, 3); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param float|integer $expected
	 * @param float|integer $actual
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertLessThanOrEqual($expected, $actual, $message = '{:message}') {
		return $this->assert($expected <= $actual, $message, [
			'expected' => $expected,
			'result' => $actual
		]);
	}

	/**
	 * Assert that `$actual` is an instance of `$expected`.
	 *
	 * ```
	 * $this->assertInstanceOf('stdClass', new stdClass); // succeeds
	 * $this->assertInstanceOf('ReflectionClass', new stdClass); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Fully namespaced expected class.
	 * @param object $actual Object you are testing.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertInstanceOf($expected, $actual, $message = '{:message}') {
		return $this->assert(is_a($actual, $expected), $message, [
			'expected' => $expected,
			'result' => get_class($actual)
		]);
	}

	/**
	 * Assert that `$actual` is *not* an instance of `$expected`.
	 *
	 * ```
	 * $this->assertNotInstanceOf('ReflectionClass', new stdClass); // succeeds
	 * $this->assertNotInstanceOf('stdClass', new stdClass); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Fully namespaced expected class.
	 * @param object $actual Object you are testing.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotInstanceOf($expected, $actual, $message = '{:message}') {
		return $this->assert(!is_a($actual, $expected), $message, [
			'expected' => $expected,
			'result' => is_object($actual) ? get_class($actual) : gettype($actual),
		]);
	}

	/**
	 * Assert that `$actual` is of given type.
	 *
	 * ```
	 * $this->assertInternalType('string', 'foobar'); // succeeds
	 * $this->assertInternalType('integer', 'foobar'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::$_internalTypes
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Internal type.
	 * @param object $actual Object you are testing.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertInternalType($expected, $actual, $message = '{:message}') {
		$method = static::$_internalTypes[$expected];
		return $this->assert($method($actual), $message, [
			'expected' => $expected,
			'result' => gettype($actual)
		]);
	}

	/**
	 * Assert that `$actual` is *not* of given type.
	 *
	 * ```
	 * $this->assertNotInternalType('integer', 'foobar'); // succeeds
	 * $this->assertNotInternalType('string', 'foobar'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::$_internalTypes
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Internal type.
	 * @param object $actual Object you are testing.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertNotInternalType($expected, $actual, $message = '{:message}') {
		$method = static::$_internalTypes[$expected];
		return $this->assert(!$method($actual), $message, [
			'expected' => $expected,
			'result' => gettype($actual)
		]);
	}

	/**
	 * Assert that the file contents of `$expected` are equal to the contents of `$actual`.
	 *
	 * ```
	 * $this->assertFileEquals('/tmp/foo.txt', '/tmp/foo.txt'); // succeeds
	 * $this->assertFileEquals('/tmp/foo.txt', '/tmp/bar.txt'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Absolute path to the expected file.
	 * @param string $actual Absolute path to the actual file.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertFileEquals($expected, $actual, $message = '{:message}') {
		$expected = md5_file($expected);
		$result = md5_file($actual);
		return $this->assert($expected === $result, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that the file contents of `$expected` are *not* equal to the contents of `$actual`.
	 *
	 * ```
	 * $this->assertFileNotEquals('/tmp/foo.txt', '/tmp/bar.txt'); // succeeds
	 * $this->assertFileNotEquals('/tmp/foo.txt', '/tmp/foo.txt'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $expected Absolute path to the expected file.
	 * @param string $actual Absolute path to the actual file.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertFileNotEquals($expected, $actual, $message = '{:message}') {
		$expected = md5_file($expected);
		$result = md5_file($actual);
		return $this->assert($expected !== $result, $message, compact('expected', 'result'));
	}

	/**
	 * Assert that a file exists.
	 *
	 * ```
	 * $this->assertFileExists(__FILE__); // succeeds
	 * $this->assertFileExists('/tmp/bar.txt'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $actual Absolute path to the actual file.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertFileExists($actual, $message = '{:message}') {
		return $this->assert(file_exists($actual), $message, [
			'expected' => $actual,
			'result' => file_exists($actual)
		]);
	}

	/**
	 * Assert that a file does *not* exist.
	 *
	 * ```
	 * $this->assertFileNotExists('/tmp/bar.txt'); // succeeds
	 * $this->assertFileNotExists(__FILE__); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param string $actual Absolute path to the actual file.
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertFileNotExists($actual, $message = '{:message}') {
		return $this->assert(!file_exists($actual), $message, [
			'expected' => $actual,
			'result' => !file_exists($actual)
		]);
	}

	/**
	 * Assert that a class has a given attribute.
	 *
	 * ```
	 * $this->assertClassHasAttribute('__construct', 'ReflectionClass'); // succeeds
	 * $this->assertClassHasAttribute('name', 'ReflectionClass'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @see lithium\test\Unit::assertObjectHasAttribute()
	 * @throws InvalidArgumentException When $class does not exist.
	 * @param mixed $attributeName
	 * @param string $class
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertClassHasAttribute($attributeName, $class, $message = '{:message}') {
		if (!is_string($class)) {
			throw new InvalidArgumentException('Argument $class must be a string');
		}
		$object = new ReflectionClass($class);
		return $this->assert($object->hasProperty($attributeName), $message, [
			'expected' => $attributeName,
			'result' => $object->getProperties()
		]);
	}

	/**
	 * Assert that a class does *not* have a given attribute.
	 *
	 * ```
	 * $this->assertClassNotHasAttribute('name', 'ReflectionClass'); // succeeds
	 * $this->assertClassNotHasAttribute('__construct', 'ReflectionClass'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @see lithium\test\Unit::assertObjectHasAttribute()
	 * @throws InvalidArgumentException When $class does not exist.
	 * @param mixed $attributeName
	 * @param string $class
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertClassNotHasAttribute($attributeName, $class, $message = '{:message}') {
		if (!is_string($class)) {
			throw new InvalidArgumentException('Argument $class must be a string.');
		}
		$object = new ReflectionClass($class);
		return $this->assert(!$object->hasProperty($attributeName), $message, [
			'expected' => $attributeName,
			'result' => $object->getProperties()
		]);
	}

	/**
	 * Assert that a class does have a given _static_ attribute.
	 *
	 * ```
	 * $this->assertClassHasStaticAttribute('_methodFilters', '\my_app\SomeClass');
	 * $this->assertClassHasStaticAttribute('foobar', '\my_app\SomeClass');
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $attributeName
	 * @param string $class
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertClassHasStaticAttribute($attributeName, $class, $message = '{:message}') {
		$object = new ReflectionClass($class);

		if ($object->hasProperty($attributeName)) {
			$attribute = $object->getProperty($attributeName);

			return $this->assert($attribute->isStatic(), $message, [
				'expected' => $attributeName,
				'result' => $object->getProperties()
			]);
		}
		return $this->assert(false, $message, [
			'expected' => $attributeName,
			'result' => $object->getProperties()
		]);
	}

	/**
	 * Assert that a class does *not* have a given _static_ attribute.
	 *
	 * ```
	 * $this->assertClassNotHasStaticAttribute('foobar', '\my_app\SomeClass');
	 * $this->assertClassNotHasStaticAttribute('_methodFilters', '\my_app\SomeClass');
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @param mixed $attributeName
	 * @param string $class
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertClassNotHasStaticAttribute($attrName, $class, $message = '{:message}') {
		$object = new ReflectionClass($class);

		if ($object->hasProperty($attrName)) {
			$attribute = $object->getProperty($attrName);

			return $this->assert(!$attribute->isStatic(), $message, [
				'expected' => $attrName,
				'result' => $object->getProperties()
			]);
		}
		return $this->assert(true, $message, [
			'expected' => $attrName,
			'result' => $object->getProperties()
		]);
	}

	/**
	 * Assert that `$object` has given attribute.
	 *
	 * ```
	 * $this->assertObjectHasAttribute('__construct', 'ReflectionClass'); // succeeds
	 * $this->assertObjectHasAttribute('name', 'ReflectionClass'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @throws InvalidArgumentException When $object is not an object.
	 * @param string $attributeName
	 * @param string $object
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertObjectHasAttribute($attributeName, $object, $message = '{:message}') {
		if (!is_object($object)) {
			throw new InvalidArgumentException('Second argument $object must be an object.');
		}
		$object = new ReflectionClass($object);
		return $this->assert($object->hasProperty($attributeName), $message, [
			'expected' => $attributeName,
			'result' => $object->getProperties()
		]);
	}

	/**
	 * Assert that `$object` does *not* have given attribute.
	 *
	 * ```
	 * $this->assertObjectNotHasAttribute('name', 'ReflectionClass'); // succeeds
	 * $this->assertObjectNotHasAttribute('__construct', 'ReflectionClass'); // fails
	 * ```
	 *
	 * @see lithium\test\Unit::assert()
	 * @throws InvalidArgumentException When $object is not an object.
	 * @param string $attributeName
	 * @param string $object
	 * @param string|boolean $message
	 * @return boolean `true` if the assertion succeeded, `false` otherwise.
	 */
	public function assertObjectNotHasAttribute($attributeName, $object, $message = '{:message}') {
		if (!is_object($object)) {
			throw new InvalidArgumentException('Second argument $object must be an object');
		}
		$object = new ReflectionClass($object);
		return $this->assert(!$object->hasProperty($attributeName), $message, [
			'expected' => $attributeName,
			'result' => $object->getProperties()
		]);
	}

	/**
	 * Reports test result messages.
	 *
	 * @param string $type The type of result being reported.  Can be `'pass'`, `'fail'`, `'skip'`
	 *               or `'exception'`.
	 * @param array $info An array of information about the test result. At a minimum, this should
	 *              contain a `'message'` key. Other possible keys are `'file'`, `'line'`,
	 *              `'class'`, `'method'`, `'assertion'` and `'data'`.
	 * @param array $options Currently unimplemented.
	 * @return void
	 */
	protected function _result($type, $info, array $options = []) {
		$info = (['result' => $type] + $info);
		$defaults = [];
		$options += $defaults;
		if ($this->_reporter) {
			$filtered = $this->_reporter->__invoke($info);
			$info = is_array($filtered) ? $filtered : $info;
		}
		$this->_results[] = $info;
	}

	/**
	 * Runs an individual test method, collecting results and catching exceptions along the way.
	 *
	 * @param string $method The name of the test method to run.
	 * @param array $options
	 * @return mixed
	 * @filter Executes filters applied to this class' run method.
	 */
	protected function _runTestMethod($method, $options) {
		try {
			$this->setUp();
		} catch (Throwable $e) {
			$this->_handleException($e, __LINE__ - 2);
			return $this->_results;
		}
		$params = compact('options', 'method');

		$passed = Filters::run($this, 'run', $params, function($params) {
			try {
				$method = $params['method'];
				$lineFlag = __LINE__ + 1;
				$this->{$method}();
			} catch (Throwable $e) {
				$this->_handleException($e);
			}
		});
		try {
			$this->tearDown();
		} catch (Throwable $e) {
			$this->_handleException($e, __LINE__ - 2);
		}
		return $passed;
	}

	/**
	 * Normalizes `Exception` objects and PHP error data into a single array format
	 * then the error data is logged to the test results.
	 *
	 * @see lithium\test\Unit::_reportException()
	 * @param mixed $exception An `Exception` object instance, or an array containing the following
	 *              keys: `'name'`,` 'message'`, `'file'`, `'line'`, `'trace'` (in
	 *              `debug_backtrace()` format) and optionally `'code'` (error code number)
	 *              and `'context'` (an array of variables relevant to the scope of where the
	 *              error occurred).
	 * @param integer $lineFlag A flag used for determining the relevant scope of the call stack.
	 *                Set to the line number where test methods are called.
	 * @return void
	 */
	protected function _handleException($exception, $lineFlag = null) {
		$data = $exception;

		if (is_object($exception)) {
			$data = ['name' => get_class($exception)];

			foreach (['message', 'file', 'line', 'trace', 'code'] as $key) {
				$method = 'get' . ucfirst($key);
				$data[$key] = $exception->{$method}();
			}

			if ($exception instanceof ErrorException) {
				$mapSeverity = function($severity) {
					foreach (get_defined_constants(true)['Core'] as $constant => $value) {
						if (substr($constant, 0, 2) === 'E_' && $value === $severity) {
							return $constant;
						}
					}
					return 'E_UNKNOWN';
				};
				$data['code'] = $mapSeverity($exception->getSeverity());
			}

			$ref = $exception->getTrace();
			$ref = $ref[0] + ['class' => null];

			if ($ref['class'] === __CLASS__ && $ref['function'] === 'skipIf') {
				return $this->_result('skip', $data);
			}
		}
		return $this->_reportException($data, $lineFlag);
	}

	/**
	 * Convert an exception object to an exception result array for test reporting.
	 *
	 * @param array $exception The exception data to report on. Statistics are gathered and
	 *               added to the reporting stack contained in `Unit::$_results`.
	 * @param string $lineFlag
	 * @return void
	 * @todo Refactor so that reporters handle trace formatting.
	 */
	protected function _reportException($exception, $lineFlag = null) {
		$message = $exception['message'];
		$initFrame = current($exception['trace']) + ['class' => '-', 'function' => '-'];

		foreach ($exception['trace'] as $frame) {
			if (isset($scopedFrame)) {
				break;
			}
			if (!class_exists('lithium\analysis\Inspector')) {
				continue;
			}
			if (isset($frame['class']) && in_array($frame['class'], Inspector::parents($this))) {
				$scopedFrame = $frame;
			}
		}
		if (class_exists('lithium\analysis\Debugger')) {
			$exception['trace'] = Debugger::trace([
				'trace'        => $exception['trace'],
				'format'       => '{:functionRef}, line {:line}',
				'includeScope' => false,
				'scope'        => array_filter([
					'functionRef' => __NAMESPACE__ . '\{closure}',
					'line'        => $lineFlag
				])
			]);
		}
		$this->_result('exception', $exception + [
			'class'     => $initFrame['class'],
			'method'    => $initFrame['function']
		]);
	}

	/**
	 * Compare the expected with the result.  If `$result` is null `$expected` equals `$type`
	 * and `$result` equals `$expected`.
	 *
	 * @param string $type The type of comparison either `'identical'` or `'equal'` (default).
	 * @param mixed $expected The expected value.
	 * @param mixed $result An optional result value, defaults to `null`
	 * @param string $trace An optional trace used internally to track arrays and objects,
	 *               defaults to `null`.
	 * @return array Data with the keys `trace'`, `'expected'` and `'result'`.
	 */
	protected function _compare($type, $expected, $result = null, $trace = null) {
		$compareTypes = function($expected, $result, $trace) {
			$types = ['expected' => gettype($expected), 'result' => gettype($result)];

			if ($types['expected'] !== $types['result']) {
				$expected = trim("({$types['expected']}) " . print_r($expected, true));
				$result = trim("({$types['result']}) " . print_r($result, true));
				return compact('trace', 'expected', 'result');
			}
		};
		if ($types = $compareTypes($expected, $result, $trace)) {
			return $types;
		}
		$data = [];

		if (!is_scalar($expected)) {
			foreach ($expected as $key => $value) {
				$newTrace = "{$trace}[{$key}]";
				$isObject = false;

				if (is_object($expected)) {
					$isObject = true;
					$expected = (array) $expected;
					$result = (array) $result;
				}
				if (!array_key_exists($key, $result)) {
					$trace = (!$key) ? null : $newTrace;
					$expected = (!$key) ? $expected : $value;
					$result = ($key) ? null : $result;
					return compact('trace', 'expected', 'result');
				}
				$check = $result[$key];

				if ($isObject) {
					$newTrace = ($trace) ? "{$trace}->{$key}" : $key;
					$expected = (object) $expected;
					$result = (object) $result;
				}
				if ($type === 'identical') {
					if ($value === $check) {
						if ($types = $compareTypes($value, $check, $trace)) {
							return $types;
						}
						continue;
					}
					if ($check === []) {
						$trace = $newTrace;
						return compact('trace', 'expected', 'result');
					}
					if (is_string($check)) {
						$trace = $newTrace;
						$expected = $value;
						$result = $check;
						return compact('trace', 'expected', 'result');
					}
				} else {
					if ($value == $check) {
						if ($types = $compareTypes($value, $check, $trace)) {
							return $types;
						}
						continue;
					}
					if (!is_array($value)) {
						$trace = $newTrace;
						return compact('trace', 'expected', 'result');
					}
				}
				$compare = $this->_compare($type, $value, $check, $newTrace);

				if ($compare !== true) {
					$data[] = $compare;
				}
			}
			if (!empty($data)) {
				return $data;
			}
		} elseif (!is_scalar($result)) {
			$data = $this->_compare($type, $result, $expected);

			if (!empty($data)) {
				return [
					'trace' => $data['trace'],
					'expected' => $data['result'],
					'result' => $data['expected']
				];
			}
		}
		if ((($type === 'identical') ? $expected === $result : $expected == $result)) {
			if ($types = $compareTypes($expected, $result, $trace)) {
				return $types;
			}
			return true;
		}
		return compact('trace', 'expected', 'result');
	}

	/**
	 * Returns a basic message for the data returned from `_result()`.
	 *
	 * @see lithium\test\Unit::assert()
	 * @see lithium\test\Unit::_result()
	 * @param array $data The data to use for creating the message.
	 * @param string $message The string prepended to the generate message in the current scope.
	 * @return string
	 */
	protected function _message(&$data = [], $message =  null) {
		if (!empty($data[0])) {
			foreach ($data as $key => $value) {
				$message = (!empty($data[$key][0])) ? $message : null;
				$message .= $this->_message($value, $message);
				unset($data[$key]);
			}
			return $message;
		}
		$defaults = ['trace' => null, 'expected' => null, 'result' => null];
		$data = (array) $data + $defaults;

		$message = null;
		if (!empty($data['trace'])) {
			$message = sprintf("trace: %s\n", $data['trace']);
		}
		if (is_object($data['expected'])) {
			$data['expected'] = get_object_vars($data['expected']);
		}
		if (is_object($data['result'])) {
			$data['result'] = get_object_vars($data['result']);
		}
		return $message . sprintf("expected: %s\nresult: %s\n",
			print_r($data['expected'], true),
			print_r($data['result'], true)
		);
	}

	/**
	 * Generates all permutation of an array $items and returns them in a new array.
	 *
	 * @param array $items An array of items
	 * @param array $perms
	 * @return array
	 */
	protected function _arrayPermute($items, $perms = []) {
		static $permuted;

		if (empty($perms)) {
			$permuted = [];
		}

		if (empty($items)) {
			$permuted[] = $perms;
			return;
		}
		$numItems = count($items) - 1;

		for ($i = $numItems; $i >= 0; --$i) {
			$newItems = $items;
			$newPerms = $perms;
			list($tmp) = array_splice($newItems, $i, 1);
			array_unshift($newPerms, $tmp);
			$this->_arrayPermute($newItems, $newPerms);
		}
		return $permuted;
	}

	/**
	 * Removes everything from `resources/tmp/tests` directory. Call from inside of your test
	 * method or `tearDown()`.
	 *
	 * Uses `DIRECTORY_SEPARATOR` as `getPathname()` is used in a a direct string comparison.
	 * The method may contain slashes and backslashes.
	 *
	 * If the file to unlink is readonly, it throws a exception (Permission denied) on Windows.
	 * So, the file is checked before an unlink is tried. (this will make the tests run slower
	 * but is prefered over a if (!unlink { chmod; unlink }.
	 * http://stringoftheseus.com/blog/2010/12/22/php-unlink-permisssion-denied-error-on-windows/
	 *
	 * @param string $path Path to directory with contents to remove. If first
	 *        character is NOT a slash (`/`) or a Windows drive letter (`C:`)
	 *        prepends `Libraries::get(true, 'resources')/tmp/`.
	 * @return void
	 */
	protected function _cleanUp($path = null) {
		$resources = Libraries::get(true, 'resources');
		$path = $path ?: $resources . '/tmp/tests';
		$path = preg_match('/^\w:|^\//', $path) ? $path : $resources . '/tmp/' . $path;

		if (!is_dir($path)) {
			return;
		}
		$dirs = new RecursiveDirectoryIterator($path);
		$iterator = new RecursiveIteratorIterator($dirs, RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($iterator as $item) {
			$empty = $item->getPathname() === $path . DIRECTORY_SEPARATOR . 'empty';

			if ($empty || $iterator->isDot()) {
				continue;
			}
			if ($item->isDir()) {
				rmdir($item->getPathname());
				continue;
			}
			if (!$item->isWritable()) {
				chmod($item->getPathname(), 0777);
			}
			unlink($item->getPathname());
		}
	}

	/**
	 * Fixes some issues regarding the used EOL character(s).
	 *
	 * On linux EOL is LF, on Windows it is normally CRLF, but the latter may depend also
	 * on the git config core.autocrlf setting. As some tests use heredoc style (<<<) to
	 * specify multiline expectations, this EOL issue may cause tests to fail only because
	 * of a difference in EOL's used.
	 *
	 * in `assertEqual`, `assertNotEqual`,`` assertPattern` and `assertNotPattern` this
	 * function is called to get rid of any EOL differences.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @return array Array with the normalized elements i.e. `[$expected, $result]`.
	 */
	protected function _normalizeLineEndings($expected, $result) {
		if (is_string($expected) && is_string($result)) {
			$expected = preg_replace('/\r\n/', "\n", $expected);
			$result = preg_replace('/\r\n/', "\n", $result);
		}
		return [$expected, $result];
	}
}

?>