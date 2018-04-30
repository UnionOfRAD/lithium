<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */
namespace lithium\console\command;

use lithium\core\Libraries;
use lithium\action\Request;
use lithium\net\http\Router;
use lithium\core\Environment;

/**
 * The route command lets you inspect your routes and issue requests against the router.
 */
class Route extends \lithium\console\Command {

	/**
	 * Override the default 'development' environment.
	 *
	 * For example:
	 * ```sh
	 * li3 route --env=production
	 * li3 route show /foo --env=test
	 * ```
	 *
	 * @var string
	 */
	public $env = 'development';

	/**
	 * Constructor. Load the routes file and set the environment.
	 *
	 * @see lithium\console\Command
	 * @param array $config For available options, see parent class.
	 *        Additionally the absolute path to the routes file to load
	 *        may be specified, using the `'routes'` key.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['routes' => Libraries::get(true, 'path') . '/config/routes.php'];
		parent::__construct($config + $defaults);

		Environment::set($this->env);

		if (file_exists($this->_config['routes'])) {
			return require $this->_config['routes'];
		}
		$this->error("The routes file for this library doesn't exist or can't be found.");
	}

	/**
	 * Lists all connected routes to the router. See the `all()`
	 * method for details and examples.
	 *
	 * @return void
	 */
	public function run() {
		$this->all();
	}

	/**
	 * Lists all connected routes to the router. This is a convenience
	 * alias for the `show()` method.
	 *
	 * Example:
	 * ```sh
	 * li3 route
	 * li3 route all
	 * ```
	 *
	 * Will return an output similar to:
	 *
	 * ```
	 * Template                        	Params
	 * --------                        	------
	 * /                               	{"controller":"pages","action":"view"}
	 * /pages/{:args}                  	{"controller":"pages","action":"view"}
	 * /{:slug:[\w\-]+}                	{"controller":"posts","action":"show"}
	 * /{:controller}/{:action}/{:args}	{"action":"index"}
	 * ```
	 *
	 * @return void
	 */
	public function all($scope = true) {
		$routes = Router::get(null, true);
		$columns = [['Template', 'Params'], ['--------', '------']];

		foreach ($routes As $route) {
			$info = $route->export();
			$columns[] = [$info['template'], json_encode($info['params'])];
		}
		$this->columns($columns);
	}

	/**
	 * Returns the corresponding params for a given URL and an optional request
	 * method.
	 *
	 * Examples:
	 * ```
	 * 1: li3 route show /foo
	 * 2: li3 route show post /foo/bar/1
	 * 3: li3 route show /test
	 * 4: li3 route show /test --env=production
	 * ```
	 *
	 * Will return outputs similar to:
	 *
	 * ```
	 * 1: {"controller":"foo","action":"index"	}
	 * 2: {"controller":"foo","action":"bar","args":["1"]}
	 * 3: {"controller":"lithium\\test\\Controller","action":"index"}
	 * 4: {"controller":"test","action":"index"}
	 * ```
	 *
	 * @return void
	 */
	public function show() {
		$url = join(" ", $this->request->params['args']);
		$method = 'GET';

		if (!$url) {
			$this->error('Please provide a valid URL');
		}

		if (preg_match('/^(GET|POST|PUT|DELETE|HEAD|OPTIONS) (.+)/i', $url, $matches)) {
			$method = strtoupper($matches[1]);
			$url = $matches[2];
		}

		$request = new Request(compact('url') + ['env' => ['REQUEST_METHOD' => $method]]);
		$result = Router::process($request);
		$this->out($result->params ? json_encode($result->params) : "No route found.");
	}
}

?>