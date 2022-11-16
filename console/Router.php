<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console;

use lithium\util\Inflector;

/**
 * The `Router` class uses an instance of `lithium\console\Request`, which represents an incoming
 * command-line invocation, to parse the correct command, and sub-command(s) and parameters, which
 * are used by `lithium\console\Dispatcher` to load and execute the proper `Command` class.
 */
class Router {

	/**
	 * Parse incoming request from console. Short and long (GNU-style) options
	 * in the form of `-f`, `--foo`, `--foo-bar` and `--foo=bar` are parsed.
	 * XF68-style long options (i.e. `-foo`) are not supported but support
	 * can be added by extending this class.
	 *
	 * Long options like `--foo-bar` are camelized and made available
	 * after parsing as `fooBar`.
	 *
	 * @param \lithium\console\Request $request
	 * @return array $params
	 */
	public static function parse($request = null) {
		$defaults = ['command' => null, 'action' => 'run', 'args' => []];
		$params = $request ? (array) $request->params + $defaults : $defaults;

		if (!empty($request->argv)) {
			$args = $request->argv;

			while ($args) {
				$arg = array_shift($args);
				if (preg_match('/^-(?P<key>[a-zA-Z0-9])$/i', $arg, $match)) {
					$key = Inflector::camelize($match['key'], false);
					$params[$key] = true;
					continue;
				}
				if (preg_match('/^--(?P<key>[a-z0-9-]+)(?:=(?P<val>.+))?$/i', $arg, $match)) {
					$key = Inflector::camelize($match['key'], false);
					$params[$key] = !isset($match['val']) ? true : $match['val'];
					continue;
				}
				$params['args'][] = $arg;
			}
		}
		foreach (['command', 'action'] as $param) {
			if (!empty($params['args'])) {
				$params[$param] = array_shift($params['args']);
			}
		}
		return $params;
	}
}

?>