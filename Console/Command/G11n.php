<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Console\Command;

use Lithium\Console\Command\G11n\Extract;

/**
 * The `G11n` set of commands deals with the extraction and merging of message templates.
 */
class G11n extends \Lithium\Console\Command {

	/**
	 * The main method of the command.
	 *
	 * @return void
	 */
	public function run() {}

	/**
	 * Runs the `Extract` command.
	 *
	 * @return void
	 */
	public function extract() {
		$extract = new Extract(array('request' => $this->request));
		return $extract->run();
	}
}

?>