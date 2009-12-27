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
 * Generate and extract Phar::GZ archives. Requires zlib extension.
 *
 */
class Library extends \lithium\console\command\Build {

	/**
	 * Extract an archive into a path
	 *
	 * @param string $new the name of or path to the library to create `from` phar
	 * @param string $from the name of or path to the phar.gz to copy
	 * @return boolean
	 */
	public function run($new = 'new', $from = 'app') {
		$new = $this->_toPath($new);

		if ($from[0] !== '/') {
			$from = Libraries::locate('command.build.template', $from, array(
				'filter' => false, 'type' => 'file', 'suffix' => '.phar.gz',
			));
			if (!$from || is_array($from)) {
				return false;
			}
		}
		if (file_exists("{$from}")) {
			$archive = new Phar("{$from}");
			if ($archive->extractTo($new)) {
				$this->out(basename($new) . " created in " . dirname($new));
				return true;
			}
		}
		$this->error("Could not extract {$from}");
		return false;
	}

	/**
	 * Create the Phar::GZ archive
	 *
	 * @param string $name the name of or path the archive
	 * @param string $from the name of or path to directory to compress
	 * @return boolean
	 */
	public function archive($name = 'app', $from = null) {
		$path = $this->_toPath($name);
		$archive = new Phar("{$path}.phar");
		$from = $from !== null ? $this->_toPath($from) :  LITHIUM_APP_PATH;
		$result = (boolean) $archive->buildFromDirectory($from);
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
	 * @return string
	 */
	protected function _toPath($name) {
		$library = Libraries::get($this->library);
		$name = ($name[0] !== '/') ? dirname($library['path']) . "/{$name}" : $name;
		return $name;
	}
}

?>