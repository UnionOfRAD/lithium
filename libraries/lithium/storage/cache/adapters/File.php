<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
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