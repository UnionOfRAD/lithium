<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\commands\docs;

use \lithium\core\Libraries;
use \lithium\console\Request;
use \lithium\util\Inflector;
use \lithium\util\reflection\Inspector;

/**
 * Searches and displays @todo, @discuss, @fix and @important comments in your code.
 */
class Todo extends \lithium\console\Command {

	/**
	 * undocumented variable
	 *
	 * @var id
	 */
	public $show;

	public function main($dir = null) {
		if (!$dir) {
			$namespace = '\\';
		} else {
			$namespace = array_reduce(explode('/', $dir), function($namespace, $part) {
				return $namespace . '\\' . Inflector::camelize($part);
			});
		}
		$libs = Libraries::find($namespace, array('recursive' => true));
		$files = array();

		foreach ($libs as $lib) {
			$file = Libraries::path($lib);
			if ($matches = static::parse(file_get_contents($file))) {
				$files[$file] = $matches;
			}
		}

		foreach ($files as $file => $matches) {
			if (!$this->show) {
				Console::out($file.':');
			}
			$rows = array(array('', 'ID', 'LINE', 'TYPE', 'TEXT'));
			foreach ($matches as $match) {
				$id = substr(sha1($file.$match['line']), 0, 4);
				if ($id == $this->show) {
					Console::stop(0, $file);
				}
				$rows[] = array('', $id, $match['line'], $match['type'], $match['text']);
			}
			if (!$this->show) {
				Console::out(Console::columns($rows));
				Console::hr();
				Console::nl();
			}
		}
	}

	/**
	 * undocumented
	 *
	 */
}

// if (1) {
// 	Request::dispatch(new Request(compact('argv')), new Todo());
// }

?>