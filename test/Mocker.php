<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use lithium\util\String;
use lithium\util\collection\Filters;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Reflection;
use Closure;

/**
 * The Mocker class aids in the creation of Mocks on the fly, allowing you to
 * use Lithium filters on most methods in the class.
 *
 * To enable the autoloading of mocks you simply need to make a simple method
 * call.
 * {{{
 * use lithium\core\Environment;
 * use lithium\test\Mocker;
 * if (!Environment::is('production')) {
 *   Mocker::register();
 * }
 * }}}
 *
 * You can also enable autoloading inside the setup of a unit test class. This
 * method can be called redundantly.
 * {{{
 * use lithium\test\Mocker;
 * class MockerTest extends \lithium\test\Unit {
 *   public function setUp() {
 *     Mocker::register();
 *   }
 * }
 * }}}
 *
 * Using Mocker is the fun magical part, it's autoloaded so simply call the
 * class you want to mock with the '\Mock' at the end. The autoloader will
 * detect you want to autoload it, and create it for you. Now you can filter
 * any method.
 * {{{
 * use lithium\console\dispatcher\Mock as DispatcherMock;
 * $dispatcher = new DispatcherMock();
 * $dispatcher->applyFilter('config', function($self, $params, $chain) {
 * 	return array();
 * });
 * $results = $dispatcher->config();
 * }}}
 * {{{
 * use lithium\analysis\parser\Mock as ParserMock;
 * $code = 'echo "foobar";';
 * ParserMock::applyFilter('config', function($self, $params, $chain) {
 *   return array();
 * });
 * $tokens = ParserMock::tokenize($code, array('wrap' => true));
 * }}}
 *
 * Mocker also gives the ability, if used correctly, to stub build in php
 * function calls. Consider the following example.
 * {{{
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
 * }}}
 * {{{
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
 * }}}
 */
class Mocker {

	/**
	 * Stores the closures that represent the method filters. They are indexed by called class.
	 *
	 * @var array Method filters, indexed by class.
	 */
	protected static $_methodFilters = array();

	/**
	 * Functions to be called instead of the original.
	 *
	 * The key is the fully namespaced function name, and the value is the closure to be called.
	 *
	 * @var array
	 */
	protected static $_functionCallbacks = array();

	/**
	 * Results of function calls for later assertion in `MockerChain`.
	 *
	 * @var array
	 */
	protected static $_functionResults = array();

	/**
	 * A list of code to be generated for the delegator.
	 *
	 * The MockDelgate directly extends the mocker and makes all methods
	 * publically available to other classes but should not be accessed directly
	 * by any other application. This should be called only by the mocker and
	 * the mockee and never by the consumer.
	 *
	 * @var array
	 */
	protected static $_mockDelegateIngredients = array(
		'startClass' => array(
			'namespace {:namespace};',
			'class MockDelegate extends \{:mocker} {',
			'    public $parent = null;',
		),
		'constructor' => array(
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
		),
		'method' => array(
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $token = spl_object_hash($this);',
			'    if (func_num_args() > 0 && func_get_arg(func_num_args() - 1) === $token) {',
			'        return call_user_func_array("parent::{:method}", compact({:stringArgs}));',
			'    }',
			'    $method = array($this->parent, "{:method}");',
			'    return call_user_func_array($method, $args);',
			'}',
		),
		'staticMethod' => array(
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $token = "1f3870be274f6c49b3e31a0c6728957f";',
			'    if (func_num_args() > 0 && func_get_arg(func_num_args() - 1) === $token) {',
			'        return call_user_func_array("parent::{:method}", compact({:stringArgs}));',
			'    }',
			'    $method = \'{:namespace}\Mock::{:method}\';',
			'    return call_user_func_array($method, $args);',
			'}',
		),
		'endClass' => array(
			'}',
		),
	);

	/**
	 * List of code to be generated for the function mock.
	 *
	 * @var array
	 */
	protected static $_mockFunctionIngredients = array(
		'function' => array(
			'namespace {:namespace};',
			'use lithium\test\Mocker;',
			'function {:function}({:args}) {',
			'    $params = array();',
			'    foreach (array({:stringArgs}) as $value) {',
			'        if (!empty($value)) {',
			'            $params[] =& ${$value};',
			'        }',
			'    }',
			'    return Mocker::callFunction(__FUNCTION__, $params);',
			'}',
		),
	);

