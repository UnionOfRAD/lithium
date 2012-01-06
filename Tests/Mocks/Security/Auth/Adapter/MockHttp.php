<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Security\Auth\Adapter;

class MockHttp extends \Lithium\Security\Auth\Adapter\Http {

	public $headers = array();

	protected function _writeHeader($string) {
		$this->headers[] = $string;
	}
}

?>