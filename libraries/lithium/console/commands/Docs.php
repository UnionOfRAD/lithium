<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\commands;

use \lithium\console\commands\docs\Generator;
/**
 * Adds headers and docblocks to classes and methods
 *
 **/
class Docs extends \lithium\console\Command {
	
	public function run() {
		
	}
	
	public function generator() {
		$generator = new Generator(array('request' => $this->request));
		return $generator->run();
	}
}
?>