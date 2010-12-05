<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class Company extends \lithium\data\Model {

	public $hasMany = array('Employees');

	protected $_meta = array('connection' => 'test');
}

?>