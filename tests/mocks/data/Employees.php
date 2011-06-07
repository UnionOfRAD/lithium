<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class Employees extends \lithium\data\Model {

	public $belongsTo = array('Companies');

	protected $_meta = array('connection' => 'test');

	public function lastName($entity) {
		$name = explode(' ', $entity->name);
		return $name[1];
	}
}

?>