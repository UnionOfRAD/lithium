<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

class Images2 extends \lithium\data\Model {

	protected $_meta = array(
			'connection' => 'lithium_mysql_test',
			'source' => 'images'
	);

	/**
	 * swapping sequence for title and image
	 */
	protected $_schema = array(
		'id' => array('type' => 'integer'),
		'gallery_id' => array('type' => 'integer'),
		'title' => array('type' => 'string', 'length' => 50),
		'image' => array('type' => 'string', 'length' => 50),
	);

	public $belongsTo = array('Galleries');
}

?>