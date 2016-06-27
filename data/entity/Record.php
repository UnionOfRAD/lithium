<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\entity;

/**
 * `Record` class. Represents data such as a row from a database. Records have fields (often known
 * as columns in databases).
 */
class Record extends \lithium\data\Entity {

	protected function _init() {
		parent::_init();
		$this->_handlers += array('stdClass' => function($item) { return $item; });
	}
}

?>