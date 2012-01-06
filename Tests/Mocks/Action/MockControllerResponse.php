<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Action;

class MockControllerResponse extends \Lithium\Action\Response {

	public $hasRendered = false;

	public function render() {
		$this->hasRendered = true;
	}
}

?>