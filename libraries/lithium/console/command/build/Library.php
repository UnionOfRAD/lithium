<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\build;

use \Phar;
use \lithium\core\Libraries;

/**
 * Generate directory structure
 *
 * @package default
 */
class Library extends \lithium\console\command\Build {

	/**
	 * Runs current command
	 *
	 * @return void
	 */
	public function run($new = 'new', $copy = null) {
		$new = $this->_toPath($new);
		$copy = $this->_toPath($copy);

		if (file_exists("{$copy}.phar.gz")) {
			$archive = new Phar("{$copy}.phar.gz");
			if ($archive->extractTo($new)) {
				$this->out(basename($new) . " created in " . dirname($new));
				return true;
			}
		}
		$this->error("Could not extract {$copy}.phar.gz");
		return false;
	}

	/**
	 * create an archive to use compressed with gz
	 *
	 * @param string $name
	 * @param string $from
	 * @return void
	 */
	public function archive($name = 'app', $from = null) {
		$path = $this->_toPath($name);
		$archive = new Phar("{$path}.phar");
		$from = $from !== null ? $this->_toPath($from) :  LITHIUM_APP_PATH;
		$result = (bool) $archive->buildFromDirectory($from);
		if ($result) {
			$archive->compress(Phar::GZ);
			$this->out(basename($path) . " created in " . dirname($path));
			return true;
		}
		return false;
	}

	/**
	 * Take a name and return the path
	 *
	 * @param string $name
	 * @return void
	 */
	protected function _toPath($name) {
		$library = Libraries::get($this->library);
		$name = ($name[0] !== '/') ? dirname($library['path']) . "/{$name}" : $name;
		return $name;
	}
}

?>