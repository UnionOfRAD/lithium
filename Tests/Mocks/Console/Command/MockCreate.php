<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Console\Command;

class MockCreate extends \Lithium\Console\Command\Create {

	protected $_classes = array(
		'response' => '\Lithium\Tests\Mocks\Console\MockResponse'
	);

	public function save($template, $params = array()) {
		return $this->_save($template, $params);
	}
}

?>