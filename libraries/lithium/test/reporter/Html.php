<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;

class Html extends \lithium\core\Object {

	public function render($results) {
		$filters = Libraries::locate('test.filters');
	}

	public function format($type, $params = array()) {
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