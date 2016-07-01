<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\test;

use lithium\aop\Filters;
use lithium\util\Text;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Reflection;

$message  = 'lithium\test\Mocker has been deprecated, as alternatives ';
$message .= 'exist (i.e. Mockery) which take the task of maintaining a ';
$message .= 'mocking framework from us.';
trigger_error($message, E_USER_DEPRECATED);

/**
 * The Mocker class aids in the creation of Mocks on the fly, allowing you to
 * use Lithium filters on most methods in a class as close to the test as
 * possible.
 *
 * ## How to use it
 * To create a new Mock, you need to register `Mocker`, then call or instantiate
 * the same class but with '\Mock' appended to the end of the class name.
 *
 * ### Registering Mocker
 * To enable the autoloading of mocks you simply need to make a simple method
 * call.
 * ```
 * use lithium\core\Environment;
 * use lithium\test\Mocker;
 * if (!Environment::is('production')) {
 *   Mocker::register();
 * }
 * ```
 *
 * You can also enable autoloading inside the setup of a unit test class. This
 * method can be called redundantly.
 * ```
 * use lithium\test\Mocker;
 * class MockerTest extends \lithium\test\Unit {
 *   public function setUp() {
 *     Mocker::register();
 *   }
 * }
 * ```
 *
 * ### Usage and Examples
 * Using Mocker is the fun magical part, it's autoloaded so simply call the
 * class you want to mock with the '\Mock' at the end. The autoloader will
 * detect you want to autoload it, and create it for you. Now you can filter
 * any method.
 *
 * ```
 * use lithium\console\dispatcher\Mock as DispatcherMock;
 * $dispatcher = new DispatcherMock();
 * $dispatcher->applyFilter('config', function($params, $next) {
 * 	return [];
 * });
 * $results = $dispatcher->config();
 * ```
 * ```
 * use lithium\analysis\parser\Mock as ParserMock;
 * $code = 'echo "foobar";';
 * ParserMock::applyFilter('config', function($params, $next) {
 *   return [];
 * });
 * $tokens = ParserMock::tokenize($code, ['wrap' => true]);
 * ```
 *
 * Mocker also gives the ability, if used correctly, to stub build in php
 * function calls. Consider the following example.
 * ```
 * namespace app\extensions;
 *
 * class AwesomeFileEditor {
 *
 *   public static function updateJson($file) {
 *     if (file_exists($file)) {
 *       $time = microtime(true);
 *       $packages = json_decode(file_get_contents($file), true);
 *       foreach ($packages['users'] as &$package) {
 *         $package['updated'] = $time;
 *       }
 *       return $packages;
 *     }
 *     return false;
 *   }
 *
 * }
 * ```
 * ```
 * namespace app\tests\cases\extensions;
 *
 * use lithium\test\Mocker;
 * use app\extensions\AwesomeFileEditor;
 *
 * class AwesomeFileEditorTest extends \lithium\test\Unit {
 *
 *   public function setUp() {
 *     Mocker::overwriteFunction(false);
 *   }
 *
 *   public function testUpdateJson() {
 *     Mocker::overwriteFunction('app\extensions\file_exists', function() {
 *       return true;
 *     });
 *     Mocker::overwriteFunction('app\extensions\file_get_contents', function() {
 *       return <<<EOD
 * {
 *   "users": [
 *     {
 *       "name": "BlaineSch",
 *       "updated": 0
 *     }
 *   ]
 * }
 * EOD;
 *     });
 *
 *     $results = AwesomeFileEditor::updateJson('idontexist.json');
 *     $this->assertNotEqual(0, $results['users'][0]['updated']);
 *   }
 *
 * }
 * ```
 *
 * ## How does Mocking classes work?
 * This section isn't necessary to read, but can help others better understand
 * it so that they can add new features, or debug current ones.
 *
 * ### TLDR
 * The `Mocker` class dynamically makes two classes, a `Delegate` and a `Mock`.
 * Both of these classes extend the target class. The `Delegate` is passed into
 * the `Mock` class for it to call within (anonymous functions) filters. This
 * allows public and protected methods to be filterable.
 *
 * ### Theory
 * I'll walk you through the steps I did in order to figure out how `Mocker`
 * should work. The goal here is to mock class `Person`.
 *
 * ```
 * class Person {
 *   public function speak() {
 *     $this->_openMouth();
 *     return true;
 *   }
 *   protected function _openMouth() {
 *     return $this->mouth = 'open';
 *   }
 * }
 * ```
 *
 * In order to make the `speak()` method filterable we'll need to create a class
 * called `MockPerson` and we'll make its `speak()` method filterable, however
 * there is already an issue since a filter works inside of an anonymous
 * function you cannot call `parent`, so `MockPerson` will also need an instance
 * of `Person`.
 *
 * ```
 * class MockPerson extends Person {
 *   public $person;
 *   public function speak() {
 *     $params = compact();
 *     $person = $this->person;
 *     return Filters::run($this, __FUNCTION__, [], function($params) use (&$person) {
 *       return $person->speak();
 *     };
 *   }
 * }
 * ```
 *
 * You might stop here and call it a day, but what about filtering protected
 * methods? For example you might want to make sure `_openMouth()` does not
 * modify the class. However this isn't possible with the current implementation
 * since `_openMouth` is protected and we can't call protected methods within an
 * anonymous function. The trick is that when you are extending a class you can
 * make a method MORE visible than its parent, with the exception of private
 * methods. So let's make a class `DelegatePerson` that simply extends `Person`
 * and makes `_openMouth()` public.
 *
 * ```
 * class DelegatePerson extends Person {
 *   public function _openMouth() {
 *     parent::_openMouth();
 *   }
 * }
 * ```
 *
 * Now we simply pass `DelegatePerson` to `MockPerson` and all methods are now
 * filterable.
 *
 * ## How does overwriting PHP functions work?
 * In short, this is a hack. When you are inside of a namespace `foo\bar\baz`
 * and you call a function `file_get_contents` it first searches the current
 * namespace for that function `foo\bar\baz\file_get_contents`. `Mocker` simply
 * creates that function dynamically, so when its called it delegates back to
 * `Mocker` which will determine if it should call a user-defined function or
 * if it should go back to the original PHP function.
 *
 * @deprecated Please use an alternative mocking framework, i.e. Mockery.
 */
