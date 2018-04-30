<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\core;

/**
 * @deprecated
 */
class MockObjectDeprecatedConfiguration extends \lithium\core\ObjectDeprecated {

	use \lithium\core\AutoConfigurable;

	protected $_testScalar = 'default';

	protected $_testArray = ['default'];

	protected $_protected = null;

	public function __construct(array $config = []) {
		if (isset($config['autoConfig'])) {
			$this->_autoConfig = (array) $config['autoConfig'];
			unset($config['autoConfig']);
		}
		$this->_autoConfig($config, $this->_autoConfig);
		parent::__construct($config);
	}

	public function testScalar($value) {
		$this->_testScalar = 'called';
	}

	public function getProtected() {
		return $this->_protected;
	}

	public function getConfig() {
		return [
			'testScalar' => $this->_testScalar,
			'testArray' => $this->_testArray
		];
	}
}

?>