	/**
	 * A list of code to be generated for the mocker.
	 *
	 * The Mock class directly extends the mock class but only directly
	 * interacts with the MockDelegate directly. This class is the actual
	 * interface for consumers, instantiation or static method calls, and can
	 * have most of its methods filtered.
	 *
	 * The `$results` variable holds all method calls allowing you for you
	 * make your own custom assertions on them.
	 *
	 * @var array
	 */
	protected static $_mockIngredients = array(
		'startClass' => array(
			'namespace {:namespace};',
			'class Mock extends \{:mocker} {',
			'    public $mocker;',
			'    public $results = array();',
			'    public static $staticResults = array();',
			'    protected $_safeVars = array(',
			'        "_classes",',
			'        "_methodFilters",',
			'        "mocker",',
			'        "_safeVars",',
			'        "results",',
			'        "staticResults",',
			'    );',
		),
		'get' => array(
			'public function {:reference}__get($name) {',
			'    $data ={:reference} $this->mocker->$name;',
			'    return $data;',
			'}',
		),
		'set' => array(
			'public function __set($name, $value = null) {',
			'    return $this->mocker->$name = $value;',
			'}',
		),
		'isset' => array(
			'public function __isset($name) {',
			'    return isset($this->mocker->$name);',
			'}',
		),
		'unset' => array(
			'public function __unset($name) {',
			'    unset($this->mocker->$name);',
			'}',
		),
		'constructor' => array(
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
		),
		'destructor' => array(
			'public function __destruct() {}',
		),
		'staticMethod' => array(
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $args["hash"] = "1f3870be274f6c49b3e31a0c6728957f";',
			'    $method = \'{:namespace}\MockDelegate::{:method}\';',
			'    $result = {:master}::invokeMethod("_filter", array(',
			'        __CLASS__, ',
			'        "{:method}",',
			'        $args,',
			'        function($self, $args) use(&$method) {',
			'            return call_user_func_array($method, $args);',
			'        }',
			'    ));',
			'    if (!isset(self::$staticResults["{:method}"])) {',
			'        self::$staticResults["{:method}"] = array();',
			'    }',
			'    self::$staticResults["{:method}"][] = array(',
			'        "args" => func_get_args(),',
			'        "result" => $result,',
			'        "time" => microtime(true),',
			'    );',
			'    return $result;',
			'}',
		),
		'method' => array(
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $args["hash"] = spl_object_hash($this->mocker);',
			'    $method = array($this->mocker, "{:method}");',
			'    $result = {:master}::invokeMethod("_filter", array(',
			'        __CLASS__,',
			'        "{:method}",',
			'        $args,',
			'        function($self, $args) use(&$method) {',
			'           return call_user_func_array($method, $args);',
			'        }',
			'    ));',
			'    if (!isset($this->results["{:method}"])) {',
			'        $this->results["{:method}"] = array();',
			'    }',
			'    $this->results["{:method}"][] = array(',
			'        "args" => func_get_args(),',
			'        "result" => $result,',
			'        "time" => microtime(true),',
			'    );',
			'    return $result;',
			'}',
		),
		'applyFilter' => array(
			'public {:static} function applyFilter($method, $filter = null) {',
			'    return {:master}::applyFilter(__CLASS__, $method, $filter);',
			'}',
		),
		'endClass' => array(
			'}',
		),
	);

	/**
	 * A list of methods we should not overwrite in our mock class.
	 *
	 * @var array
	 */
	protected static $_blackList = array(
		'__destruct', '_parents',
		'__get', '__set', '__isset', '__unset', '__sleep',
		'__wakeup', '__toString', '__clone', '__invoke',
		'_stop', '_init', 'invokeMethod', '__set_state',
		'_instance', '_filter', '_object', '_initialize',
		'applyFilter',
	);

