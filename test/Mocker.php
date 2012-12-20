<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use lithium\util\String;
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
 * if(!Environment::is('production')) {
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
	 * A list of code to be generated based on the type.
	 *
	 * @var array
	 */
	protected static $_dynamicCode = array(
		'startClass' => array(
			'namespace {:namespace};',
			'class {:mockee} extends \{:mocker} {'
		),
		'staticMethod' => array(
			'{:modifiers} function {:name}({:params}) {',
			'    $params = func_get_args();',
			'    list($class, $method) = explode(\'::\', __METHOD__, 2);',
			'    $parent = \'parent::\' . $method;',
			'    $result = call_user_func_array($parent, $params);',
			'    return self::_filter($method, $params, function($self, $params) use(&$result) {',
			'       return $result;',
			'    });',
			'}',
		),
		'method' => array(
			'{:modifiers} function {:name}({:params}) {',
			'    $params = func_get_args();',
			'    list($class, $method) = explode(\'::\', __METHOD__, 2);',
			'    $parent = \'parent::\' . $method;',
			'    $result = call_user_func_array($parent, $params);',
			'    return $this->_filter($parent, $params, function($self, $params) use(&$result) {',
			'        return $result;',
			'    });',
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
		'__construct', '__destruct', '__call', '__callStatic',
		'__get', '__set', '__isset', '__unset', '__sleep',
		'__wakeup', '__toString', '__clone', '__invoke',
		'__construct', '_init', 'applyFilter', 'invokeMethod',
		'__set_state', '_instance', '_filter', '_parents',
		'_stop',
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

		$code = self::_dynamicCode('startClass', array(
			'namespace' => self::_namespace($mockee),
			'mocker' => $mocker,
			'mockee' => 'Mock',
		));

		$reflectedClass = new ReflectionClass($mocker);
		$reflecedMethods = $reflectedClass->getMethods();
		foreach ($reflecedMethods as $method) {
			if (!in_array($method->name, self::$_blackList)) {
				$key = $method->isStatic() ? 'staticMethod' : 'method';
				$code .= self::_dynamicCode($key, array(
					'name' => $method->name,
					'modifiers' => self::_methodModifiers($method),
					'params' => self::_methodParams($method),
					'visibility' => 'public',
				));
			}
		}

		$code .= self::_dynamicCode('endClass');

		eval($code);
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
		return implode(' ', $modifierArray);
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
	 * Will generate the code you are wanting.
	 *
	 * @param  string $key    The key from self::$_dynamicCode
	 * @param  array  $tokens Tokens, if any, that should be inserted
	 * @return string
	 */
	protected static function _dynamicCode($key, $tokens = array()) {
		$code = implode("\n", self::$_dynamicCode[$key]);
		return String::insert($code, $tokens) . "\n";
	}

	/**
	 * Will generate the mocker from the current mockee.
	 *
	 * @param  string $mockee The fully namespaced `\Mock` class
	 * @return array
	 */
	protected static function _mocker($mockee) {
		$matches = array();
		preg_match_all('/^(.*)\\\\([^\\\\]+)\\\\Mock$/', $mockee, $matches);
		if (!isset($matches[1][0])) {
			return;
		}
		return $matches[1][0] . '\\' . ucfirst($matches[2][0]);
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
	 * @param  string $mockee The fully namespaced `\Mock` class
	 * @return bool
	 */
	protected static function _validateMockee($mockee) {
		if (class_exists($mockee) || preg_match('/\\\\Mock$/', $mockee) !== 1) {
			return false;
		}
		$mocker = self::_mocker($mockee);
		$isObject = is_subclass_of($mocker, 'lithium\core\Object');
		$isStatic = is_subclass_of($mocker, 'lithium\core\StaticObject');
		if (!$isObject && !$isStatic) {
			return false;
		}
		return true;
	}

}

?>