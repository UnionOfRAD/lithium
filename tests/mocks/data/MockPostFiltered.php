<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;


class MockPostFiltered extends \lithium\tests\mocks\data\MockBase {

	public $hasMany = array('MockComment');

	public static $connection = null;

	public static $initCalled = 0;

	protected $_meta = array('connection' => false, 'key' => 'id');

	public function _init() {
		parent::_init();

		static::applyFilter('filteredStatic', function($self, $params, $chain) {
		    $response = $chain->next($self, $params, $chain);
		    return $response . ' filtered static';
		});

		static::applyFilter('filteredDynamic', function($self, $params, $chain) {
		    $response = $chain->next($self, $params, $chain);
		    return $response . ' filtered dynamic';
		});

		static::$initCalled++;
	}

	public function filteredDynamic($entity, $value) {
		$params = compact('value');

		return static::_filter(__METHOD__, $params, function($self, $params) {
		    return $params['value'];
		});
	}

	public static function filteredStatic($value) {
		$params = compact('value');

		return static::_filter(__FUNCTION__, $params, function($self, $params) {
		    return $params['value'];
		});
	}
}

?>