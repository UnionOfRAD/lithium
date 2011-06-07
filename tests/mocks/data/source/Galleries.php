<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

class Galleries extends \lithium\data\Model {

	protected $_meta = array('connection' => 'test');

	public $hasMany = array('Images');
}

?>