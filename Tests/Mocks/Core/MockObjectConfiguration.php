<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Core;

class MockObjectConfiguration extends \Lithium\Core\Object {

	protected $_testScalar = 'default';

	protected $_testArray = array('default');

	protected $_protected = null;

	public function __construct(array $config = array()) {
		if (isset($config['autoConfig'])) {
			$this->_autoConfig = (array) $config['autoConfig'];
			unset($config['autoConfig']);
		}
		parent::__construct($config);
	}

	public function testScalar($value) {
		$this->_testScalar = 'called';
	}

	public function getProtected() {
		return $this->_protected;
	}

	public function getConfig() {
		return array(
			'testScalar' => $this->_testScalar,
			'testArray' => $this->_testArray
		);
	}
}

?>