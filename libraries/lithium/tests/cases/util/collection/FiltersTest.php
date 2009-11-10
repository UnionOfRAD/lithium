<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\util\collection;

use \lithium\util\collection\Filters;

class FiltersTest extends \lithium\test\Unit {

	public function testRun() {
		$options = array('method' => __FUNCTION__, 'class' => __CLASS__, 'items' => array(
			function($self, $params, $chain) {
				$params['message'] .= 'is a filter chain ';
				return $chain->next($self, $params, $chain);
			},
			function($self, $params, $chain) {
				$params['message'] .= 'in the ' . $chain->method() . ' method ';
				return $chain->next($self, $params, $chain);
			},
			function($self, $params, $chain) {
				return $params['message'] . 'of the ' . $self . ' class.';
			}
		));
		$result = Filters::run(__CLASS__, array('message' => 'This '), $options);
		$expected = 'This is a filter chain in the testRun method of the';
		$expected .= ' lithium\tests\cases\util\collection\FiltersTest class.';
		$this->assertEqual($expected, $result);
	}
}

?>