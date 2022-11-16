<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\g11n\catalog\adapter;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use lithium\core\ConfigException;
use lithium\template\view\Compiler;

/**
 * The `Code` class is an adapter which treats files containing code as just another source
 * of globalized data.
 *
 * In fact it allows for extracting messages which are needed to build
 * message catalog templates. Currently only code written in PHP is supported through a parser
 * using the built-in tokenizer.
 *
 * @see lithium\g11n\Message
 * @see lithium\template\View
 */
class Code extends \lithium\g11n\catalog\Adapter {

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'path'`: The path to the directory holding the data.
	 *        - `'scope'`: Scope to use.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['path' => null, 'scope' => null];
		parent::__construct($config + $defaults);
	}

	/**
	 * Initializer.  Checks if the configured path exists.
	 *
	 * @throws lithium\core\ConfigException
	 */
	protected function _init() {
		parent::_init();

		if (!is_dir($this->_config['path'])) {
			$message = "Code directory does not exist at path `{$this->_config['path']}`.";
			throw new ConfigException($message);
		}
	}

	/**
	 * Reads data.
	 *
	 * @param string $category A category. `'messageTemplate'` is the only category supported.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return array Returns the message template. If the scope is not equal to the current scope
	 *         or `$category` is not `'messageTemplate'` null is returned.
	 */
	public function read($category, $locale, $scope) {
		if ($scope !== $this->_config['scope']) {
			return null;
		}
		$path = $this->_config['path'];

		switch ($category) {
			case 'messageTemplate':
				return $this->_readMessageTemplate($path);
			default:
				return null;
		}
	}

	/**
	 * Extracts data from files within configured path recursively.
	 *
	 * @param string $path Base path to start extracting from.
	 * @return array
	 */
	protected function _readMessageTemplate($path) {
		$base = new RecursiveDirectoryIterator($path);
		$iterator = new RecursiveIteratorIterator($base);
		$data = [];

		foreach ($iterator as $item) {
			$file = $item->getPathname();

			switch (pathinfo($file, PATHINFO_EXTENSION)) {
				case 'php':
					$data += $this->_parsePhp($file);
				break;
			}
		}
		return $data;
	}

	/**
	 * Parses a PHP file for messages marked as translatable.  Recognized as message
	 * marking are `$t()` and `$tn()` which are implemented in the `View` class. This
	 * is a rather simple and stupid parser but also fast and easy to grasp. It doesn't
	 * actively attempt to detect and work around syntax errors in marker functions.
	 *
	 * @see lithium\g11n\Message::aliases()
	 * @param string $file Absolute path to a PHP file.
	 * @return array
	 */
	protected function _parsePhp($file) {
		$contents = file_get_contents($file);
		$contents = Compiler::compile($contents);

		$defaults = [
			'ids' => [],
			'context' => null,
			'open' => false,
			'position' => 0,
			'occurrence' => ['file' => $file, 'line' => null]
		];
		extract($defaults);
		$data = [];

		if (strpos($contents, '$t(') === false && strpos($contents, '$tn(') === false) {
			return $data;
		}

		$tokens = token_get_all($contents);
		unset($contents);

		$findContext = function ($position) use ($tokens) {
			$ignore  = [T_WHITESPACE, '(', ')', T_ARRAY, ','];
			$open    = 1;
			$options = [];
			$depth   = 0;

			while (isset($tokens[$position]) && $token = $tokens[$position]) {
				if (!is_array($token)) {
					$token = [0 => null, 1 => $token, 2 => null];
				}
				if ($token[1] === '[' || $token[1] === '(') {
					$open++;
				} elseif (($token[1] === ']' || $token[1] === ')') && --$open === 0) {
					break;
				}
				if ($token[1] === '[' || $token[0] === T_ARRAY) {
					$depth++;
				} elseif ($depth > 1 && ($token[1] === ']' || $token[1] === ')')) {
					$depth--;
				}
				if ($depth === 1 && $open === 2) {
					if (!in_array($token[0] ? : $token[1], $ignore)) {
						$options[] = $token;
					}
				}
				$position++;
			}

			foreach ($options as $i => $token) {
				if (!(isset($options[$i + 1]) && isset($options[$i + 2]))) {
					break;
				}

				$condition1 = substr($token[1], 1, -1) === 'context';
				$condition2 = $options[$i + 1][0] === T_DOUBLE_ARROW;
				$condition3 = $options[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING;

				if ($condition1 && $condition2 && $condition3) {
					return $options[$i + 2][1];
				}
			}

			return null;
		};

		foreach ($tokens as $key => $token) {
			if (!is_array($token)) {
				$token = [0 => null, 1 => $token, 2 => null];
			}

			if ($open) {
				if ($position >= ($open === 'singular' ? 1 : 2)) {
					$data = $this->_merge($data, [
						'id' => $ids['singular'],
						'ids' => $ids,
						'occurrences' => [$occurrence],
						'context' => $context
					]);
					extract($defaults, EXTR_OVERWRITE);
				} elseif ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
					$ids[$ids ? 'plural' : 'singular'] = $token[1];
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
					$context = $findContext($key + 2);
				}
			}
		}
		return $data;
	}

	/**
	 * Merges an item into given data and removes quotation marks
	 * from the beginning and end of message strings.
	 *
	 * @see lithium\g11n\catalog\Adapter::_merge()
	 * @param array $data Data to merge item into.
	 * @param array $item Item to merge into $data.
	 * @return array The merged data.
	 */
	protected function _merge(array $data, array $item) {
		$filter = function ($value) use (&$filter) {
			if (is_array($value)) {
				return array_map($filter, $value);
			}
			return substr($value, 1, -1);
		};
		$fields = ['id', 'ids', 'translated', 'context'];

		foreach ($fields as $field) {
			if (isset($item[$field])) {
				$item[$field] = $filter($item[$field]);
			}
		}
		return parent::_merge($data, $item);
	}
}

?>