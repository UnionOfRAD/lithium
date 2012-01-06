<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Net\Http;

class Template extends \Lithium\Core\Object {

	public function __construct(array $config = array()) {
		$config['response']->headers('Custom', 'Value');
	}

	public function render() {}
}

?>