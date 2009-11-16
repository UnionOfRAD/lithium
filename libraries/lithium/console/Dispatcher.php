<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

use \UnexpectedValueException;
use \lithium\core\Libraries;
use \lithium\util\String;
use \lithium\util\Inflector;

class Dispatcher extends \lithium\core\Object {

	/**
	 * Fully-namespaced router class reference.  Class must implement a `parse()` method,
	 * which must return an array with (at a minimum) 'command' and 'action' keys.
	 *
	 * @see lithium\console\Router::parse()
	 * @var array
	 */
	protected static $_classes = array(
		'request' => '\lithium\console\Request',
		'router' => '\lithium\console\Router'
	);

	/**
	 * Contains pre-process format strings for changing Dispatcher's behavior based on 'rules'.
	 * Each key in the array represents a 'rule'; if a key that matches the rule is present (and
	 * not empty) in a route, (i.e. the result of `lithium\console\Router::parse()`) then the rule's
	 * value will be applied to the route before it is dispatched.  When applying a rule, any array
	 * elements array elements of the flag which are present in the route will be modified using a
	 * `lithium\util\String::insert()`-formatted string.
	 *
	 * For example, to implement action prefixes (i.e. `admin_index()`), set a rule named 'admin',
	 * with a value array containing a modifier key for the `action` element of a route, i.e.:
	 * `array('action' => 'admin_{:action}')`.  See `lithium\console\Dispatcher::config()` for examples
	 * on setting rules.
	 *
	 * @see lithium\console\Dispatcher::config()
	 * @see lithium\util\String::insert()
	 */
	protected static $_rules = array(
		//'plugin' => array('command' => '{:plugin}.{:command}')
	);

	/**
	 * Used to set configuration parameters for the Dispatcher.
	 *
	 * @param array $config
	 * @return array|void If no parameters are passed, returns an associative array with the
	 *         current configuration, otherwise returns null.
	 */
	public static function config($config = array()) {
		if (empty($config)) {
			return array('rules' => static::$_rules);
		}

		foreach ($config as $key => $val) {
			if (isset(static::${'_' . $key})) {
				static::${'_' . $key} = $val + static::${'_' . $key};
			}
		}
	}

	/**
	 * Dispatches a request based on a request object (an instance of `lithium\console\Request`).  If
	 * `$request` is null, a new request object is instantiated based on the value of the
	 * `'request'` key in the `$_classes` array.
	 *
	 * @param object $request An instance of a request object with HTTP request information.  If
	 *        null, an instance will be created.
	 * @param array $options
	 * @return object
	 * @todo Add exception-handling/error page rendering
	 */
	public static function run($request = null, $options = array()) {
		$defaults = array();
		$options += $defaults;

		if (empty($request)) {
			$request = new static::$_classes['request']($options);
		}
		$router = static::$_classes['router'];
		$request->params = static::_applyRules($router::parse($request));
		$class = $request->params['command'] ?: '\lithium\console\Command';

		if ($class[0] !== '\\') {
			$class = Libraries::locate('commands', Inflector::camelize($class));
		}

		$isRun = (
			$request->params['action'] != 'run'
			&& !method_exists($class, $request->params['action'])
		);

		if ($isRun) {
			array_unshift($request->params['passed'], $request->params['action']);
			$request->params['action'] = 'run';
		}

		if (!class_exists($class)) {
			throw new UnexpectedValueException("Command $class not found");
		}

		$command = new $class(compact('request'));
		return $command($request->params['action'], $request->params['passed']);
	}

	/**
	 * Attempts to apply a set of formatting rules from `$_rules` to a `$params` array, where each
	 * formatting rule is applied if the key of the rule in `$_rules` is present and not empty in
	 * `$params`.  Also performs sanity checking against `$params` to ensure that no value
	 * matching a rule is present unless the rule check passes.
	 *
	 * @param array $params An array of route parameters to which rules will be applied.
	 * @return array Returns the $params array with formatting rules applied to array values.
	 */
	protected static function _applyRules($params) {
		foreach (static::$_rules as $rule => $value) {
			foreach ($value as $k => $v) {
				if (!empty($params[$rule])) {
					$params[$k] = String::insert($v, $params);
				}

				$match = preg_replace('/\{:\w+\}/', '@', $v);
				$match = preg_replace('/@/', '.+', preg_quote($match, '/'));

				if (preg_match('/' . $match . '/i', $params[$k])) {
					return false;
				}
			}
		}
		return $params;
	}
}

?>