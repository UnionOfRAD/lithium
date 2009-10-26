<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\cache\adapters;

use \SplFileInfo;
use \DirectoryIterator;

class File extends \lithium\core\Object {

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('path' => LITHIUM_APP_PATH . '/tmp/cache');
		parent::__construct($config + $defaults);
	}

	/**
	 * Write value(s) to the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @param mixed  $value      The value to be cached
	 * @param object $conditions Conditions under which the operation should proceed
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($key, $data, $expiry, $conditions = null) {
		$path = $this->_config['path'];

		return function($self, $params, $chain) use (&$path) {
			extract($params);
			$expiry = strtotime($expiry);
			$data = "{:expiry:{$expiry}}\n{$data}";
			$path = "$path/$key";

			return file_put_contents($path, $data);
		};

	}

	/**
	 * Read value(s) from the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @param object $conditions Conditions under which the operation should proceed
	 * @return mixed Cached value if successful, false otherwise
	 */
	public function read($key, $conditions = null) {
		$path = $this->_config['path'];

		return function($self, $params, $chain) use (&$path) {
			extract($params);
			$path = "$path/$key";
			$file = new SplFileInfo($path);

			if (!$file->isFile() || !$file->isReadable())  {
				return false;
			}

			$data = file_get_contents($path);
			preg_match('/^\{\:expiry\:(\d+)\}\\n/', $data, $matches);
			$expiry = $matches[1];

			if ($expiry < time()) {
				unlink($path);
				return false;
			}
			return preg_replace('/^\{\:expiry\:\d+\}\\n/', '', $data, 1);

		};

	}

	/**
	 * Delete value from the cache
	 *
	 * @param string $key        The key to uniquely identify the cached item
	 * @param object $conditions Conditions under which the operation should proceed
	 * @return mixed True on successful delete, false otherwise
	 */
	public function delete($key, $conditions = null) {
		$path = $this->_config['path'];

		return function($self, $params, $chain) use (&$path) {
			extract($params);
			$path = "$path/$key";
			$file = new SplFileInfo($path);

			if ($file->isFile() && $file->isReadable())  {
				return unlink($path);
			}

			return false;
		};
	}

	/**
	 * Clears user-space cache
	 *
	 * @return mixed True on successful clear, false otherwise
	 */
	public function clear() {
		$directory = new DirectoryIterator($this->_config['path']);

		foreach ($directory as $file) {
			if ($file->isFile()) {
				unlink($file->getPathInfo());
			}
		}
		return true;

	}

}

?>