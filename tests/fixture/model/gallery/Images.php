<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\fixture\model\gallery;

class Images extends \lithium\data\Model {

	public $belongsTo = ['Galleries'];

	public $hasMany = ['ImagesTags', 'Comments'];

	protected $_meta = ['connection' => 'test'];
}

?>