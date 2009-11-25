<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;
use lithium\http\Router;

/**
 * Html Reporter
 *
 */
class Html extends \lithium\test\Reporter {

	/**
	 * undocumented function
	 *
	 * @param array $stats
	 * @return string
	 */
	protected function _result($stats) {
		$class = ($stats['success'] ? 'success' : 'fail');
		$result = array(
			"<div class=\"test-result test-result-{$class}\">",
			"{$stats['passes']} / {$stats['asserts']} passes, {$stats['fails']} ",
			((intval($stats['fails']) == 1) ? 'fail' : 'fails') . " and {$stats['exceptions']} ",
			((intval($stats['exceptions']) == 1) ? 'exceptions' : 'exceptions'),
			'</div>'
		);
		return join("\n", $result);
	}

	/**
	 * undocumented function
	 *
	 * @param array $error
	 * @return string
	 */
	protected function _fail($error) {
		$fail = array(
			'<div class="test-assert test-assert-failed">',
			"Assertion '{$error['assertion']}' failed in ",
			"{$error['class']}::{$error['method']}() on line ",
			"{$error['line']}: ",
			"<span class=\"content\">{$error['message']}</span>",
			'</div>'
		);
		return join("\n", $fail);
	}

	/**
	 * undocumented function
	 *
	 * @param array $error
	 * @return string
	 */
	protected function _exception($error) {
		$exception = array(
			'<div class="test-exception">',
			"Exception thrown in  {$error['class']}::{$error['method']}() ",
			"on line {$error['line']}: ",
			"<span class=\"content\">{$error['message']}</span>",
		);
		if (isset($error['trace']) && !empty($error['trace'])) {
			$exception[] = "Trace:<span class=\"trace\">{$error['trace']}</span>";
		}
		$exception[] = '</div>';
		return join("\n", $exception);
	}

	/**
	 * undocumented function
	 *
	 * @param string $filters
	 * @return void
	 */
	public function filters($filters) {
		$result = array();
		foreach ((array)$filters as $class => $data) {
			$result[] = $class::output('html', $data);
		}
		return join("\n", $result);
	}

	/**
	 * Renders a menu item
	 *
	 * @param string $type group, case or null
	 * @param string $params
	 *               - request: a request object
	 *               - namespace: namespace for test case
	 *               - name: test case class name
	 *               - menu: current menu string for recursive construction
	 * @return void
	 */
	protected function _item($type, $params = array()) {
		$defaults = array(
			'request' => null, 'namespace' => null, 'name' => null, 'menu' => null
		);
		$params += $defaults;
		extract($params);

		$controller = array('controller' => '\lithium\test\Controller');

		if ($type == 'group') {
			$url = Router::match($controller + array('args' => $namespace) ?: '', $request);
			return "<li><a href=\"{$url}\">{$name}</a>{$menu}</li>";
		}

		if ($type == 'case') {
			$url = Router::match($controller + array('args' =>"{$namespace}/{$name}") ?: '', $request);
			return "<li><a href=\"{$url}\">{$name}</a></li>";
		}
		return "<ul>{$menu}</ul>";
	}
}

?>