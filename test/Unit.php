<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use Exception;
use lithium\util\String;
use lithium\core\Libraries;
use lithium\util\Validator;
use lithium\analysis\Debugger;
use lithium\analysis\Inspector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * This is the base class for all test cases. Test are performed using an assertion method. If the
 * assertion is correct, the test passes, otherwise it fails. Most assertions take an expected
 * result, a received result, and a message (to describe the failure) as parameters.
 *
 * Unit tests are used to check a small unit of functionality, such as if a
 * method returns an expected result for a known input, or whether an adapter
 * can successfully open a connection.
 *
 * Available assertions are (see `assert<assertion-name>` methods for details): Equal, False,
 * Identical, NoPattern, NotEqual, Null, Pattern, Tags, True.
 *
 * If an assertion is expected to produce an exception, the `expectException` method should be
 * called before it.
 */
class Unit extends \lithium\core\Object {

	/**
	 * The Reference to a test reporter class.
	 *
	 * @var string
	 */
	protected $_reporter = null;

	/**
	 * The list of test results.
	 *
	 * @var string
	 */
	protected $_results = array();

	/**
	 * The list of expected exceptions.
	 *
	 * @var string
	 */
	protected $_expected = array();

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
	 * Setup method run before every test method. override in subclasses
	 *
	 * @return void
	 */
	public function setUp() {}

	/**
	 * Teardown method run after every test method. override in subclasses
	 *
	 * @return void
	 */
	public function tearDown() {}

	/**
	 * Subclasses should use this method to set conditions that, if failed, terminate further
	 * testing.
	 *
	 * For example:
	 * {{{
	 * public function skip() {
	 *	$this->_dbConfig = Connections::get('default', array('config' => true));
	 *	$hasDb = (isset($this->_dbConfig['adapter']) && $this->_dbConfig['adapter'] == 'MySql');
	 *	$message = 'Test database is either unavailable, or not using a MySQL adapter';
	 *	$this->skipIf(!$hasDb, $message);
	 * }
	 * }}}
	 *
	 * @return void
	 */
	public function skip() {}

	/**
	 * Skips test(s) if the condition is met.
	 *
	 * When used within a subclass' `skip` method, all tests are ignored if the condition is met,
	 * otherwise processing continues as normal.
	 * For other methods, only the remainder of the method is skipped, when the condition is met.
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
	 * Return test methods to run
	 *
	 * @return array
	 */
	public function methods() {
		static $methods;
		return $methods ?: $methods = array_values(preg_grep('/^test/', get_class_methods($this)));
	}

