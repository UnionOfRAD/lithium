<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\view;

use \Exception;

/**
 * The template compiler is a simple string replacement engine which allows PHP templates to be
 * overridden with custom syntax. The default process rules allow PHP templates using short-echo
 * syntax (`<?=`) to be rewritten to full PHP tags which automatically escape their output.
 */
class Compiler extends \lithium\core\StaticObject {

	/**
	 * The list of syntax replacements to apply to compiled templates.
	 */
	protected static $_processors = array(
		'/\<\?=\s*\$this->(.+?)\s*;?\s*\?>/msx' => '<?php echo $this->$1; ?>',
		'/\<\?=\s*(.+?)\s*;?\s*\?>/msx' => '<?php echo $h($1); ?>'
	);

	/**
	 * Compiles a template and writes it to a cache file, which is used for inclusion.
	 *
	 * @param string $file The full
	 * @param string $options
	 * @return void
	 */
	public static function template($file, $options = array()) {
		$cachePath = LITHIUM_APP_PATH . '/resources/tmp/cache/templates';
		$defaults = array('path' => $cachePath, 'fallback' => true);
		$options += $defaults;

		$stats = stat($file);
		$oname = basename($file, '.php');
		$template = "template_{$oname}_{$stats['ino']}_{$stats['mtime']}_{$stats['size']}.php";
		$template = "{$options['path']}/{$template}";

		if (!file_exists($template)) {
			$compiled = static::compile(file_get_contents($file));
			$success = $options['fallback'] && !is_writable(dirname($template))
				? false : (file_put_contents($template, $compiled) !== false);

			if (!$success && $options['fallback']) {
				return $file;
			} elseif (!$success && !$options['fallback']) {
				throw new Exception('Could not write compiled template to cache');
			}
		}
		return $template;
	}

	public static function compile($string) {
		$patterns = static::$_processors;
		return preg_replace(array_keys($patterns), array_values($patterns), $string);
	}
}

?>