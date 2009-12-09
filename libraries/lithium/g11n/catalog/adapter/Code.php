<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapter;

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
class Code extends \lithium\g11n\catalog\adapter\Base {

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
	 * @return object
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
	 * Parses a PHP file for translateable strings wrapped in `$t()` calls.
	 *
	 * @param string $file Absolute path to a PHP file.
	 * @return array
	 * @todo How should invalid entries be handled?
	 */
	protected function _parsePhp($file) {
		$contents = file_get_contents($file);

		if (strpos($contents, '$t(') === false) {
			return array();
		}

		$defaults = array(
			'singularId' => null,
			'pluralId' => null,
			'open' => false,
			'concat' => false,
			'occurrence' => array('file' => $file, 'line' => null)
		);
		extract($defaults);
		$data = array();

		$tokens = token_get_all($contents);
		unset($contents);

		foreach ($tokens as $key => $token) {
			if (!is_array($token)) {
				$token = array(0 => null, 1 => $token, 2 => null);
			}

			if (!$open) {
				if ($token[1] === '$t' && isset($tokens[$key + 1]) && $tokens[$key + 1] === '(') {
					$open = true;
					$occurrence['line'] = $token[2];
				}
			} else {
				if ($token[1] === '.') {
					$concat = true;
				} elseif ($token[1] === ',') {
					$concat = false;
				} elseif ($token[0] === T_CONSTANT_ENCAPSED_STRING && !isset($pluralId)) {
					$type = isset($singularId) ? 'pluralId' : 'singularId';
					$$type = ($concat ? $$type : null) . $this->_formatMessage($token[1]);
				} elseif ($token[0] !== T_WHITESPACE && $token[1] !== '(') {
					if (isset($singularId)) {
						$this->_mergeMessageItem($data, array(
							'singularId' => $singularId,
							'pluralId' => $pluralId,
							'occurrences' => array($occurrence),
						));
					}
					extract($defaults, EXTR_OVERWRITE);
				}
			}
		}
		return $data;
	}

	/**
	 * Formats a string to be added as a message.
	 *
	 * @param string $string
	 * @return string
	 */
	function _formatMessage($string) {
		$quote = substr($string, 0, 1);
		$string = substr($string, 1, -1);

		if ($quote === '"') {
			$string = stripcslashes($string);
		} else {
			$string = strtr($string, array("\\'" => "'", "\\\\" => "\\"));
		}
		$string = str_replace("\r\n", "\n", $string);
		return addcslashes($string, "\0..\37\\\"");
	}
}

?>