	/**
	 * Runs the test methods in this test case, with the given options.
	 *
	 * @param array $options The options to use when running the test.	Available options are:
	 *             - 'methods': An arbitrary array of method names to execute. If
	 *                unspecified, all methods starting with 'test' are run.
	 *             - 'reporter': A closure which gets called after each test result,
	 *                which may modify the results presented.
	 * @return array
	 */
	public function run(array $options = array()) {
		$defaults = array('methods' => array(), 'reporter' => null, 'handler' => null);
		$options += $defaults;
		$this->_results = array();
		$self = $this;

		try {
			$this->skip();
		} catch (Exception $e) {
			$this->_handleException($e);
			return $this->_results;
		}

		$h = function($code, $message, $file, $line = 0, $context = array()) use ($self) {
			$trace = debug_backtrace();
			$trace = array_slice($trace, 1, count($trace));
			$self->invokeMethod('_reportException', array(
				compact('code', 'message', 'file', 'line', 'trace', 'context')
			));
		};
		$options['handler'] = $options['handler'] ?: $h;
		set_error_handler($options['handler']);

		$methods = $options['methods'] ?: $this->methods();
		$this->_reporter = $options['reporter'] ?: $this->_reporter;

		foreach ($methods as $method) {
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
	 *        will use the `$data` to format the message with `String::insert()`.
	 * @param array $data
	 * @return void
	 */
	public function assert($expression, $message = false, $data = array()) {
		if (!is_string($message)) {
			$message = '{:message}';
		}
		if (strpos($message, "{:message}") !== false) {
			$params = $data;
			$params['message'] = $this->_message($params);
			$message = String::insert($message, $params);
		}
		$trace = Debugger::trace(array(
			'start' => 1, 'depth' => 4, 'format' => 'array', 'closures' => !$expression
		));
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

		$result = compact('class', 'method', 'message', 'data') + array(
			'file'      => $trace[$i - 1]['file'],
			'line'      => $trace[$i - 1]['line'],
			'assertion' => $trace[$i - 1]['function']
		);
		$this->_result($expression ? 'pass' : 'fail', $result);
		return $expression;
	}

	/**
	 * Generates a failed test with the passed message.
	 *
	 * @param string $message
	 */
	public function fail($message = false) {
		$this->assert(false, $message);
	}

	/**
	 * Fixes some issues regarding the used EOL character(s).
	 *
	 * On linux EOL is LF, on Windows it is normally CRLF, but the latter may depend also
	 * on the git config core.autocrlf setting. As some tests use heredoc style (<<<) to
	 * specify multiline expectations, this EOL issue may cause tests to fail only because
	 * of a difference in EOL's used.
	 *
	 * in assertEqual, assertNotEqual, assertPattern and assertNotPattern this function is
	 * called to get rid of any EOL differences.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 */
	protected function _normalizeLineEndings($expected, $result) {
		if (is_string($expected) && is_string($result)) {
			$expected = preg_replace('/\r\n/', "\n", $expected);
			$result = preg_replace('/\r\n/', "\n", $result);
		}
		return array($expected, $result);
	}

	/**
	 * Checks that the actual result is equal, but not neccessarily identical, to the expected
	 * result.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 */
	public function assertEqual($expected, $result, $message = false) {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		$data = ($expected != $result) ? $this->_compare('equal', $expected, $result) : null;
		return $this->assert($expected == $result, $message, $data);
	}

	/**
	 * Checks that the actual result and the expected result are not equal to each other.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 */
	public function assertNotEqual($expected, $result, $message = false) {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		return $this->assert($result != $expected, $message, compact('expected', 'result'));
	}

	/**
	 * Checks that the actual result and the expected result are identical.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 */
	public function assertIdentical($expected, $result, $message = false) {
		$data = ($expected !== $result) ? $this->_compare('identical', $expected, $result) : null;
		return $this->assert($expected === $result, $message, $data);
	}

	/**
	 * Checks that the result evaluates to true.
	 *
	 * For example:
	 * {{{
	 * $this->assertTrue('false', 'String has content');
	 * }}}
	 * {{{
	 * $this->assertTrue(10, 'Non-Zero value');
	 * }}}
	 * {{{
	 * $this->assertTrue(true, 'Boolean true');
	 * }}}
	 * all evaluate to true.
	 *
	 * @param mixed $result
	 * @param string $message
	 */
	public function assertTrue($result, $message = '{:message}') {
		$expected = true;
		return $this->assert(!empty($result), $message, compact('expected', 'result'));
	}

	/**
	 * Checks that the result evaluates to false.
	 *
	 * For example:
	 * {{{
	 * $this->assertFalse('', 'String is empty');
	 * }}}
	 *
	 * {{{
	 * $this->assertFalse(0, 'Zero value');
	 * }}}
	 *
	 * {{{
	 * $this->assertFalse(false, 'Boolean false');
	 * }}}
	 * all evaluate to false.
	 *
	 * @param mixed $result
	 * @param string $message
	 */
	public function assertFalse($result, $message = '{:message}') {
		$expected = false;
		return $this->assert(empty($result), $message, compact('expected', 'result'));
	}

	/**
	 * Checks if the result is null.
	 *
	 * @param mixed $result
	 * @param string $message
	 */
	public function assertNull($result, $message = '{:message}') {
		$expected = null;
		return $this->assert($result === null, $message, compact('expected', 'result'));
	}

	/**
	 * Checks that the regular expression `$expected` is not matched in the result.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string $message
	 */
	public function assertNoPattern($expected, $result, $message = '{:message}') {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		return $this->assert(!preg_match($expected, $result), $message, compact('expected', 'result'));
	}

	/**
	 * Checks that the regular expression `$expected` is matched in the result.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string $message
	 */
	public function assertPattern($expected, $result, $message = '{:message}') {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		return $this->assert(!!preg_match($expected, $result), $message, compact('expected', 'result'));
	}

	/**
	 * Takes an array $expected and generates a regex from it to match the provided $string.
	 * Samples for $expected:
	 *
	 * Checks for an input tag with a name attribute (contains any non-empty value) and an id
	 * attribute that contains 'my-input':
	 * {{{
	 * 	array('input' => array('name', 'id' => 'my-input'))
	 * }}}
	 *
	 * Checks for two p elements with some text in them:
	 * {{{
	 * 	array(
	 * 		array('p' => true),
	 * 		'textA',
	 * 		'/p',
	 * 		array('p' => true),
	 * 		'textB',
	 * 		'/p'
	 *	)
	 * }}}
	 *
	 * You can also specify a pattern expression as part of the attribute values, or the tag
	 * being defined, if you prepend the value with preg: and enclose it with slashes, like so:
	 * {{{
	 *	array(
	 *  	array('input' => array('name', 'id' => 'preg:/FieldName\d+/')),
	 *  	'preg:/My\s+field/'
	 *	)
	 * }}}
	 *
	 * Important: This function is very forgiving about whitespace and also accepts any
	 * permutation of attribute order. It will also allow whitespaces between specified tags.
	 *
	 * @param string $string An HTML/XHTML/XML string
	 * @param array $expected An array, see above
	 * @return boolean
	 */
	function assertTags($string, $expected) {
		$regex = array();
		$normalized = array();

		foreach ((array) $expected as $key => $val) {
			if (!is_numeric($key)) {
				$normalized[] = array($key => $val);
			} else {
				$normalized[] = $val;
			}
		}
		$i = 0;

		foreach ($normalized as $tags) {
			$i++;
			if (is_string($tags) && $tags{0} == '<') {
				$tags = array(substr($tags, 1) => array());
			} elseif (is_string($tags)) {
				$tagsTrimmed = preg_replace('/\s+/m', '', $tags);

				if (preg_match('/^\*?\//', $tags, $match) && $tagsTrimmed !== '//') {
					$prefix = array(null, null);

					if ($match[0] == '*/') {
						$prefix = array('Anything, ', '.*?');
					}
					$regex[] = array(
						sprintf('%sClose %s tag', $prefix[0], substr($tags, strlen($match[0]))),
						sprintf('%s<[\s]*\/[\s]*%s[\s]*>[\n\r]*', $prefix[1], substr(
							$tags, strlen($match[0])
						)),
						$i
					);
					continue;
				}

				if (!empty($tags) && preg_match('/^regex\:\/(.+)\/$/i', $tags, $matches)) {
					$tags = $matches[1];
					$type = 'Regex matches';
				} else {
					$tags = preg_quote($tags, '/');
					$type = 'Text equals';
				}
				$regex[] = array(sprintf('%s "%s"', $type, $tags), $tags, $i);
				continue;
			}
			foreach ($tags as $tag => $attributes) {
				$regex[] = array(
					sprintf('Open %s tag', $tag),
					sprintf('[\s]*<%s', preg_quote($tag, '/')),
					$i
				);
				if ($attributes === true) {
					$attributes = array();
				}
				$attrs = array();
				$explanations = array();

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
					$permutationTokens = array();
					foreach ($permutations as $permutation) {
						$permutationTokens[] = join('', $permutation);
					}
					$regex[] = array(
						sprintf('%s', join(', ', $explanations)),
						$permutationTokens,
						$i
					);
				}
				$regex[] = array(sprintf('End %s tag', $tag), '[\s]*\/?[\s]*>[\n\r]*', $i);
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
	 * Assert that the code passed in a closure throws an exception matching the passed expected
	 * exception.
	 *
	 * The value passed to `exepected` is either an exception class name or the expected message.
	 *
	 * @param mixed $expected A string indicating what the error text is expected to be.  This can
	 *              be an exact string, a /-delimited regular expression, or true, indicating that
	 *              any error text is acceptable.
	 * @param closure $closure A closure containing the code that should throw the exception.
	 * @param string $message
	 * @return boolean
	 */
	public function assertException($expected, $closure, $message = '{:message}') {
		try {
			$closure();
			$message = sprintf('An exception "%s" was expected but not thrown.', $expected);
			return $this->assert(false, $message, compact('expected', 'result'));
		} catch (Exception $e) {
			$class = get_class($e);
			$eMessage = $e->getMessage();

			if (get_class($e) == $expected) {
				$result = $class;
				return $this->assert(true, $message, compact('expected', 'result'));
			}
			if ($eMessage == $expected) {
				$result = $eMessage;
				return $this->assert(true, $message, compact('expected', 'result'));
			}
			if (Validator::isRegex($expected) && preg_match($expected, $eMessage)) {
				$result = $eMessage;
				return $this->assert(true, $message, compact('expected', 'result'));
			}

			$message = sprintf(
				'Exception "%s" was expected. Exception "%s" with message "%s" was thrown instead.',
				$expected, get_class($e), $eMessage);
			return $this->assert(false, $message);
		}
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
	 * @param array $expected
	 * @param array $headers When empty, value of `headers_list()` is used.
	 * @return boolean
	 */
	public function assertCookie($expected, $headers = null) {
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
	 * @param array $expected
	 * @param array $headers When empty, value of `headers_list()` is used.
	 * @return boolean
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
	 * @return boolean True if cookie is found, false otherwise.
	 */
	protected function _cookieMatch($expected, $headers) {
		$defaults = array('path' => '/', 'name' => '[\w.-]+');
		$expected += $defaults;

		$headers = ($headers) ?: headers_list();
		$value = preg_quote(urlencode($expected['value']), '/');

		$key = explode('.', $expected['key']);
		$key = (count($key) == 1) ? '[' . current($key) . ']' : ('[' . join('][', $key) . ']');
		$key = preg_quote($key, '/');

		if (isset($expected['expires'])) {
			$date = gmdate('D, d-M-Y H:i:s \G\M\T', strtotime($expected['expires']));
			$expires = preg_quote($date, '/');
		} else {
			$expires = '(?:.+?)';
		}
		$path = preg_quote($expected['path'], '/');
		$pattern  = "/^Set\-Cookie:\s{$expected['name']}$key=$value;";
		$pattern .= "\sexpires=$expires;\spath=$path/";
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
	 * Used before a call to `assert*()` if you expect the test assertion to generate an exception
	 * or PHP error.  If no error or exception is thrown, a test failure will be reported.  Can
	 * be called multiple times per assertion, if more than one error is expected.
	 *
	 * @param mixed $message A string indicating what the error text is expected to be.  This can
	 *              be an exact string, a /-delimited regular expression, or true, indicating that
	 *              any error text is acceptable.
	 * @return void
	 */
	public function expectException($message = true) {
		$this->_expected[] = $message;
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
	protected function _result($type, $info, array $options = array()) {
		$info = (array('result' => $type) + $info);
		$defaults = array();
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
	 * @filter
	 */
	protected function _runTestMethod($method, $options) {
		try {
			$this->setUp();
		} catch (Exception $e) {
			$this->_handleException($e, __LINE__ - 2);
			return $this->_results;
		}
		$params = compact('options', 'method');

		$passed = $this->_filter(__CLASS__ . '::run', $params, function($self, $params, $chain) {
			try {
				$method = $params['method'];
				$lineFlag = __LINE__ + 1;
				$self->{$method}();
			} catch (Exception $e) {
				$self->invokeMethod('_handleException', array($e));
			}
		});

		foreach ($this->_expected as $expected) {
			$this->_result('fail', compact('method') + array(
				'class' => get_class($this),
				'message' => "Expected exception matching `{$expected}` uncaught.",
				'data' => array(),
				'file' => null,
				'line' => null,
				'assertion' => 'expectException'
			));
		}
		$this->_expected = array();

		try {
			$this->tearDown();
		} catch (Exception $e) {
			$this->_handleException($e, __LINE__ - 2);
		}
		return $passed;
	}

	/**
	 * Normalizes `Exception` objects and PHP error data into a single array format, and checks
	 * each error against the list of expected errors (set using `expectException()`).  If a match
	 * is found, the expectation is removed from the stack and the error is ignored.  If no match
	 * is found, then the error data is logged to the test results.
	 *
	 * @see lithium\test\Unit::expectException()
	 * @see lithium\test\Unit::_reportException()
	 * @param mixed $exception An `Exception` object instance, or an array containing the following
	 *              keys: `'message'`, `'file'`, `'line'`, `'trace'` (in `debug_backtrace()`
	 *              format) and optionally `'code'` (error code number) and `'context'` (an array
	 *              of variables relevant to the scope of where the error occurred).
	 * @param integer $lineFlag A flag used for determining the relevant scope of the call stack.
	 *                Set to the line number where test methods are called.
	 * @return void
	 */
	protected function _handleException($exception, $lineFlag = null) {
		$data = $exception;

		if (is_object($exception)) {
			$data = array();

			foreach (array('message', 'file', 'line', 'trace') as $key) {
				$method = 'get' . ucfirst($key);
				$data[$key] = $exception->{$method}();
			}
			$ref = $exception->getTrace();
			$ref = $ref[0] + array('class' => null);

			if ($ref['class'] == __CLASS__ && $ref['function'] == 'skipIf') {
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

		$isExpected = (($exp = end($this->_expected)) && ($exp === true || $exp == $message || (
			Validator::isRegex($exp) && preg_match($exp, $message)
		)));
		if ($isExpected) {
			return array_pop($this->_expected);
		}
		$initFrame = current($exception['trace']) + array('class' => '-', 'function' => '-');

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
			$exception['trace'] = Debugger::trace(array(
				'trace'        => $exception['trace'],
				'format'       => '{:functionRef}, line {:line}',
				'includeScope' => false,
				'scope'        => array_filter(array(
					'functionRef' => __NAMESPACE__ . '\{closure}',
					'line'        => $lineFlag
				))
			));
		}
		$this->_result('exception', $exception + array(
			'class'     => $initFrame['class'],
			'method'    => $initFrame['function']
		));
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
			$types = array('expected' => gettype($expected), 'result' => gettype($result));

			if ($types['expected'] !== $types['result']) {
				$expected = trim("({$types['expected']}) " . print_r($expected, true));
				$result = trim("({$types['result']}) " . print_r($result, true));
				return compact('trace', 'expected', 'result');
			}
		};
		if ($types = $compareTypes($expected, $result, $trace)) {
			return $types;
		}
		$data = array();

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
					if ($check === array()) {
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
				return array(
					'trace' => $data['trace'],
					'expected' => $data['result'],
					'result' => $data['expected']
				);
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
	protected function _message(&$data = array(), $message =  null) {
		if (!empty($data[0])) {
			foreach ($data as $key => $value) {
				$message = (!empty($data[$key][0])) ? $message : null;
				$message .= $this->_message($value, $message);
				unset($data[$key]);
			}
			return $message;
		}
		$defaults = array('trace' => null, 'expected' => null, 'result' => null);
		$result = (array) $data + $defaults;

		$message = null;
		if (!empty($result['trace'])) {
			$message = sprintf("trace: %s\n", $result['trace']);
		}
		if (is_object($result['expected'])) {
			$result['expected'] = get_object_vars($result['expected']);
		}
		if (is_object($result['result'])) {
			$result['result'] = get_object_vars($result['result']);
		}
		return $message . sprintf("expected: %s\nresult: %s\n",
			var_export($result['expected'], true),
			var_export($result['result'], true)
		);
	}

	/**
	 * Generates all permutation of an array $items and returns them in a new array.
	 *
	 * @param array $items An array of items
	 * @param array $perms
	 * @return array
	 */
	protected function _arrayPermute($items, $perms = array()) {
		static $permuted;

		if (empty($perms)) {
			$permuted = array();
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
	 *        prepends `LITHIUM_APP_PATH/resources/tmp/`.
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
	 * Returns the current results
	 *
	 * @return array The Results, currently
	 */
	public function results() {
		return $this->_results;
	}

	/**
	 * Checks for a working internet connection.
	 *
	 * This method is used to check for a working connection to google.com, both
	 * testing for proper DNS resolution and reading the actual URL.
	 *
	 * @param array $config Override the default URL to check.
	 * @return boolean True if a network connection is established, false otherwise.
	 */
	protected function _hasNetwork($config = array()) {
		$defaults = array(
			'scheme' => 'http',
			'host' => 'google.com'
		);
		$config += $defaults;

		$url = "{$config['scheme']}://{$config['host']}";
		$failed = false;

		set_error_handler(function($errno, $errstr) use (&$failed) {
			$failed = true;
		});

		dns_check_record($config['host'], 'A');

		if ($handle = fopen($url, 'r')) {
			fclose($handle);
		}

		restore_error_handler();
		return !$failed;
	}
}

?>