class Mocker {

	/**
	 * Functions to be called instead of the original.
	 *
	 * The key is the fully namespaced function name, and the value is the closure to be called.
	 *
	 * @var array
	 */
	protected static $_functionCallbacks = [];

	/**
	 * Results of function calls for later assertion in `MockerChain`.
	 *
	 * @var array
	 */
	protected static $_functionResults = [];

	/**
	 * A list of code to be generated for the `Delegate`.
	 *
	 * The `Delegate` directly extends the class you wish to mock and makes all
	 * methods publically available to other classes but should not be accessed
	 * directly by any other classes other than `Mock`.
	 *
	 * @item variable `$parent` Instance of `Mock`. Allows `Delegate` to send
	 *                          calls back to `Mock` if it was called directly
	 *                          from a parent class.
	 * @var array
	 */
	protected static $_mockDelegateIngredients = [
		'startClass' => [
			'namespace {:namespace};',
			'class MockDelegate extends \{:mocker} {',
			'    public $parent = null;',
		],
		'constructor' => [
			'{:modifiers} function __construct({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $argCount = func_num_args();',
			'    $this->parent = $argCount === 0 ? false : func_get_arg($argCount - 1);',
			'    if (!is_a($this->parent, __NAMESPACE__ . "\Mock")) {',
			'        $class = new \ReflectionClass(\'{:namespace}\Mock\');',
			'        $this->parent = $class->newInstanceArgs($args);',
			'    }',
			'    $this->parent->mocker = $this;',
			'    if (method_exists(\'{:mocker}\', "__construct")) {',
			'        call_user_func_array("parent::__construct", $args);',
			'    }',
			'}',
		],
		'method' => [
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $token = spl_object_hash($this);',
			'    if (func_num_args() > 0 && func_get_arg(func_num_args() - 1) === $token) {',
			'        return call_user_func_array("parent::{:method}", compact({:stringArgs}));',
			'    }',
			'    $method = [$this->parent, "{:method}"];',
			'    return call_user_func_array($method, $args);',
			'}',
		],
		'staticMethod' => [
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $token = "1f3870be274f6c49b3e31a0c6728957f";',
			'    if (func_num_args() > 0 && func_get_arg(func_num_args() - 1) === $token) {',
			'        return call_user_func_array("parent::{:method}", compact({:stringArgs}));',
			'    }',
			'    $method = \'{:namespace}\Mock::{:method}\';',
			'    return call_user_func_array($method, $args);',
			'}',
		],
		'endClass' => [
			'}',
		],
	];

