<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command;

use lithium\console\command\g11n\Extract;

/**
 * The `G11n` set of commands deals with the extraction and merging of message templates.
 */
class G11n extends \lithium\console\Command {

	/**
	 * The main method of the command.
	 *
	 * @return void
	 */
	public function run() {}

	/**
	 * Runs the `Extract` command.
	 *
	 * @return integer|boolean|void
	 */
	public function extract() {
		$extract = new Extract(['request' => $this->request]);
		return $extract->run();
	}
}

?>