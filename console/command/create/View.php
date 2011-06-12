<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use lithium\util\Inflector;
use lithium\util\String;

/**
 * Generate a View file in the `--library` namespace
 *
 * `li3 create view Posts index`
 * `li3 create --library=li3_plugin view Posts index`
 *
 */
class View extends \lithium\console\command\Create {

    /**
     * Override the save method to handle view specific params.
     *
     * @param array $params
     * @return mixed
     */
	protected function _save(array $params = array()) {
		$params['path'] = Inflector::underscore($this->request->action);
		$params['file'] = $this->request->args(0);

		$contents = $this->_template();
		$result = String::insert($contents, $params);

		if (!empty($this->_library['path'])) {
			$path = $this->_library['path'] . "/views/{$params['path']}/{$params['file']}";
			$file = str_replace('//', '/', "{$path}.php");
			$directory = dirname($file);

			if (!is_dir($directory)) {
				if (!mkdir($directory, 0755, true)) {
					return false;
				}
			}
			$directory = str_replace($this->_library['path'] . '/', '', $directory);

			if (file_put_contents($file, "<?php\n\n{$result}\n\n?>")) {
				return "{$params['file']}.php created in {$directory}.";
			}
		}
		return false;
	}
}

?>