	/**
	 * List of code to be generated for overwriting php functions.
	 *
	 * @var array
	 */
	protected static $_mockFunctionIngredients = [
		'function' => [
			'namespace {:namespace};',
			'use lithium\test\Mocker;',
			'function {:function}({:args}) {',
			'    $params = [];',
			'    foreach ([{:stringArgs}] as $value) {',
			'        if (!empty($value)) {',
			'            $params[] =& ${$value};',
			'        }',
			'    }',
			'    return Mocker::callFunction(__FUNCTION__, $params);',
			'}',
		],
	];

	/**
	 * A list of code to be generated for the `Mock`.
	 *
	 * The Mock class directly extends the class you wish to mock but only
	 * interacts with the `Delegate` directly. This class is the public
	 * interface for users.
	 *
	 * @item variable `$results` All method calls allowing you for you make your
	 *                           own custom assertions.
	 * @item variable `$staticResults` See `$results`.
	 * @item variable `$mocker` Home of the `Delegate` defined above.
	 * @item variable `$_safeVars` Variables that should not be deleted on
	 *                             `Mock`. We delete them so they cannot be
	 *                             accessed directly, but sent to `Delegate` via
	 *                             PHP magic methods on `Mock`.
	 * @var array
	 */
	protected static $_mockIngredients = [
		'startClass' => [
			'namespace {:namespace};',
			'use lithium\aop\Filters as _Filters;',
			'class Mock extends \{:mocker} {',
			'    public $mocker;',
			'    public $results = [];',
			'    public static $staticResults = [];',
			'    protected $_safeVars = [',
			'        "_classes",',
			'        "mocker",',
			'        "_safeVars",',
			'        "results",',
			'        "staticResults",',
			'        "_methodFilters",',
			'    ];',
		],
		'get' => [
			'public function {:reference}__get($name) {',
			'    $data ={:reference} $this->mocker->$name;',
			'    return $data;',
			'}',
		],
		'set' => [
			'public function __set($name, $value = null) {',
			'    return $this->mocker->$name = $value;',
			'}',
		],
		'isset' => [
			'public function __isset($name) {',
			'    return isset($this->mocker->$name);',
			'}',
		],
		'unset' => [
			'public function __unset($name) {',
			'    unset($this->mocker->$name);',
			'}',
		],
		'constructor' => [
			'{:modifiers} function __construct({:args}) {',
			'    $args = compact({:stringArgs});',
			'    array_push($args, $this);',
			'    foreach (get_class_vars(get_class($this)) as $key => $value) {',
			'        if (isset($this->{$key}) && !in_array($key, $this->_safeVars)) {',
			'            unset($this->$key);',
			'        }',
			'    }',
			'    $class = new \ReflectionClass(\'{:namespace}\MockDelegate\');',
			'    $class->newInstanceArgs($args);',
			'}',
		],
		'destructor' => [
			'public function __destruct() {}',
		],
		'staticMethod' => [
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $args["hash"] = "1f3870be274f6c49b3e31a0c6728957f";',
			'    $method = \'{:namespace}\MockDelegate::{:method}\';',
			'    $result = _Filters::run(__CLASS__, "{:method}", $args,',
			'        function($args) use(&$method) {',
			'            return call_user_func_array($method, $args);',
			'        }',
			'    );',
			'    if (!isset(static::$staticResults["{:method}"])) {',
			'        static::$staticResults["{:method}"] = [];',
			'    }',
			'    static::$staticResults["{:method}"][] = [',
			'        "args" => func_get_args(),',
			'        "result" => $result,',
			'        "time" => microtime(true),',
			'    ];',
			'    return $result;',
			'}',
		],
		'method' => [
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $args["hash"] = spl_object_hash($this->mocker);',
			'    $_method = [$this->mocker, "{:method}"];',
			'    $result = _Filters::run(__CLASS__, "{:method}", $args,',
			'        function($args) use(&$_method) {',
			'           return call_user_func_array($_method, $args);',
			'        }',
			'    );',
			'    if (!isset($this->results["{:method}"])) {',
			'        $this->results["{:method}"] = [];',
			'    }',
			'    $this->results["{:method}"][] = [',
			'        "args" => func_get_args(),',
			'        "result" => $result,',
			'        "time" => microtime(true),',
			'    ];',
			'    return $result;',
			'}',
		],
		'applyFilter' => [
			'public {:static} function applyFilter($method, $filter = null) {',
			'    $message  = "<mocked class>::applyFilter() is deprecated. ";',
			'    $message .= "Use Filters::applyFilter(" . __CLASS__ .", ...) instead.";',
			'    // trigger_error($message, E_USER_DEPRECATED);',
			'    foreach ((array) $method as $m) {',
			'        if ($filter === null) {',
			'            _Filters::clear(__CLASS__, $m);',
			'        } else {',
			'            _Filters::apply(__CLASS__, $m, $filter);',
			'        }',
			'    }',
			'}',
		],
		'endClass' => [
			'}',
		],
	];

