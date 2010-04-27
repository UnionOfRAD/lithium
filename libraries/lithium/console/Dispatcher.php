<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

use \lithium\util\String;
use \lithium\core\Libraries;
use \lithium\util\Inflector;
use \UnexpectedValueException;

/**
 * The console dispatcher is responsible for accepting requests from scripts called from the command
 * line, and executing the appropriate `Command` class(es). The `run()` method accepts an instance
 * of `lithium\console\Request`, which encapsulates the console environment and any command-line
 * parameters passed to the script. `Dispatcher` then invokes `lithium\console\Router` to determine
 * the correct `Command` class to invoke, and which method should be called.
 */
class Dispatcher extends \lithium\core\StaticObject {

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
	 * `array('action' => 'admin_{:action}')`.  See `lithium\console\Dispatcher::config()` for
	 * examples on setting rules.
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
		if (!$config) {
			return array('rules' => static::$_rules);
		}
		foreach ($config as $key => $val) {
			if (isset(static::${'_' . $key})) {
				static::${'_' . $key} = $val + static::${'_' . $key};
			}
		}
	}

	/**
	 * Dispatches a request based on a request object (an instance of `lithium\console\Request`).
	 *  If `$request` is `null`, a new request object is instantiated based on the value of the
	 * `'request'` key in the `$_classes` array.
	 *
	 * @param object $request An instance of a request object with HTTP request information.  If
	 *        `null`, an instance will be created.
	 * @param array $options
	 * @return object The command action result which is an instance of `lithium\console\Response`.
	 * @todo Add exception-handling/error page rendering
	 */
	public static function run($request = null, $options = array()) {
		$defaults = array('request' => array());
		$options += $defaults;
		$classes = static::$_classes;
		$params = compact('request', 'options');
		$method = __FUNCTION__;

		return static::_filter($method, $params, function($self, $params, $chain) use ($classes) {
			extract($params);

			$router = $classes['router'];
			$request = $request ?: new $classes['request']($options['request']);
			$request->params = $router::parse($request);
			$params = $request->params;

			try {
				$callable = $self::invokeMethod('_callable', array($request, $params, $options));
				return $self::invokeMethod('_call', array($callable, $request, $params));
			} catch (UnexpectedValueException $e) {
				return (object) array('status' => $e->getMessage() . "\n");
			}
		});
	}

	/**
	 * Determines Command to use for current request. If
	 *
	 * @param string $request
	 * @param string $params
	 * @param string $options
	 * @return class \lithium\console\COmmand
	 */
	protected static function _callable($request, $params, $options) {
		$params = compact('request', 'params', 'options');
		return static::_filter(__FUNCTION__, $params, function($self, $params, $chain) {
			extract($params, EXTR_OVERWRITE);
			$name = $class = $params['command'];

			if (!$name) {
				$request->params['args'][0] = $name;
				$name = $class = '\lithium\console\command\Help';
			}
			if ($class[0] !== '\\') {
				$name = Inflector::camelize($class);
				$class = Libraries::locate('command', $name);
			}

			if (class_exists($class)) {
				return new $class(compact('request'));
			}
			throw new UnexpectedValueException("Command `{$name}` not found");
		});
	}

	/**
	 * Call class method
	 *
	 * @param string $callable
	 * @param string $request
	 * @param string $params
	 * @return void
	 */
	protected static function _call($callable, $request, $params) {
		$params = compact('callable', 'request', 'params');
		return static::_filter(__FUNCTION__, $params, function($self, $params, $chain) {
			if (is_callable($callable = $params['callable'])) {
				$request = $params['request'];

				if (!method_exists($callable, $request->params['action'])) {
					array_unshift($request->params['args'], $request->params['action']);
					$request->params['action'] = 'run';
				}
				$isHelp = (
					!empty($request->params['help']) || !empty($request->params['h'])
					|| !method_exists($callable, $request->params['action'])
				);
				if ($isHelp) {

					$request->params['action'] = '_help';
				}
				return $callable($request->params['action'], $request->params['args']);
			}
			throw new UnexpectedValueException("{$callable} not callable");
		});
	}
}

?>