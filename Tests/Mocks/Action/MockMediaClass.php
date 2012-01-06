<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Action;

class MockMediaClass extends \Lithium\Net\Http\Media {

	public static function render(&$response, $data = null, array $options = array()) {
		$response->options = $options;
		$response->data = $data;
	}
}

?>