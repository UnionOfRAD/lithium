<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Action;

class MockCgiRequest extends \Lithium\Action\Request {

	protected function _init() {
		parent::_init();
		$this->_env = array(
			'PLATFORM' => 'CGI',
			'SCRIPT_FILENAME' => false,
			'DOCUMENT_ROOT' => false,
			'SCRIPT_URL' => '/lithium/app/webroot/index.php'
		);
	}
}

?>