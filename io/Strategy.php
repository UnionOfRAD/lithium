<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\io;

/**
 * This is the base class for all strategies. A strategy manipulates data and is used by
 * classes that follow the write, read, delete triad. Strategies manipulate data before it
 * is written, after it is read and before it is deleted.
 */
abstract class Strategy {

	/**
	 * Write strategy method.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	abstract public function write($data);

	/**
	 * Read strategy method.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	abstract public function read($data);

	/**
	 * Delete strategy method.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	abstract public function delete($data);

	/**
	 * Determines if a strategey is available for usage and all preconditions are met
	 * (i.e. extension installed).
	 *
	 * Override to check for preconditions.
	 *
	 * @return boolean `true` if enabled, `false` otherwise.
	 */
	public static function enabled() {
		return true;
	}
}

?>