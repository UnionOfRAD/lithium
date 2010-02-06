<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;

class Console extends \lithium\test\Reporter {

	/**
	 * undocumented function
	 *
	 * @param array $stats
	 * @return string
	 */
	protected function _result($stats) {
		$success = $stats['fails'] == 0 && $stats['exceptions'] == 0;
		$result = array(
			($success ? '{:success}' : '') . "{$stats['passes']} / {$stats['asserts']} passes",
			"{$stats['fails']} " . ((intval($stats['fails']) == 1) ? 'fail' : 'fails') .
			" and {$stats['exceptions']} " .
			((intval($stats['exceptions']) == 1) ? 'exceptions' : 'exceptions') .
			($success ? '{:end}' : ''),
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
			"{:error}Assertion '{$error['assertion']}' failed in",
			"{$error['class']}::{$error['method']}()",
			"on line {$error['line']}:{:end}",
			"\n{$error['message']}",
		);
		return join(" ", $fail);
	}

	/**
	 * undocumented function
	 *
	 * @param array $error
	 * @return string
	 */
	protected function _exception($error) {
		$exception = array(
			"{:error}Exception thrown in
			{$error['class']}::{$error['method']}() on line {$error['line']}:{:end}",
			"{$error['message']}",
		);
		if (isset($error['trace']) && !empty($error['trace'])) {
			$exception[] = "Trace: {$error['trace']}";
		}
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
		foreach ((array) $filters as $class => $data) {
			$result[] = $class::output('text', $data);
		}
		$output = array();
		foreach ($result as $level) {
			if (is_array($level)) {
				foreach ($level as $title => $value) {
					if (is_array($value)) {
						$output[] = "{$value['title']}: {$value['value']}";
					} else {
						$output[] = "{$title}: {$value}";
					}
				}
			}

		}
		return join("\n", $output);
	}

	/**
	 * undocumented function
	 *
	 * @param string $type
	 * @param string $params
	 * @return void
	 */
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