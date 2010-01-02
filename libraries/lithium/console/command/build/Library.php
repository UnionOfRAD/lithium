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
	 * `li3 build library myapp`
	 * `li3 build library myapp another_archive`
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
				$this->out(basename($new) . " created in " . dirname($new) . " from {$from}");
				return true;
			}
		}
		$this->error("Could not extract {$from}");
		return false;
	}

	/**
	 * Create the Phar::GZ archive
	 * `li3 build library archive my_archive`
	 * `li3 build library archive my_archive myapp`
	 *
	 * @param string $to the name of or path the archive
	 * @param string $from the name of or path to directory to compress
	 * @return boolean
	 */
	public function archive($to = null, $from = null) {
		$path = $this->_toPath($to);
 		$archive = new Phar("{$path}.phar");
		$from = $this->_toPath($from);
		$filter = '/^(?(?=\.)\.(htaccess|gitignore|gitmodules)|.*)$/i';
		$result = (boolean) $archive->buildFromDirectory($from, $filter);
		if ($result) {
			$archive->compress(Phar::GZ);
			$this->out(basename($path) . ".phar.gz created in " . dirname($path) . " from {$from}");
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
		$path = $this->request->env('working');
		$library = Libraries::get($this->library);

		if (!empty($library['path'])) {
			$path = dirname($library['path']);
		}
		if (!$name) {
			return $path;
		}
		$name = ($name[0] !== '/') ? "{$path}/{$name}" : $name;
		return $name;
	}
}

?>