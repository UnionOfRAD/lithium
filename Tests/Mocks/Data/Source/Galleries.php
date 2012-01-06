<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data\Source;

class Galleries extends \Lithium\Data\Model {

	protected $_meta = array('connection' => 'test');

	public $hasMany = array('Images');
}

?>