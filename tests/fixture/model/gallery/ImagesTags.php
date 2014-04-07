<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2014, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\gallery;

class ImagesTags extends \lithium\data\Model {

	public $belongsTo = array('Images', 'Tags');

	protected $_meta = array('connection' => 'test');
}

?>