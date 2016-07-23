<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\mongodb;

class Images extends \lithium\data\Model {

	public $belongsTo = array('Galleries');

	public $hasMany = array('ImagesTags', 'Comments');

	protected $_meta = array('connection' => 'test');

	protected $_schema = array(
		'_id' => array('type' => 'id'),
		'gallery' => array('type' => 'id'),
		'image' => array('type' => 'string', 'length' => 255),
		'title' => array('type' => 'string', 'length' => 50),
		'created' => array('type' => 'datetime'),
		'modified' => array('type' => 'datetime')
	);
}

?>