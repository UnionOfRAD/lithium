<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

/**
 * Provides methods to recursively merge an object's array properties.
 *
 * Does intentionally not do any caching of method call results, as it is expected that
 * inheritance happens on distinct classes once per (request/response) cycle.
 */
trait MergeInheritable {

	/**
	 * Recursively merges a set of properties of each parent in the class tree into the
	 * objects corresponding property.
	 *
	 * When called from a subclass will stop after merging that class' properties this
	 * method is member of.
	 *
	 * _Note_: Will error out when trying to merge a non-array property.
	 *
	 * @param array $properites Names of array properties to merge with.
	 * @return void
	 */
	protected function _inherit(array $properties) {
		if (($class = get_called_class()) === __CLASS__) {
			return;
		}
		foreach (class_parents($class) as $parent) {
			$inherit = get_class_vars($parent);

			foreach ($properties as $member) {
				if (isset($inherit[$member])) {
					$this->{$member} += $inherit[$member];
				}
			}
			if ($parent === __CLASS__) {
				break;
			}
		}
	}
}

?>