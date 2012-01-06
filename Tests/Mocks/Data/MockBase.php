<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD(http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data;

class MockBase extends \Lithium\Data\Model {

	protected $_meta = array('connection' => 'mock-source');

	public static function __init() {
		static::_isBase(__CLASS__, true);
		parent::__init();
	}
}

?>