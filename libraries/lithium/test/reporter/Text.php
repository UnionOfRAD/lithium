<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\reporter;

use lithium\util\String;

class Text extends \lithium\core\Object {

	public function render($results) {
		$filters = Libraries::locate('test.filters');
	}

	public function format($type, $params = array()) {
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