	/**
	 * Will register this class into the autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register(array(__CLASS__, 'create'));
	}

	/**
	 * The main entrance to create a new Mock class.
	 *
	 * @param  string $mockee The fully namespaced `\Mock` class
	 * @return void
	 */
	public static function create($mockee) {
		if (!self::_validateMockee($mockee)) {
			return;
		}

		$mocker = self::_mocker($mockee);
		$isStatic = is_subclass_of($mocker, 'lithium\core\StaticObject');

		$tokens = array(
			'namespace' => self::_namespace($mockee),
			'mocker' => $mocker,
			'mockee' => 'MockDelegate',
			'static' => $isStatic ? 'static' : '',
		);
		$mockDelegate = self::_dynamicCode('mockDelegate', 'startClass', $tokens);
		$mock = self::_dynamicCode('mock', 'startClass', $tokens);

		$reflectedClass = new ReflectionClass($mocker);
		$reflecedMethods = $reflectedClass->getMethods();
		$getByReference = false;
		$staticApplyFilter = true;
		$constructor = false;
		foreach ($reflecedMethods as $methodId => $method) {
			if (!in_array($method->name, self::$_blackList)) {
				$key = $method->isStatic() ? 'staticMethod' : 'method';
				if ($method->name === '__construct') {
					$key = 'constructor';
					$constructor = true;
				}
				$docs = ReflectionMethod::export($mocker, $method->name, true);
				if (preg_match('/&' . $method->name . '/', $docs) === 1) {
					continue;
				}
				$tokens = array(
					'namespace' => self::_namespace($mockee),
					'method' => $method->name,
					'modifiers' => self::_methodModifiers($method),
					'args' => self::_methodParams($method),
					'stringArgs' => self::_stringMethodParams($method),
					'mocker' => $mocker,
				);
				$mockDelegate .= self::_dynamicCode('mockDelegate', $key, $tokens);
				$mock .= self::_dynamicCode('mock', $key, $tokens);
			} elseif ($method->name === '__get') {
				$docs = ReflectionMethod::export($mocker, '__get', true);
				$getByReference = preg_match('/&__get/', $docs) === 1;
			} elseif ($method->name === 'applyFilter') {
				$staticApplyFilter = $method->isStatic();
			}
		}

		if (!$constructor) {
			$tokens = array(
				'namespace' => self::_namespace($mockee),
				'modifiers' => 'public',
				'args' => null,
				'stringArgs' => 'array()',
				'mocker' => $mocker,
			);
			$mock .= self::_dynamicCode('mock', 'constructor', $tokens);
			$mockDelegate .= self::_dynamicCode('mockDelegate', 'constructor', $tokens);
		}

		$mockDelegate .= self::_dynamicCode('mockDelegate', 'endClass');
		$mock .= self::_dynamicCode('mock', 'get', array(
			'reference' => $getByReference ? '&' : '',
		));
		$mock .= self::_dynamicCode('mock', 'set');
		$mock .= self::_dynamicCode('mock', 'isset');
		$mock .= self::_dynamicCode('mock', 'unset');
		$mock .= self::_dynamicCode('mock', 'applyFilter', array(
			'static' => $staticApplyFilter ? 'static' : '',
		));
		$mock .= self::_dynamicCode('mock', 'destructor');
		$mock .= self::_dynamicCode('mock', 'endClass');

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
		return str_replace(array('private', 'protected'), 'public', $modifiers);
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
		$replace = array(
			'from' => array('Array', 'or NULL'),
			'to' => array('array()', ''),
		);
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
	protected static function _dynamicCode($type, $key, $tokens = array()) {
		$defaults = array(
			'master' => '\lithium\test\Mocker',
		);
		$tokens += $defaults;
		$name = '_' . $type . 'Ingredients';
		$code = implode("\n", self::${$name}[$key]);
		return String::insert($code, $tokens) . "\n";
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
		$matches = array();
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
		$results = array();
		$string = is_string($mock);
		if (is_object($mock) && isset($mock->results)) {
			$results = static::mergeResults($mock->results, $mock::$staticResults);
		} elseif ($string && class_exists($mock) && isset($mock::$staticResults)) {
			$results = $mock::$staticResults;
		} elseif ($string && function_exists($mock) && isset(static::$_functionResults[$mock])) {
			$results = array($mock => static::$_functionResults[$mock]);
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
	 * Apply a closure to a method of the current static object.
	 *
	 * @see lithium\core\StaticObject::_filter()
	 * @see lithium\util\collection\Filters
	 * @param string $class Fully namespaced class to apply filters.
	 * @param mixed $method The name of the method to apply the closure to. Can either be a single
	 *        method name as a string, or an array of method names. Can also be false to remove
	 *        all filters on the current object.
	 * @param Closure $filter The closure that is used to filter the method(s), can also be false
	 *        to remove all the current filters for the given method.
	 * @return void
	 */
	public static function applyFilter($class, $method = null, $filter = null) {
		if ($class === false) {
			return static::$_methodFilters = array();
		}
		if ($method === false) {
			return static::$_methodFilters[$class] = array();
		}
		foreach ((array) $method as $m) {
			if (!isset(static::$_methodFilters[$class][$m]) || $filter === false) {
				static::$_methodFilters[$class][$m] = array();
			}
			if ($filter !== false) {
				static::$_methodFilters[$class][$m][] = $filter;
			}
		}
	}

	/**
	 * Executes a set of filters against a method by taking a method's main implementation as a
	 * callback, and iteratively wrapping the filters around it.
	 *
	 * @see lithium\util\collection\Filters
	 * @param string $class Fully namespaced class to apply filters.
	 * @param string|array $method The name of the method being executed, or an array containing
	 *        the name of the class that defined the method, and the method name.
	 * @param array $params An associative array containing all the parameters passed into
	 *        the method.
	 * @param Closure $callback The method's implementation, wrapped in a closure.
	 * @param array $filters Additional filters to apply to the method for this call only.
	 * @return mixed
	 */
	protected static function _filter($class, $method, $params, $callback, $filters = array()) {
		$hasNoFilters = empty(static::$_methodFilters[$class][$method]);
		if ($hasNoFilters && !$filters && !Filters::hasApplied($class, $method)) {
			return $callback($class, $params, null);
		}
		if (!isset(static::$_methodFilters[$class][$method])) {
			static::$_methodFilters += array($class => array());
			static::$_methodFilters[$class][$method] = array();
		}
		$data = array_merge(static::$_methodFilters[$class][$method], $filters, array($callback));
		return Filters::run($class, $params, compact('data', 'class', 'method'));
	}

	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper for
	 * `forward_static_call_array()`.
	 *
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling `$method`.
	 * @return mixed Returns the result of the method call.
	 */
	public static function invokeMethod($method, $params = array()) {
		return forward_static_call_array(array(get_called_class(), $method), $params);
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
			static::$_functionResults = array();
			return static::$_functionCallbacks = array();
		}
		if ($callback === false) {
			static::$_functionResults[$name] = array();
			return static::$_functionCallbacks[$name] = false;
		}
		static::$_functionCallbacks[$name] = $callback;
		if (function_exists($name)) {
			return;
		}

		$function = new ReflectionFunction($callback);
		$pos = strrpos($name, '\\');
		eval(self::_dynamicCode('mockFunction', 'function', array(
			'namespace' => substr($name, 0, $pos),
			'function' => substr($name, $pos + 1),
			'args' => static::_methodParams($function),
			'stringArgs' => static::_stringMethodParams($function),
		)));
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
	public static function callFunction($name, array &$params = array()) {
		$function = substr($name, strrpos($name, '\\'));
		$exists = isset(static::$_functionCallbacks[$name]);
		if ($exists && is_callable(static::$_functionCallbacks[$name])) {
			$function = static::$_functionCallbacks[$name];
		}
		$result = call_user_func_array($function, $params);
		if (!isset(static::$_functionResults[$name])) {
			static::$_functionResults[$name] = array();
		}
		static::$_functionResults[$name][] = array(
			'args' => $params,
			'result' => $result,
			'time' => microtime(true),
		);
		return $result;
	}

}

?>