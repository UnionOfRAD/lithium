<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use \lithium\core\Libraries;
use \lithium\analysis\Inspector;

/**
 * Adds headers and docblocks to classes and methods.
 */
class FindDeps extends \lithium\console\Command {

	public function run() {
		$classes = Libraries::find('lithium', array('recursive' => true, 'exclude' => '/Test$/'));
		$dependencies = array();

		foreach ($classes as $class) {
			list(, $ns) = explode('\\', $class);
			if ($depends = Inspector::dependencies($class)) {
				foreach ($depends as $d) {
					list(, $dependNs) = explode('\\', $d);
					$dependencies[$ns][] = $dependNs;

					// if ($dependNs == 'http') {
					// 	$this->out("{$class} : {$d}");
					// }
				}
				$dependencies[$ns] = array_filter(array_unique($dependencies[$ns]));
			}
		}

		foreach ($dependencies as $ns => $list) {
			if ($ns == 'tests') { // || $ns == 'core'
				continue;
			}
			$this->out("{$ns}");

			foreach ($list as $d) {
				$this->out(" - {$d}");
			}
		}
		echo "\n\n";
	}

	public function help() {
	}
}

?>