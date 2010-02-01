<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\action;

class MockMediaClass extends \lithium\net\http\Media {

	public static function render(&$response, $data = null, $options = array()) {
		$response->options = $options;
		$response->data = $data;
	}
}

?>