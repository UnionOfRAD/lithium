<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data;

class Companies extends \Lithium\Data\Model {

	public $hasMany = array('Employees');

	protected $_meta = array('connection' => 'test');
}

?>