<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\mongodb;

class Galleries extends \lithium\data\Model {

	public $hasMany = array('Images');

	protected $_meta = array('connection' => 'test');

	protected $_schema = array(
		'_id' => array('type' => 'id'),
		'name' => array('type' => 'string', 'length' => 50),
		'active' => array('type' => 'boolean', 'default' => true),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime')
	);
}

?>