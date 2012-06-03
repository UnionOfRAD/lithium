<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

class Images3 extends \lithium\data\Model {

	protected $_meta = array(
			'connection' => 'lithium_mysql_test',
			'source' => 'images'
	);

	/**
	 * a table row extra
	 */
	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'gallery_id' => array('type' => 'integer'),
		'image' => array('type' => 'string', 'length' => 50),
		'title' => array('type' => 'string', 'length' => 50),
		'extra' => array('type' => 'string', 'length' => 50),
	);

	public $belongsTo = array('Galleries');
}

?>