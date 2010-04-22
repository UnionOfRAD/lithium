<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

/**
 * The `Router` class uses an instance of `lithium\console\Request`, which represents an incoming
 * command-line invokation, to parse the correct command, and sub-command(s) and parameters, which
 * are used by `lithium\console\Dispatcher` to load and execute the proper `Command` class.
 */
class Router extends \lithium\core\Object {

	/**
	 * Parse incoming request from console
	 *
	 * @param object $request \lithium\console\Request
	 * @return array $params
	 *
	 **/
	public static function parse($request = null) {
		$params = array(
			'command' => null, 'action' => 'run', 'args' => array()
		);
		if (!empty($request->params)) {
			$params = $request->params + $params;
		}
		if (!empty($request->args)) {
			$args = $request->args;
			if (empty($params['command'])) {
				$params['command'] = array_shift($args);
			}
			while ($arg = array_shift($args)) {

				if (preg_match('/^-(?P<key>[a-zA-Z0-9]+)$/i', $arg, $match)) {
					$params[$match['key']] = true;
					continue;
				}
				if (preg_match('/^--(?P<key>[a-z0-9-]+)(?:=(?P<val>.+))?$/i', $arg, $match)) {
					$params[$match['key']] = !isset($match['val']) ? true : $match['val'];
					continue;
				}
				$params['args'][] = $arg;
			}
		}

		if (!empty($params['args'])) {
			$params['action'] = array_shift($params['args']);
		}
		return $params;
	}
}

?>