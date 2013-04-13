<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\action;

class MockIisRequest extends \lithium\action\Request {

	public function __construct(array $config = array()) {
		$config = array(
			'env' => array(
				'PLATFORM' => 'IIS',
				'SCRIPT_NAME' => '\index.php',
				'SCRIPT_FILENAME' => false,
				'DOCUMENT_ROOT' => false,
				'PATH_TRANSLATED' => '\lithium\app\webroot\index.php',
				'HTTP_PC_REMOTE_ADDR' => '123.456.789.000'
			),
			'globals' => false
		);
		parent::__construct($config);
	}
}

?>