	/**
	 * A list of methods we should not overwrite in our mock class.
	 *
	 * Some of these methods are are too custom inside the `Mock` or `Delegate`,
	 * while others should simply not be filtered.
	 *
	 * @var array
	 */
	protected static $_blackList = [
		'__destruct', '_parents',
		'__get', '__set', '__isset', '__unset', '__sleep',
		'__wakeup', '__toString', '__clone', '__invoke',
		'_stop', '_init', 'invokeMethod', '__set_state',
		'_instance', '_object', '_initialize',
		'_filter', 'applyFilter',
	];

	/**
	 * Will register this class into the autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register([__CLASS__, 'create']);
	}

	/**
	 * The main entrance to create a new Mock class.
	 *
	 * @param  string $mockee The fully namespaced `\Mock` class
	 * @return void
	 */
	public static function create($mockee) {
		if (!static::_validateMockee($mockee)) {
			return;
		}

		$mocker = static::_mocker($mockee);
		$isStatic = is_subclass_of($mocker, 'lithium\core\StaticObject');

		$tokens = [
			'namespace' => static::_namespace($mockee),
			'mocker' => $mocker,
			'mockee' => 'MockDelegate',
			'static' => $isStatic ? 'static' : '',
		];
		$mockDelegate = static::_dynamicCode('mockDelegate', 'startClass', $tokens);
		$mock = static::_dynamicCode('mock', 'startClass', $tokens);

		$reflectedClass = new ReflectionClass($mocker);
		$reflecedMethods = $reflectedClass->getMethods();
		$getByReference = false;
		$staticApplyFilter = true;
		$constructor = false;
		foreach ($reflecedMethods as $methodId => $method) {
			if (!in_array($method->name, static::$_blackList)) {
				$key = $method->isStatic() ? 'staticMethod' : 'method';
				if ($method->name === '__construct') {
					$key = 'constructor';
					$constructor = true;
				}
				$docs = ReflectionMethod::export($mocker, $method->name, true);
				if (preg_match('/&' . $method->name . '/', $docs) === 1) {
					continue;
				}
				$tokens = [
					'namespace' => static::_namespace($mockee),
					'method' => $method->name,
					'modifiers' => static::_methodModifiers($method),
					'args' => static::_methodParams($method),
					'stringArgs' => static::_stringMethodParams($method),
					'mocker' => $mocker,
				];
				$mockDelegate .= static::_dynamicCode('mockDelegate', $key, $tokens);
				$mock .= static::_dynamicCode('mock', $key, $tokens);
			} elseif ($method->name === '__get') {
				$docs = ReflectionMethod::export($mocker, '__get', true);
				$getByReference = preg_match('/&__get/', $docs) === 1;
			} elseif ($method->name === 'applyFilter') {
				$staticApplyFilter = $method->isStatic();
			}
		}

		if (!$constructor) {
			$tokens = [
				'namespace' => static::_namespace($mockee),
				'modifiers' => 'public',
				'args' => null,
				'stringArgs' => 'array()',
				'mocker' => $mocker,
			];
			$mock .= static::_dynamicCode('mock', 'constructor', $tokens);
			$mockDelegate .= static::_dynamicCode('mockDelegate', 'constructor', $tokens);
		}

		$mockDelegate .= static::_dynamicCode('mockDelegate', 'endClass');
		$mock .= static::_dynamicCode('mock', 'get', [
			'reference' => $getByReference ? '&' : '',
		]);
		$mock .= static::_dynamicCode('mock', 'set');
		$mock .= static::_dynamicCode('mock', 'isset');
		$mock .= static::_dynamicCode('mock', 'unset');
		$mock .= static::_dynamicCode('mock', 'applyFilter', [
			'static' => $staticApplyFilter ? 'static' : '',
		]);
		$mock .= static::_dynamicCode('mock', 'destructor');
		$mock .= static::_dynamicCode('mock', 'endClass');

		eval($mockDelegate . $mock);
	}

