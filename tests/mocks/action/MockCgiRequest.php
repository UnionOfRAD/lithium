<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\action;

class MockCgiRequest extends \lithium\action\Request {

	public function __construct(array $config = array()) {
		$config = array(
			'env' => array(
				'PLATFORM' => 'CGI',
				'SCRIPT_FILENAME' => false,
				'DOCUMENT_ROOT' => false,
				'SCRIPT_URL' => '/lithium/app/webroot/index.php'
			),
			'globals' => false
		);
		parent::__construct($config);
	}
}

?>