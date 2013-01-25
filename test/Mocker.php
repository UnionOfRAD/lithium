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
use Reflection;

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
 * 	Mocker::register();
 * }
 * }}}
 *
 * You can also enable autoloading inside the setup of a unit test class. This
 * method can be called redundantly.
 * {{{
 * use lithium\test\Mocker;
 * class MockerTest extends \lithium\test\Unit {
 * 	public function setUp() {
 * 		Mocker::register();
 * 	}
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
 * 	return array();
 * });
 * $tokens = ParserMock::tokenize($code, array('wrap' => true));
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
			'class MockDelegate extends \{:mocker} {'
		),
		'constructor' => array(
			'{:modifiers} function __construct({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $this->parent = func_get_arg(count(func_get_args()) - 1);',
			'    $this->parent->mocker = $this;',
			'    if (method_exists("{:mocker}", "__construct")) {',
			'        call_user_func_array("parent::__construct", $args);',
			'    }',
			'}',
		),
		'method' => array(
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $token = spl_object_hash($this);',
			'    if (func_get_arg(count(func_get_args()) - 1) !== $token) {',
			'        $method = array($this->parent, "{:method}");',
			'        return call_user_func_array($method, $args);',
			'    }',
			'    return call_user_func_array("parent::{:method}", compact({:stringArgs}));',
			'}',
		),
		'staticMethod' => array(
			'{:modifiers} function {:method}({:args}) {',
			'    $args = compact({:stringArgs});',
			'    $token = "1f3870be274f6c49b3e31a0c6728957f";',
			'    $id = func_get_arg(count(func_get_args()) - 1);',
			'    if (func_get_arg(count(func_get_args()) - 1) !== $token) {',
			'        $method = \'{:namespace}\Mock::{:method}\';',
			'        return call_user_func_array($method, $args);',
			'    }',
			'    return call_user_func_array("parent::{:method}", compact({:stringArgs}));',
			'}',
		),
		'endClass' => array(
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
		'constructor' => array(
			'{:modifiers} function __construct({:args}) {',
			'    $args = compact({:stringArgs});',
			'    array_push($args, $this);',
			'    foreach ($this as $key => $value) {',
			'        if (!in_array($key, $this->_safeVars)) {',
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
		'__destruct', '__call', '__callStatic', '_parents',
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
	 * For instance: 'ReflectionMethod $method' or '$name, array $foo = null'
	 *
	 * @param  ReflectionMethod $method
	 * @return string
	 */
	protected static function _methodParams(ReflectionMethod $method) {
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
	 * @param  ReflectionMethod $method
	 * @return string
	 */
	protected static function _stringMethodParams(ReflectionMethod $method) {
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
	 * @param  object $mock Mock to chain
	 * @return object       MockerChain instance
	 */
	public static function chain($mock) {
		$results = array();
		if (is_object($mock) && isset($mock->results)) {
			$results = static::mergeResults($mock->results, $mock::$staticResults);
		} elseif (is_string($mock) && class_exists($mock) && isset($mock::$staticResults)) {
			$results = $mock::$staticResults;
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
	 * @param closure $filter The closure that is used to filter the method(s), can also be false
	 *        to remove all the current filters for the given method.
	 * @return void
	 */
	public static function applyFilter($class, $method, $filter = null) {
		if ($method === false) {
			static::$_methodFilters[$class] = array();
			return;
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

}

?>