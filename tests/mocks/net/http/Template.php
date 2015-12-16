<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\net\http;

use lithium\core\Filterable;

class Template extends \lithium\core\Object {
	use Filterable;

	public function __construct(array $config = array()) {
		$config['response']->headers('Custom', 'Value');
	}

	public function render() {}
}

?>