	/**
	 * Will determine what method mofifiers of a method.
	 *
	 * For instance: 'public static' or 'private abstract'
	 *
	 * @param  ReflectionMethod $method
	 * @return string
	 */
	protected static function _methodModifiers(ReflectionMethod $method) {
		$modifierKey = $method->getModifiers();
		$modifierArray = Reflection::getModifierNames($modifierKey);
		$modifiers = implode(' ', $modifierArray);
		return str_replace(['private', 'protected'], 'public', $modifiers);
	}

	/**
	 * Will determine what parameter prototype of a method.
	 *
	 * For instance: 'ReflectionFunctionAbstract $method' or '$name, array $foo = null'
	 *
	 * @param  ReflectionFunctionAbstract $method
	 * @return string
	 */
	protected static function _methodParams(ReflectionFunctionAbstract $method) {
		$pattern = '/Parameter #[0-9]+ \[ [^\>]+>([^\]]+) \]/';
		$replace = [
			'from' => [' Array', 'or NULL'],
			'to' => [' array()', ''],
		];
		preg_match_all($pattern, $method, $matches);
		$params = implode(', ', $matches[1]);
		return str_replace($replace['from'], $replace['to'], $params);
	}

	/**
	 * Will return the params in a way that can be placed into `compact()`
	 *
	 * @param  ReflectionFunctionAbstract $method
	 * @return string
	 */
	protected static function _stringMethodParams(ReflectionFunctionAbstract $method) {
		$pattern = '/Parameter [^$]+\$([^ ]+)/';
		preg_match_all($pattern, $method, $matches);
		$params = implode("', '", $matches[1]);
		return strlen($params) > 0 ? "'{$params}'" : 'array()';
	}

