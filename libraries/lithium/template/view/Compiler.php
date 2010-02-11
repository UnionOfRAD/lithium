<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\view;

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
		'/\<\?=\s*\$this->(.+?)\s*;?\s*\?>/ms' => '<?php echo $this->$1; ?>',
		'/\<\?=\s*(.+?)\s*;?\s*\?>/ms' => '<?php echo $h($1); ?>'
	);

	/**
	 * Compiles a template and writes it to a cache file, which is used for inclusion.
	 *
	 * @param string $file The full 
	 * @param string $options 
	 * @return void
	 */
	public static function template($file, $options = array()) {
		$defaults = array('path' => LITHIUM_APP_PATH . '/resources/tmp/cache/templates');
		$options += $defaults;

		$oname = basename($file);
		$stats = stat($file);
		extract($stats);
		$template = "{$options['path']}/template_{$oname}_{$ino}_{$mtime}_{$size}.php";

		if (!file_exists($template)) {
			file_put_contents($template, static::compile(file_get_contents($file)));
		}
		return $template;
	}

	public static function compile($string) {
		foreach (static::$_processors as $pattern => $replacement) {
			$string = preg_replace($pattern, $replacement, $string);
		}
		return $string;
	}
}

?>