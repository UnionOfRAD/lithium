<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapters;

use \Exception;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

/**
 * The `Code` class is an adapter which treats files containing code as just another source
 * of globalized data.  In fact it allows for extracting messages which are needed to build
 * message catalog templates. Currently only code written in PHP is supported through a parser
 * using the built-in tokenizer.
 *
 * @see lithium\g11n\Message
 */
class Code extends \lithium\g11n\catalog\adapters\Base {

	/**
	 * Supported categories.
	 *
	 * @var array
	 */
	protected $_categories = array(
		'message' => array(
			'template' => array('read' => true)
	));

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'path'`: The path to the directory holding the data.
	 *        - `'scope'`: Scope to use.
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('path' => null, 'scope' => null);
		parent::__construct($config + $defaults);
	}

	/**
	 * Initializer.  Checks if the configured path exists.
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function _init() {
		parent::_init();
		if (!is_dir($this->_config['path'])) {
			throw new Exception("Code directory does not exist at `{$this->_config['path']}`");
		}
	}

	/**
	 * Extracts data from files within configured path recursively.
	 *
	 * @param string $category Dot-delimited category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return mixed
	 */
	public function read($category, $locale, $scope) {
		if ($scope != $this->_config['scope']) {
			return null;
		}
		$path = $this->_config['path'];

		$base = new RecursiveDirectoryIterator($path);
		$iterator = new RecursiveIteratorIterator($base);
		$data = array();

		foreach ($iterator as $item) {
			$file = $item->getPathname();

			switch (pathinfo($file, PATHINFO_EXTENSION)) {
				case 'php':
					$data += $this->_parsePhp($file);
				break;
			}
		}
		if ($data) {
			return $data;
		}
	}

	/**
	 * Writing is not supported.
	 *
	 * @param string $category Dot-delimited category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @param mixed $data The data to write.
	 * @return void
	 */
	public function write($category, $locale, $scope, $data) {}

	/**
	 * Parses a PHP file for messages marked as translatable.  Recognized as message
	 * marking are `$t()` and `$tn()` which are implemented in the `View` class. This
	 * is a rather simple and stupid parser but also fast and easy to grasp. It doesn't
	 * actively attempt to detect and work around syntax errors in marker functions.
	 *
	 * @param string $file Absolute path to a PHP file.
	 * @return array
	 * @see lithium\template\View
	 */
	protected function _parsePhp($file) {
		$contents = file_get_contents($file);

		$defaults = array(
			'singularId' => null,
			'pluralId' => null,
			'open' => false,
			'position' => 0,
			'occurrence' => array('file' => $file, 'line' => null)
		);
		extract($defaults);
		$data = array();

		if (strpos($contents, '$t(') === false && strpos($contents, '$tn(') == false) {
			return $data;
		}

		$tokens = token_get_all($contents);
		unset($contents);

		foreach ($tokens as $key => $token) {
			if (!is_array($token)) {
				$token = array(0 => null, 1 => $token, 2 => null);
			}

			if ($open) {
				if ($position >= ($open === 'singular' ? 1 : 2)) {
					$this->_mergeMessageItem($data, array(
						'singularId' => $singularId,
						'pluralId' => $pluralId,
						'occurrences' => array($occurrence),
					));
					extract($defaults, EXTR_OVERWRITE);
				} elseif ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
					$type = isset($singularId) ? 'pluralId' : 'singularId';
					$$type = $token[1];
					$position++;
				}
			} else {
				if (isset($tokens[$key + 1]) && $tokens[$key + 1] === '(') {
					if ($token[1] === '$t') {
						$open = 'singular';
					} elseif ($token[1] === '$tn') {
						$open = 'plural';
					} else {
						continue;
					}
					$occurrence['line'] = $token[2];
				}
			}
		}
		return $data;
	}

	/**
	 * Merges a message item into given data and removes quotation marks
	 * from the beginning and end of message strings.
	 *
	 * @param array $data Data to merge item into.
	 * @param array $item Item to merge into $data.
	 * @return void
	 * @see lithium\g11n\catalog\adapter\Base::_mergeMessageItem()
	 */
    protected function _mergeMessageItem(&$data, $item) {
		$fields = array('singularId', 'pluralId');

		foreach ($fields as $field) {
			if (isset($item[$field])) {
				$item[$field] = substr($item[$field], 1, -1);
			}
		}
        return parent::_mergeMessageItem($data, $item);
    }
}

?>