	/**
	 * Will generate the code you are wanting.
	 *
	 * This pulls from $_mockDelegateIngredients and $_mockIngredients.
	 *
	 * @param  string $type   The name of the array of ingredients to use
	 * @param  string $key    The key from the array of ingredients
	 * @param  array  $tokens Tokens, if any, that should be inserted
	 * @return string
	 */
	protected static function _dynamicCode($type, $key, $tokens = []) {
		$defaults = [
			'master' => '\lithium\test\Mocker',
		];
		$tokens += $defaults;
		$name = '_' . $type . 'Ingredients';
		$code = implode("\n", static::${$name}[$key]);
		return Text::insert($code, $tokens) . "\n";
	}

	/**
	 * Will generate the mocker from the current mockee.
	 *
	 * @param  string $mockee The fully namespaced `\Mock` class
	 * @return array
	 */
	protected static function _mocker($mockee) {
		$sections = explode('\\', $mockee);
		array_pop($sections);
		$sections[] = ucfirst(array_pop($sections));
		return implode('\\', $sections);
	}

	/**
	 * Will generate the namespace from the current mockee.
	 *
	 * @param  string $mockee The fully namespaced `\Mock` class
	 * @return string
	 */
	protected static function _namespace($mockee) {
		$matches = [];
		preg_match_all('/^(.*)\\\\Mock$/', $mockee, $matches);
		return isset($matches[1][0]) ? $matches[1][0] : null;
	}

	/**
	 * Will validate if mockee is a valid class we should mock.
	 *
	 * Will fail if the mock already exists, or it doesn't contain `\Mock` in
	 * the namespace.
	 *
	 * @param  string $mockee The fully namespaced `\Mock` class
	 * @return bool
	 */
	protected static function _validateMockee($mockee) {
		return preg_match('/\\\\Mock$/', $mockee) === 1;
	}

	/**
	 * Generate a chain class with the current rules of the mock.
	 *
	 * @param  mixed  $mock Mock object, namespaced static mock, namespaced function name.
	 * @return object       MockerChain instance
	 */
	public static function chain($mock) {
		$results = [];
		$string = is_string($mock);
		if (is_object($mock) && isset($mock->results)) {
			$results = static::mergeResults($mock->results, $mock::$staticResults);
		} elseif ($string && class_exists($mock) && isset($mock::$staticResults)) {
			$results = $mock::$staticResults;
		} elseif ($string && function_exists($mock) && isset(static::$_functionResults[$mock])) {
			$results = [$mock => static::$_functionResults[$mock]];
		}
		return new MockerChain($results);
	}

	/**
	 * Will merge two sets of results into each other.
	 *
	 * @param  array $results
	 * @param  array $secondary
	 * @return array
	 */
	public static function mergeResults($results, $secondary) {
		foreach ($results as $method => $calls) {
			if (isset($secondary[$method])) {
				$results['method1'] = array_merge($results['method1'], $secondary['method1']);
				usort($results['method1'], function($el1, $el2) {
					return strcmp($el1['time'], $el2['time']);
				});
				unset($secondary['method1']);
			}
		}
		return $results + $secondary;
	}

	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper for
	 * `forward_static_call_array()`.
	 *
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling `$method`.
	 * @return mixed Returns the result of the method call.
	 */
	public static function invokeMethod($method, $params = []) {
		return forward_static_call_array([get_called_class(), $method], $params);
	}

	/**
	 * Will overwrite namespaced functions.
	 *
	 * @param  string|bool   $name     Fully namespaced function, or `false` to reset functions.
	 * @param  closure|bool  $callback Callback to be called, or `false` to reset this function.
	 * @return void
	 */
	public static function overwriteFunction($name, $callback = null) {
		if ($name === false) {
			static::$_functionResults = [];
			return static::$_functionCallbacks = [];
		}
		if ($callback === false) {
			static::$_functionResults[$name] = [];
			return static::$_functionCallbacks[$name] = false;
		}
		static::$_functionCallbacks[$name] = $callback;
		if (function_exists($name)) {
			return;
		}

		$function = new ReflectionFunction($callback);
		$pos = strrpos($name, '\\');
		eval(static::_dynamicCode('mockFunction', 'function', [
			'namespace' => substr($name, 0, $pos),
			'function' => substr($name, $pos + 1),
			'args' => static::_methodParams($function),
			'stringArgs' => static::_stringMethodParams($function),
		]));
		return;
	}

