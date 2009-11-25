<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;

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
	 *               - namespace
	 *               - name
	 *               - menu
	 * @return void
	 */
	protected function _item($type, $params = array()) {
		$defaults = array(
			'namespace' => null, 'name' => null, 'menu' => null
		);
		$params += $defaults;
		if ($type == 'group') {
			return '<li><a href="?'. String::insert(
				'group={:namespace}">{:name}</a>{:menu}</li>', $params
			);
		}
		if ($type == 'case') {
			return '<li><a href="?'. String::insert(
				'case={:namespace}\{:name}">{:name}</a></li>', $params
			);
		}
		return String::insert('<ul>{:menu}</ul>', $params);
	}
}

?>