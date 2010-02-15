<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;

class Html extends \lithium\test\Reporter {

	protected $_classes = array(
		'router' => '\lithium\net\http\Router'
	);

	protected function _result($stats) {
		$class = ($stats['success'] ? 'success' : 'fail');
		$result = array(
			"<div class=\"test-result test-result-{$class}\">",
			"{$stats['passes']} / {$stats['asserts']} passes, {$stats['fails']} ",
			((intval($stats['fails']) == 1) ? 'fail' : 'fails') . " and {$stats['exceptions']} ",
			((intval($stats['exceptions']) == 1) ? 'exception' : 'exceptions'),
			'</div>'
		);
		return join("", $result);
	}

	protected function _fail($error) {
		$fail = array(
			'<div class="test-assert test-assert-failed">',
			"Assertion '{$error['assertion']}' failed in ",
			"{$error['class']}::{$error['method']}() on line ",
			"{$error['line']}: ",
			"<span class=\"content\">{$error['message']}</span>",
			'</div>'
		);
		return join("", $fail);
	}

	protected function _exception($error) {
		$exception = array(
			'<div class="test-exception">',
			"Exception thrown in {$error['class']}::{$error['method']}() ",
			"on line {$error['line']}: ",
			"<span class=\"content\">{$error['message']}</span>",
		);
		if (isset($error['trace']) && !empty($error['trace'])) {
			$exception[] = "Trace: <span class=\"trace\">{$error['trace']}</span>";
		}
		$exception[] = '</div>';
		return join("", $exception);
	}

	protected function _skip($skip) {
		$result = array(
			'<div class="test-skip">',
			"Skip {$skip['trace'][1]['class']}::{$skip['trace'][1]['function']}() ",
			"on line {$skip['trace'][1]['line']}: ",
			"<span class=\"content\">{$skip['message']}</span>",
			"</div>"
		);
		return join("", $result);
	}

	public function filters($filters) {
		$result = array();
		foreach ((array) $filters as $class => $data) {
			$result[] = $class::output('html', $data);
		}
		return join("\n", $result);
	}

	/**
	 * Renders a menu item.
	 *
	 * @param string $type group, case or null.
	 * @param string $options
	 *               - request: a request object
	 *               - namespace: namespace for test case
	 *               - name: test case class name
	 *               - menu: current menu string for recursive construction
	 * @return void
	 */
	protected function _item($type, $options = array()) {
		$defaults = array('request' => null, 'namespace' => null, 'name' => null, 'menu' => null);
		$options += $defaults;
		$router = $this->_classes['router'];
		extract($options);

		$url = array('controller' => '\lithium\test\Controller');

		if ($type == 'group') {
			$url = $router::match($url + array('args' => $namespace), $request);
			return "<li><a href=\"{$url}\">{$name}</a>{$menu}</li>";
		}

		if ($type == 'case') {
			$args = array('args' => "{$namespace}/{$name}");
			$url = $router::match($url + $args, $request);
			return "<li><a href=\"{$url}\">{$name}</a></li>";
		}
		return "<ul>{$menu}</ul>";
	}
}

?>