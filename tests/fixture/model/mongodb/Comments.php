<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\mongodb;

class Comments extends \lithium\data\Model {

	protected $_meta = array('connection' => 'test');

	protected $_schema = array(
		'_id' => array('type' => 'id'),
		'image' => array('type' => 'id'),
		'body' => array('type' => 'string')
	);
}

?>