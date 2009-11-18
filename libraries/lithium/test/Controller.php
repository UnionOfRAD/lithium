<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\test\Reporter;
use \lithium\test\Dispatcher;

class Controller extends \lithium\core\Object {

	public function __invoke($request, $params, $options = array()) {
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
		return Reporter::run(
			Dispatcher::process(Dispatcher::run(null, $params['args'])),
			array('format' => 'html')
		);
	}
}

?>