<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\G11n\Catalog;

class MockAdapter extends \Lithium\G11n\Catalog\Adapter {

	public function merge($data, $item) {
		return $this->_merge($data, $item);
	}
}

?>