	/**
	 * A method to call user defined functions.
	 *
	 * This method should only be accessed by functions created by `Mocker::overwriteFunction()`.
	 *
	 * If no matching stored function exists, the global function will be called instead.
	 *
	 * @param  string $name   Fully namespaced function name to call.
	 * @param  array  $params Params to be passed to the function.
	 * @return mixed
	 */
	public static function callFunction($name, array &$params = []) {
		$function = substr($name, strrpos($name, '\\'));
		$exists = isset(static::$_functionCallbacks[$name]);
		if ($exists && is_callable(static::$_functionCallbacks[$name])) {
			$function = static::$_functionCallbacks[$name];
		}
		$result = call_user_func_array($function, $params);
		if (!isset(static::$_functionResults[$name])) {
			static::$_functionResults[$name] = [];
		}
		static::$_functionResults[$name][] = [
			'args' => $params,
			'result' => $result,
			'time' => microtime(true),
		];
		return $result;
	}

	/* Deprecated / BC */

	/**
	 * Stores the closures that represent the method filters. They are indexed by called class.
	 *
	 * @deprecated
	 * @var array Method filters, indexed by class.
	 */
	protected static $_methodFilters = [];

	/**
	 * Apply a closure to a method of the current static object.
	 *
	 * @deprecated
	 * @see lithium\core\StaticObject::_filter()
	 * @see lithium\util\collection\Filters
	 * @param string $class Fully namespaced class to apply filters.
	 * @param mixed $method The name of the method to apply the closure to. Can either be a single
	 *        method name as a string, or an array of method names. Can also be false to remove
	 *        all filters on the current object.
	 * @param \Closure $filter The closure that is used to filter the method(s), can also be false
	 *        to remove all the current filters for the given method.
	 * @return void
	 */
	public static function applyFilter($class, $method = null, $filter = null) {
		$message  = '`' . __METHOD__ . '()` has been deprecated in favor of ';
		$message .= '`\lithium\aop\Filters::apply()` and `::clear()`.';
		trigger_error($message, E_USER_DEPRECATED);

		$class = get_called_class();

		if ($method === false) {
			Filters::clear($class);
			return;
		}
		foreach ((array) $method as $m) {
			if ($filter === false) {
				Filters::clear($class, $m);
			} else {
				Filters::apply($class, $m, $filter);
			}
		}
	}

	/**
	 * Executes a set of filters against a method by taking a method's main implementation as a
	 * callback, and iteratively wrapping the filters around it.
	 *
	 * @deprecated
	 * @see lithium\util\collection\Filters
	 * @param string $class Fully namespaced class to apply filters.
	 * @param string|array $method The name of the method being executed, or an array containing
	 *        the name of the class that defined the method, and the method name.
	 * @param array $params An associative array containing all the parameters passed into
	 *        the method.
	 * @param \Closure $callback The method's implementation, wrapped in a closure.
	 * @param array $filters Additional filters to apply to the method for this call only.
	 * @return mixed
	 */
	protected static function _filter($class, $method, $params, $callback, $filters = []) {
		$message  = '`' . __METHOD__ . '()` has been deprecated in favor of ';
		$message .= '`\lithium\aop\Filters::run()` and `::apply()`.';
		trigger_error($message, E_USER_DEPRECATED);

		$class = get_called_class();

		foreach ($filters as $filter) {
			Filters::apply($class, $method, $filter);
		}
		return Filters::run($class, $method, $params, $callback);
	}
}

?>