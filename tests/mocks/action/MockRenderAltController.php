<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\action;

class MockRenderAltController extends \lithium\action\Controller {
	protected $_render = array(
		'data' => array('foo' => 'bar'),
		'layout' => 'alternate'
	);

	public function access($var) {
		return $this->{$var};
	}
}

?>