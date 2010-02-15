<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;

class Text extends \lithium\test\Reporter {

	protected function _result($stats) {
		$result = array(
			"{$stats['passes']} / {$stats['asserts']} passes",
			"{$stats['fails']} " . ((intval($stats['fails']) == 1) ? 'fail' : 'fails') .
			" and {$stats['exceptions']} " .
			((intval($stats['exceptions']) == 1) ? 'exceptions' : 'exceptions'),
		);
		return join("\n", $result);
	}

	protected function _fail($error) {
		$fail = array(
			"Assertion `{$error['assertion']}` failed in",
			"`{$error['class']}::{$error['method']}()`",
			"on line {$error['line']}:",
			"\n{$error['message']}",
		);
		return join(" ", $fail);
	}

	protected function _exception($error) {
		$exception = array(
			"Exception thrown in `{$error['class']}::{$error['method']}()` on line {$error['line']}:",
			"{$error['message']}",
		);
		if (isset($error['trace']) && !empty($error['trace'])) {
			$exception[] = "Trace: {$error['trace']}";
		}
		return join("\n", $exception);
	}

	protected function _skip($skip) {
		$result = array(
			"Skip {$skip['trace'][1]['class']}::{$skip['trace'][1]['function']}() ",
			"on line {$skip['trace'][1]['line']}:\n",
			"{$skip['message']}",
		);
		return join("", $result);
	}

	public function filters($filters) {
		$output = array();

		foreach ((array) $filters as $class => $data) {
			$output[] = $class::output('text', $data);
		}
		return join("\n", $output);
	}

	public function _item($type, $params = array()) {
		$defaults = array(
			'namespace' => null, 'name' => null, 'menu' => null
		);
		$params += $defaults;
		$params['namespace'] = str_replace('/', '.', $params['namespace']);

		if ($type == 'group') {
			return String::insert(
				"-group {:namespace}\n{:menu}\n", $params
			);
		}
		if ($type == 'case') {
			return String::insert("-case {:namespace}.{:name}\n", $params);
		}
		return String::insert("\n{:menu}\n", $params);
	}
}

?>