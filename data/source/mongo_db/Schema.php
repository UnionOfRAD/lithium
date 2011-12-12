<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongo_db;

use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use MongoBinData;

class Schema extends \lithium\data\Schema {

	protected $_handlers = array();

	public function __construct(array $config = array()) {
		$defaults = array('fields' => array('_id' => array('type' => 'id')));
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		$this->_autoConfig[] = 'handlers';
		parent::_init();

		$this->_handlers += array(
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				$v = is_numeric($v) ? intval($v) : strtotime($v);
				return (!$v || time() == $v) ? new MongoDate() : new MongoDate($v);
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code'    => function($v) { return new MongoCode($v); },
			'binary'  => function($v) { return new MongoBinData($v); }
		);
	}
}

?>