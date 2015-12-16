<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

use lithium\core\Filterable;

class MockObjectConfiguration extends \lithium\core\Object {
	use Filterable;
	
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