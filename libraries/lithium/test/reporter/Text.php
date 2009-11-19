<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;

class Text extends \lithium\test\Reporter {

	/**
	 * undocumented function
	 *
	 * @param array $stats
	 * @return string
	 */
	protected function _result($stats) {
		$result = array(
			"{$stats['passes']} / {$stats['asserts']} passes",
			"{$stats['fails']} " . ((intval($stats['fails']) == 1) ? 'fail' : 'fails') .
			" and {$stats['exceptions']} " .
			((intval($stats['exceptions']) == 1) ? 'exceptions' : 'exceptions'),
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
			"Assertion '{$error['assertion']}' failed in ",
			"{$error['class']}::{$error['method']}() on line ",
			"{$error['line']}: ",
			"{$error['message']}",
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
			"Exception thrown in  {$error['class']}::{$error['method']}() ",
			"on line {$error['line']}: ",
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
		foreach ((array)$filters as $class => $data) {
			$result[] = $class::output('text', $data);
		}
		return join("\n", $result);
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
		if ($type == 'group') {
			return String::insert(
				"-group {:namespace}\n{:menu}\n", $params
			);
		}
		if ($type == 'case') {
			return String::insert("-case {:namespace}\{:name}\n", $params);
		}
		return String::insert("\n{:menu}\n", $params);
	}
}

?>