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
class Html extends \lithium\core\Object {
	
	/**
	 * undocumented function
	 *
	 * @param object $report \lithium\test\Report
	 * @return void
	 */
	public function stats($stats) {
		$passes = count($stats['passes']);
		$fails = count($stats['fails']);
		$errors = count($stats['errors']);
		$exceptions = count($stats['exceptions']);
		$success = ($passes === $stats['asserts'] && $errors === 0);

		$result = array(
			'<div class="test-result test-result-' . ($success ? 'success' : 'fail') . '">',
			"{$passes} / {$stats['asserts']} passes, {$fails} ",
			((intval($stats['fails']) == 1) ? 'fail' : 'fails') . " and {$exceptions} ",
			((intval($exceptions) == 1) ? 'exceptions' : 'exceptions'),
			'</div>'
		);

		foreach ((array)$stats['errors'] as $error) {
			switch ($error['result']) {
				case 'fail':
					$error += array('class' => 'unknown', 'method' => 'unknown');
					$fail = array(
						'<div class="test-assert test-assert-failed">',
						"Assertion '{$error['assertion']}' failed in ",
						"{$error['class']}::{$error['method']}() on line ",
						"{$error['line']}: ",
						"<span class=\"content\">{$error['message']}</span>",
						'</div>'
					);
					$result[] = join("\n", $fail);
				break;
				case 'exception':
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
					$result[] = join("\n", $exception);
				break;
			}
		}
		return join("\n", $result);
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
	public function menu($type, $params = array()) {
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