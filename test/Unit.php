<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use Exception;
use ReflectionClass;
use InvalidArgumentException;
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
	 * Internal types and how to test for them
	 *
	 * @var array
	 */
	protected static $_internalTypes = array(
		'array' => 'is_array',
		'bool' => 'is_bool',
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
	);

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
		$data = ($expected != $result) ? $this->_compare('equal', $expected, $result) : array();
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
		$data = ($expected !== $result) ? $this->_compare('identical', $expected, $result) : array();
		return $this->assert($expected === $result, $message, $data);
	}

	/**
	 * Checks that the actual result and the expected result are not identical.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 */
	public function assertNotIdentical($expected, $result, $message = false) {
		return $this->assert($expected !== $result, $message, compact('expected', 'result'));
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
	 * @param string|boolean $message
	 */
	public function assertTrue($result, $message = false) {
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
	 * @param string|boolean $message
	 */
	public function assertFalse($result, $message = false) {
		$expected = false;
		return $this->assert(empty($result), $message, compact('expected', 'result'));
	}

	/**
	 * Checks if the result is null.
	 *
	 * {{{
	 * $this->assertNull(1);
	 * }}}
	 *
	 * {{{
	 * $this->assertNull(null);
	 * }}}
	 *
	 * @param mixed $result
	 * @param string|boolean $message Optional
	 */
	public function assertNull($result, $message = false) {
		$expected = null;
		return $this->assert($result === null, $message, compact('expected', 'result'));
	}

	/**
	 * Checks if the result is not null.
	 *
	 * {{{
	 * $this->assertNotNull(1);
	 * }}}
	 *
	 * {{{
	 * $this->assertNotNull(null);
	 * }}}
	 *
	 * @param  mixed $result
	 * @param  string|boolean $message Optional
	 * @return boolean
	 */
	public function assertNotNull($result, $message = false) {
		$expected = null;
		return $this->assert($result !== null, $message, compact('expected', 'result'));
	}

	/**
	 * Checks that the regular expression `$expected` is not matched in the result.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 */
	public function assertNoPattern($expected, $result, $message = false) {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		return $this->assert(!preg_match($expected, $result), $message, compact('expected', 'result'));
	}

	/**
	 * Checks that the regular expression `$expected` is matched in the result.
	 *
	 * @param mixed $expected
	 * @param mixed $result
	 * @param string|boolean $message
	 */
	public function assertPattern($expected, $result, $message = false) {
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
	 * @param string|boolean $message
	 * @return boolean
	 */
	public function assertException($expected, $closure, $message = false) {
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

	/**
	 * Will mark the test `true` if `$count` and `count($arr)` are equal.
	 *
	 * {{{
	 * $this->assertCount(1, array('foo'));
	 * }}}
	 *
	 * {{{
	 * $this->assertCount(2, array('foo', 'bar', 'bar'));
	 * }}}
	 *
	 * @param  int            $expected Expected count
	 * @param  array          $array Result
	 * @param  string|boolean $message optional
	 * @return boolean
	 */
	public function assertCount($expected, $array, $message = false) {
		return $this->assert($expected === ($result = count($array)), $message, array(
			'expected' => $expected,
			'result' => $result,
		));
	}

	/**
	 * Will mark the test `true` if `$count` and `count($arr)` are not equal.
	 *
	 * {{{
	 * $this->assertNotCount(2, array('foo', 'bar', 'bar'));
	 * }}}
	 *
	 * {{{
	 * $this->assertNotCount(1, array('foo'));
	 * }}}
	 *
	 * @param  int            $expected Expected count
	 * @param  array          $array    Result
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertNotCount($expected, $array, $message = false) {
		return $this->assert($expected !== ($result = count($array)), $message, array(
			'expected' => $expected,
			'result' => $result,
		));
	}

	/**
	 * Will mark the test `true` if `$array` has key `$expected`.
	 *
	 * {{{
	 * $this->assertArrayHasKey('foo', array('bar' => 'baz'));
	 * }}}
	 *
	 * {{{
	 * $this->assertArrayHasKey('bar', array('bar' => 'baz'));
	 * }}}
	 *
	 * @param  string         $key      Key you are looking for
	 * @param  array          $array    Array to search through
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertArrayHasKey($key, $array, $message = false) {
		return $this->assert(isset($array[$key]), $message, array(
			'expected' => $key,
			'result' => $array
		));
	}

	/**
	 * Will mark the test `true` if `$array` does not have key `$expected`.
	 *
	 * {{{
	 * $this->assertArrayNotHasKey('foo', array('bar' => 'baz'));
	 * }}}
	 *
	 * {{{
	 * $this->assertArrayNotHasKey('bar', array('bar' => 'baz'));
	 * }}}
	 *
	 * @param  int            $key      Expected count
	 * @param  array          $array    Array to search through
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertArrayNotHasKey($key, $array, $message = false) {
		return $this->assert(!isset($array[$key]), $message, array(
			'expected' => $key,
			'result' => $array
		));
	}

	/**
	 * Will mark the test `true` if `$class` has an attribute `$attrName`.
	 *
	 * {{{
	 * $this->assertClassHasAttribute('name', 'ReflectionClass');
	 * }}}
	 *
	 * {{{
	 * $this->assertClassHasAttribute('__construct', 'ReflectionClass');
	 * }}}
	 *
	 * @see    lithium\test\Unit::assertObjectHasAttribute()
	 * @throws InvalidArgumentException When $class is not an string
	 * @throws ReflectionException      If the given class does not exist
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string         $class    Class name
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertClassHasAttribute($attrName, $class, $message = false) {
		return $this->_classHasAttribute($attrName, $class, $message);
	}

	/**
	 * Will mark the test `true` if `$class` has an attribute `$attrName`.
	 *
	 * {{{
	 * $this->assertClassNotHasAttribute('__construct', 'ReflectionClass');
	 * }}}
	 *
	 * {{{
	 * $this->assertClassNotHasAttribute('name', 'ReflectionClass');
	 * }}}
	 *
	 * @see    lithium\test\Unit::assertObjectNotHasAttribute()
	 * @throws InvalidArgumentException When $class is not an string
	 * @throws ReflectionException      If the given class does not exist
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string         $class    Class name
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertClassNotHasAttribute($attrName, $class, $message = false) {
		return $this->_classHasAttribute($attrName, $class, $message, false);
	}

	/**
	 * Code base for `assertClassHasAttribute()` & `assertClassNotHasAttribute()`
	 *
	 * @see    lithium\test\Unit::assertClassHasAttribute()
	 * @see    lithium\test\Unit::assertClassNotHasAttribute()
	 * @throws InvalidArgumentException When $class is not an string
	 * @throws ReflectionException      If the given class does not exist
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string         $class    Class name
	 * @param  string|boolean $message  Optional
	 * @param  boolean        $expected Optional
	 * @return boolean
	 */
	protected function _classHasAttribute($attrName, $class, $message = false, $expected = true) {
		if(!is_string($class)) {
			throw new InvalidArgumentException('Argument $class must be a string');
		}
		$object = new ReflectionClass($class);
		return $this->assert($object->hasProperty($attrName) === $expected, $message, array(
			'expected' => $attrName,
			'result' => $object->getProperties()
		));
	}

	/**
	 * Will mark the test `true` if `$class` has a static property `$attrName`.
	 *
	 * {{{
	 * $this->assertClassHasStaticAttribute('foobar', '\lithium\core\StaticObject');
	 * }}}
	 *
	 * {{{
	 * $this->assertClassHasStaticAttribute('_methodFilters', '\lithium\core\StaticObject');
	 * }}}
	 *
	 * @throws ReflectionException If the given class does not exist
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string|object  $class    Class name or object
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertClassHasStaticAttribute($attrName, $class, $message = false) {
		return $this->_classHasStaticAttribute($attrName, $class, $message);
	}

	/**
	 * Will mark the test `true` if `$class` does not have a static property `$attrName`.
	 *
	 * {{{
	 * $this->assertClassNotHasStaticAttribute('_methodFilters', '\lithium\core\StaticObject');
	 * }}}
	 *
	 * {{{
	 * $this->assertClassNotHasStaticAttribute('foobar', '\lithium\core\StaticObject')
	 * }}}
	 *
	 * @throws ReflectionException If the given class does not exist
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string|object  $class    Class name or object
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertClassNotHasStaticAttribute($attrName, $class, $message = false) {
		return $this->_classHasStaticAttribute($attrName, $class, $message, false);
	}

	/**
	 * Code base for `assertClassHasStaticAttribute()` & `assertClassNotHasStaticAttribute()`
	 *
	 * @see    lithium\test\Unit::assertClassHasStaticAttribute()
	 * @see    lithium\test\Unit::assertClassNotHasStaticAttribute()
	 * @throws ReflectionException If the given class does not exist
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string|object  $class    Class name or object
	 * @param  string|boolean $message  Optional
	 * @param  boolean        $expected Optional
	 * @return boolean
	 */
	protected function _classHasStaticAttribute($attrName, $class, $message = false, $expected = true) {
		$object = new ReflectionClass($class);
		$data = array('expected' => $attrName, 'result' => $object->getProperties());
		if ($object->hasProperty($attrName)) {
			$attribute = $object->getProperty($attrName);
			return $this->assert($attribute->isStatic() === $expected, $message, $data);
		}
		return $this->assert(!$expected, $message, $data);
	}

	/**
	 * Will mark the test `true` if `$haystack` contains `$needle` as a value.
	 *
	 * {{{
	 * $this->assertContains('foo', array('foo', 'bar', 'baz'));
	 * }}}
	 *
	 * {{{
	 * $this->assertContains(4, array(1,2,3));
	 * }}}
	 *
	 * @param  string         $needle   The needle you are looking for
	 * @param  mixed          $haystack An array, iterable object, or string
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertContains($needle, $haystack, $message = false) {
		return $this->_contains($needle, $haystack, $message);
	}

	/**
	 * Will mark the test `true` if `$haystack` does not contain `$needle` as a value.
	 *
	 * {{{
	 * $this->assertNotContains(4, array(1,2,3));
	 * }}}
	 *
	 * {{{
	 * $this->assertNotContains('foo', array('foo', 'bar', 'baz'));
	 * }}}
	 *
	 * @param  string         $needle   Needle you are looking for
	 * @param  mixed          $haystack Array, iterable object, or string
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertNotContains($needle, $haystack, $message = false) {
		return $this->_contains($needle, $haystack, $message, false);
	}

	/**
	 * Code base for `assertContains()` & `assertNotContains()`
	 *
	 * @see    lithium\test\Unit::assertContains()
	 * @see    lithium\test\Unit::assertNotContains()
	 * @param  string $needle   Needle you are looking for
	 * @param  mixed          $haystack Array, iterable object, or string
	 * @param  string|boolean $message  Optional
	 * @param  boolean        $expected Optional
	 * @return boolean
	 */
	protected function _contains($needle, $haystack, $message = false, $expected = true) {
		$data = array('expected' => $needle, 'result' => $haystack);
		if (is_string($haystack)) {
			$contained = is_numeric(strpos($haystack, $needle));
			return $this->assert($contained === $expected, $message, $data);
		}
		foreach ($haystack as $key => $value) {
			if ($value === $needle) {
				return $this->assert($expected, $message, $data);
			}
		}
		return $this->assert(!$expected, $message, $data);
	}
	/**
	 * Will mark the test `true` if `$haystack` contains only items of `$type`.
	 *
	 * {{{
	 * $this->assertContainsOnly('int', array(1,2,3));
	 * }}}
	 *
	 * {{{
	 * $this->assertContainsOnly('int', array('foo', 'bar', 'baz'));
	 * }}}
	 *
	 * @param  string         $type     Data type to check for
	 * @param  mixed          $haystack Array or iterable object
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertContainsOnly($type, $haystack, $message = false) {
		return $this->_containsOnly($type, $haystack, $message);
	}

	/**
	 * Will mark the test `true` if `$haystack` does not have any of `$type`.
	 *
	 * {{{
	 * $this->assertNotContainsOnly('int', array('foo', 'bar', 'baz'));
	 * }}}
	 *
	 * {{{
	 * $this->assertNotContainsOnly('int', array(1,2,3));
	 * }}}
	 *
	 * @param  string         $type     Data type to check for
	 * @param  mixed          $haystack Array or iterable object
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertNotContainsOnly($type, $haystack, $message = false) {
		return $this->_containsOnly($type, $haystack, $message, false);
	}

	/**
	 * Code base for `assertContainsOnly()` & `assertNotContainsOnly()`
	 *
	 * @see    lithium\test\Unit::assertContainsOnly()
	 * @see    lithium\test\Unit::assertNotContainsOnly()
	 * @param  string         $type     Data type to check for
	 * @param  mixed          $haystack Array, iterable object, or string
	 * @param  string|boolean $message  Optional
	 * @param  boolean        $expected Optional
	 * @return boolean
	 */
	public function _containsOnly($type, $haystack, $message = false, $expected = true) {
		$method = self::$_internalTypes[$type];
		$data = array('expected' => $type, 'result' => $haystack);
		foreach($haystack as $key => $value) {
			if(!$method($value)) {
				return $this->assert(!$expected, $message, $data);
			}
		}
		return $this->assert($expected, $message, $data);
	}

	/**
	 * Will mark the test `true` if `$haystack` contains only items of `$type`.
	 *
	 * {{{
	 * $this->assertContainsOnlyInstancesOf('stdClass', array(new \stdClass));
	 * }}}
	 *
	 * {{{
	 * $this->assertContainsOnlyInstancesOf('stdClass', array(new \lithium\test\Unit));
	 * }}}
	 *
	 * @param  string         $class    Fully namespaced class name
	 * @param  mixed          $haystack Array or iterable object
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertContainsOnlyInstancesOf($class, $haystack, $message = false) {
		$result = array();
		foreach($haystack as $key => &$value) {
			if(!is_a($value, $class)) {
				$result[$key] =& $value;
				break;
			}
		}
		return $this->assert(empty($result), $message, array(
			'expected' => $class,
			'result' => $result
		));
	}

	/**
	 * Will mark the test `true` if the contents of `$expected` are equal to the
	 * contents of `$result`.
	 *
	 * {{{
	 * $file1 = LITHIUM_APP_PATH . '/tests/mocks/md/file_1.md';
	 * $file2 = LITHIUM_APP_PATH . '/tests/mocks/md/file_1.md.copy';
	 * $this->assertFileEquals($file1, $file2);
	 * }}}
	 *
	 * {{{
	 * $file1 = LITHIUM_APP_PATH . '/tests/mocks/md/file_1.md';
	 * $file2 = LITHIUM_APP_PATH . '/tests/mocks/md/file_2.md';
	 * $this->assertFileEquals($file1, $file2);
	 * }}}
	 *
	 * @param  string         $expected Path to the expected file
	 * @param  string         $result   Path to the actual file
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertFileEquals($expected, $result, $message = false) {
		$expected = md5_file($expected);
		$result = md5_file($result);
		return $this->assert($expected === $result, $message, compact('expected', 'result'));
	}

	/**
	 * Will mark the test `true` if the contents of `$expected` are not equal to
	 * the contents of `$result`.
	 *
	 * {{{
	 * $file1 = LITHIUM_APP_PATH . '/tests/mocks/md/file_1.md';
	 * $file2 = LITHIUM_APP_PATH . '/tests/mocks/md/file_2.md';
	 * $this->assertFileNotEquals($file1, $file2);
	 * }}}
	 *
	 * {{{
	 * $file1 = LITHIUM_APP_PATH . '/tests/mocks/md/file_1.md';
	 * $file2 = LITHIUM_APP_PATH . '/tests/mocks/md/file_1.md.copy';
	 * $this->assertFileNotEquals($file1, $file2);
	 * }}}
	 *
	 * @param  string         $expected Path to the expected file
	 * @param  string         $result   Path to the actual file
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertFileNotEquals($expected, $result, $message = false) {
		$expected = md5_file($expected);
		$result = md5_file($result);
		return $this->assert($expected !== $result, $message, compact('expected', 'result'));
	}

	/**
	 * Will mark the test `true` if the file `$result` exists.
	 *
	 * {{{
	 * $this->assertFileExists(LITHIUM_APP_PATH . '/readme.md');
	 * }}}
	 *
	 * {{{
	 * $this->assertFileExists(LITHIUM_APP_PATH . '/does/not/exist.txt');
	 * }}}
	 *
	 * @param  string         $result   Path to the file you are asserting
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertFileExists($result, $message = false) {
		return $this->assert(file_exists($result), $message, array(
			'expected' => $result,
			'result' => file_exists($result)
		));
	}

	/**
	 * Will mark the test `true` if the file `$result` does not exist.
	 *
	 * {{{
	 * $this->assertFileExists(LITHIUM_APP_PATH . '/does/not/exist.txt');
	 * }}}
	 *
	 * {{{
	 * $this->assertFileExists(LITHIUM_APP_PATH . '/readme.md');
	 * }}}
	 *
	 * @param  string         $result  Path to the file you are asserting
	 * @param  string|boolean $message Optional
	 * @return boolean
	 */
	public function assertFileNotExists($result, $message = false) {
		return $this->assert(!file_exists($result), $message, array(
			'expected' => $result,
			'result' => !file_exists($result)
		));
	}

	/**
	 * Will mark the test `true` if `$expected` greater than `$result`.
	 *
	 * {{{
	 * $this->assertGreaterThan(5, 3);
	 * }}}
	 *
	 * {{{
	 * $this->assertGreaterThan(3, 5);
	 * }}}
	 *
	 * @param  float|int      $expected
	 * @param  float|int      $result
	 * @param  string|boolean $message Optional
	 * @return boolean
	 */
	public function assertGreaterThan($expected, $result, $message = false) {
		return $this->assert($expected > $result, $message, array(
			'expected' => $expected,
			'result' => $result
		));
	}

	/**
	 * Will mark the test `true` if `$expected` great than or equal to `$result`.
	 *
	 * {{{
	 * $this->assertGreaterThanOrEqual(5, 5);
	 * }}}
	 *
	 * {{{
	 * $this->assertGreaterThanOrEqual(3, 5);
	 * }}}
	 *
	 * @param  float|int      $expected
	 * @param  float|int      $result
	 * @param  string|boolean $message Optional
	 * @return boolean
	 */
	public function assertGreaterThanOrEqual($expected, $result, $message = false) {
		return $this->assert($expected >= $result, $message, array(
			'expected' => $expected,
			'result' => $result
		));
	}

	/**
	 * Will mark the test `true` if `$expected` less than `$result`.
	 *
	 * {{{
	 * $this->assertLessThan(3, 5);
	 * }}}
	 *
	 * {{{
	 * $this->assertLessThan(5, 3);
	 * }}}
	 *
	 * @param  float|int      $expected
	 * @param  float|int      $result
	 * @param  string|boolean $message Optional
	 * @return boolean
	 */
	public function assertLessThan($expected, $result, $message = false) {
		return $this->assert($expected < $result, $message, array(
			'expected' => $expected,
			'result' => $result
		));
	}

	/**
	 * Will mark the test `true` if `$expected` is less than or equal to `$result`.
	 *
	 * {{{
	 * $this->assertLessThanOrEqual(5, 5);
	 * }}}
	 *
	 * {{{
	 * $this->assertLessThanOrEqual(5, 3);
	 * }}}
	 *
	 * @param  float|int      $expected
	 * @param  float|int      $result
	 * @param  string|boolean $message Optional
	 * @return boolean
	 */
	public function assertLessThanOrEqual($expected, $result, $message = false) {
		return $this->assert($expected <= $result, $message, array(
			'expected' => $expected,
			'result' => $result
		));
	}

	/**
	 * Will mark the test `true` if `$result` is a `$expected`.
	 *
	 * {{{
	 * $this->assertInstanceOf('stdClass', new stdClass);
	 * }}}
	 *
	 * {{{
	 * $this->assertInstanceOf('ReflectionClass', new stdClass);
	 * }}}
	 *
	 * @param  string         $expected Fully namespaced expected class
	 * @param  object         $result   Object you are testing
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertInstanceOf($expected, $result, $message = false) {
		return $this->assert(is_a($result, $expected), $message, array(
			'expected' => $expected,
			'result' => get_class($result)
		));
	}

	/**
	 * Will mark the test `true` if `$result` is not a `$expected`.
	 *
	 * {{{
	 * $this->assertNotInstanceOf('ReflectionClass', new stdClass);
	 * }}}
	 *
	 * {{{
	 * $this->assertNotInstanceOf('stdClass', new stdClass);
	 * }}}
	 *
	 * @param  string         $expected Fully namespaced expected class
	 * @param  object         $result   Object you are testing
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertNotInstanceOf($expected, $result, $message = false) {
		return $this->assert(!is_a($result, $expected), $message, array(
			'expected' => $expected,
			'result' => get_class($result)
		));
	}

	/**
	 * Will mark the test `true` if `$result` if of type $expected.
	 *
	 * {{{
	 * $this->assertInternalType('string', 'foobar');
	 * }}}
	 *
	 * {{{
	 * $this->assertInternalType('int', 'foobar');
	 * }}}
	 *
	 * @param  string         $expected Internal data type
	 * @param  object         $result   Object you are testing
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertInternalType($expected, $result, $message = false) {
		$method = self::$_internalTypes[$expected];
		return $this->assert($method($result), $message, array(
			'expected' => $expected,
			'result' => gettype($result)
		));
	}

	/**
	 * Will mark the test `true` if `$result` if not of type $expected.
	 *
	 * {{{
	 * $this->assertNotInternalType('int', 'foobar');
	 * }}}
	 *
	 * {{{
	 * $this->assertNotInternalType('string', 'foobar');
	 * }}}
	 *
	 * @param  string         $expected Internal data type
	 * @param  object         $result   Object you are testing
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertNotInternalType($expected, $result, $message = false) {
		$method = self::$_internalTypes[$expected];
		return $this->assert(!$method($result), $message, array(
			'expected' => $expected,
			'result' => gettype($result)
		));
	}

	/**
	 * Will mark the test `true` if `$object` has an attribute `$attrName`.
	 *
	 * {{{
	 * $this->assertObjectHasAttribute('name', '\ReflectionClass');
	 * }}}
	 *
	 * {{{
	 * $this->assertObjectHasAttribute('__construct', '\ReflectionClass');
	 * }}}
	 *
	 * @see    lithium\test\Unit::assertClassHasAttribute()
	 * @throws InvalidArgumentException When $object is not an object
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string         $object   Object to assert
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertObjectHasAttribute($attrName, $object, $message = false) {
		return $this->_objectNotHasAttribute($attrName, $object, $message, true);
	}

	/**
	 * Will mark the test `true` if `$object` has an attribute `$attrName`.
	 *
	 * {{{
	 * $this->assertObjectNotHasAttribute('__construct', '\ReflectionClass');
	 * }}}
	 *
	 * {{{
	 * $this->assertObjectNotHasAttribute('name', '\ReflectionClass');
	 * }}}
	 *
	 * @see    lithium\test\Unit::assertClassHasNotAttribute()
	 * @throws InvalidArgumentException When $object is not an object
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  string         $object   Object to assert
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertObjectNotHasAttribute($attrName, $object, $message = false) {
		return $this->_objectNotHasAttribute($attrName, $object, $message, false);
	}

	/**
	 * Code base for `assertObjectHasAttribute()` & `assertObjectNotHasAttribute()`
	 *
	 * @see    lithium\test\Unit::assertObjectHasAttribute()
	 * @see    lithium\test\Unit::assertObjectNotHasAttribute()
	 * @throws InvalidArgumentException When $object is not an object
	 * @param  string         $attrName Attribute you wish to look for
	 * @param  object         $object   Object to assert
	 * @param  string|boolean $message  Optional
	 * @param  bool           $expected Optional
	 * @return boolean
	 */
	protected function _objectNotHasAttribute($attrName, $object, $message = false, $expected = true) {
		if(!is_object($object)) {
			throw new InvalidArgumentException('Second argument $object must be an object');
		}
		$object = new ReflectionClass($object);
		return $this->assert($object->hasProperty($attrName) === $expected, $message, array(
			'expected' => $attrName,
			'result' => $object->getProperties()
		));
	}

	/**
	 * Will mark the test `true` if $result matches $expected using `sprintf` format.
	 *
	 * {{{
	 * $this->assertStringMatchesFormat('%d', '10')
	 * }}}
	 *
	 * {{{
	 * $this->assertStringMatchesFormat('%d', '10.555')
	 * }}}
	 *
	 * @link   http://php.net/sprintf
	 * @link   http://php.net/sscanf
	 * @param  string         $expected Expected format using sscanf's format
	 * @param  string         $result   Value to compare against
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertStringMatchesFormat($expected, $result, $message = false) {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		$scan = sscanf($result, $expected);
		return $this->assert($scan[0] == $result, $message, array(
			'expected' => $expected, 
			'result' => $scan
		));
	}

	/**
	 * Will mark the test `true` if $result doesn't match $expected using `sprintf` format.
	 *
	 * {{{
	 * $this->assertStringNotMatchesFormat('%d', '10.555')
	 * }}}
	 *
	 * {{{
	 * $this->assertStringNotMatchesFormat('%d', '10')
	 * }}}
	 *
	 * @link   http://php.net/sprintf
	 * @link   http://php.net/sscanf
	 * @param  string         $expected Expected format using sscanf's format
	 * @param  string         $result   Value to test against
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertStringNotMatchesFormat($expected, $result, $message = false) {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		$scan = sscanf($result, $expected);
		return $this->assert($scan[0] != $result, $message, array(
			'expected' => $expected, 
			'result' => $scan
		));
	}

	/**
	 * Will mark the test `true` if $result ends with `$expected`.
	 *
	 * {{{
	 * $this->assertStringEndsWith('bar', 'foobar');
	 * }}}
	 *
	 * {{{
	 * $this->assertStringEndsWith('foo', 'foobar');
	 * }}}
	 *
	 * @param  string         $expected The suffix to check for
	 * @param  string         $result   Value to test against
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertStringEndsWith($expected, $result, $message = false) {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		return $this->assert(!!preg_match("/$expected\$/", $result, $matches), $message, array(
			'expected' => $expected,
			'result' => $result
		));
	}

	/**
	 * Will mark the test `true` if $result starts with `$expected`.
	 *
	 * {{{
	 * $this->assertStringStartsWith('foo', 'foobar');
	 * }}}
	 *
	 * {{{
	 * $this->assertStringStartsWith('bar', 'foobar');
	 * }}}
	 *
	 * @param  string         $expected Prefix to check for
	 * @param  string         $result   Value to test against
	 * @param  string|boolean $message  Optional
	 * @return boolean
	 */
	public function assertStringStartsWith($expected, $result, $message = false) {
		list($expected, $result) = $this->_normalizeLineEndings($expected, $result);
		return $this->assert(preg_match("/^$expected/", $result, $matches), $message, array(
			'expected' => $expected,
			'result' => $result
		));
	}

}

?>