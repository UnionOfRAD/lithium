<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\console;

/**
 * Router parses incoming request
 *
 *
 **/
class Router extends \lithium\core\Object {

	/**
	 * Parse incoming request from console
	 *
	 * @param object $request \lithium\console\Request
	 * @return array command, passed, named
	 *
	 **/
	public static function parse($request = null) {
		$params = array(
			'command' => null, 'action' => 'run',
			'passed' => array(), 'named' => array()
		);

		if (!empty($request->params)) {
			$params = $request->params + $params;
		}

		if (!empty($request->args)) {
			$args = $request->args;
			if (!isset($request->params['command'])) {
				$params['command'] = array_shift($args);
			}

			while ($arg = array_shift($args)) {
				if (preg_match('/^-(?P<key>[a-zA-Z0-9]+)$/', $arg, $match)) {
					$arg = array_shift($args);
					$params['named'][$match['key']] = $arg;
					continue;
				}

				if (preg_match('/^--(?P<key>[a-z0-9-]+)(?:=(?P<val>.+))?$/', $arg, $match)) {
					array_unshift($args, $arg);
					$params['named'][$match['key']] = (!isset($match['val'])) ? true : $match['val'];
					break;
				}
				$params['passed'][] = $arg;
			}
		}

		if (!empty($params['passed'][0])) {
			$params['action'] = $params['passed'][0];
			unset($params['passed'][0]);
			$params['passed'] = array_values($params['passed']);
		}
		return $params;
	}
}

?>