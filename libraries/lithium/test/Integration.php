<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

class Integration extends \lithium\test\Unit {

	/**
	 * Auto init for applying Integration filter
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		$this->applyFilter('run', function($self, $params, $chain) {
			$before = $self->results();

			$chain->next($self, $params, $chain);

			$after = $self->results();

			while (count($after) > count($before)) {
				$result = array_pop($after);
				if ($result['result'] == 'fail') {
					return false;
				}
			}
		});
	}
}

?>