<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapter;

use \Exception;
use \lithium\util\Inflector;

/**
 * The `Gettext` class is an adapter for reading and writing PO and MO files without the
 * requirement of having the gettext extension enabled or installed. Moreover it doesn't
 * require the usage of the non thread safe `setlocale()`.
 *
 * The adapter works with the directory structure below. The example shows the structure
 * for the directory as given by the `'path'` configuration setting. It closely ressembles
 * the standard gettext directory structure with a few slight adjustments to the way
 * templates are being named.
 *
 * {{{
 * | - `<locale>`
 * | | - `LC_MESSAGES`
 * |   | - `default.po`
 * |   | - `default.mo`
 * |   | - `<scope>.po`
 * |   | - `<scope>.mo`
 * | | - `LC_VALIDATION`
 * |   | - ...
 * | - ...
 * | - `message_default.pot`
 * | - `message_<scope>.pot`
 * | - `validation_default.pot`
 * | - `validation_<scope>.pot`
 * | - ...
 * - ...
 * }}}
 *
 * @see lithium\g11n\Locale
 * @link http://php.net/setlocale
 */
class Gettext extends \lithium\g11n\catalog\adapter\Base {

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'path'`: The path to the directory holding the data.
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('path' => null);
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
			throw new Exception("Gettext directory does not exist at `{$this->_config['path']}`");
		}
		if (!is_writable($this->_config['path'])) {
			throw new Exception("Gettext directory is not writable at `{$this->_config['path']}`");
		}
	}

	/**
	 * Reads data.
	 *
	 * @param string $category A category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return array|void
	 */
	public function read($category, $locale, $scope) {
		$files = $this->_files($category, $locale, $scope);

		foreach ($files as $file) {
			$method = '_parse' . ucfirst(pathinfo($file, PATHINFO_EXTENSION));

			if (!is_readable($file)) {
				continue;
			}
			$stream = fopen($file, 'rb');
			$data = $this->invokeMethod($method, array($stream));
			fclose($stream);

			if ($data) {
				return $data;
			}
		}
	}

	/**
	 * Writes data.
	 *
	 * @param string $category A category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @param mixed $data The data to write.
	 * @return boolean
	 */
	public function write($category, $locale, $scope, $data) {
		$files = $this->_files($category, $locale, $scope);

		foreach ($files as $file) {
			$method = '_compile' . ucfirst(pathinfo($file, PATHINFO_EXTENSION));

			if (!$stream = fopen($file, 'wb')) {
				return false;
			}
			$this->invokeMethod($method, array($stream, $data));
			fclose($stream);
		}
		return true;
	}

	/**
	 * Returns absolute paths to files according to configuration.
	 *
	 * @param string $category
	 * @param string $locale
	 * @param string $scope
	 * @return array
	 */
	protected function _files($category, $locale, $scope) {
		$path = $this->_config['path'];
		$scope = $scope ?: 'default';

		if (($pos = strpos($category, 'Template')) !== false) {
			$category = substr($category, 0, $pos);

			return array(
				"{$path}/{$category}_{$scope}.pot"
			);
		}

		if ($category == 'message') {
			$category = Inflector::pluralize($category);
		}
		$category = strtoupper($category);

		return array(
			"{$path}/{$locale}/LC_{$category}/{$scope}.mo",
			"{$path}/{$locale}/LC_{$category}/{$scope}.po"
		);
	}

	/**
	 * Parses portable object (PO) format.
	 *
	 * @param resource $stream
	 * @return array
	 */
	protected function _parsePo($stream) {
		$defaults = array(
			'id' => null,
			'ids' => array('singular' => null, 'plural' => null),
			'translated' => array(),
			'flags' => array(),
			'comments' => array(),
			'occurrences' => array()
		);
		extract($defaults);
		$data = array();

		while ($line = fgets($stream)) {
			$line = trim($line);

			if ($line === '') {
				continue;
			}

			if (preg_match('/^#\,\s(\w+)$/', $line, $matches)) {
				$flags[$matches[1]] = true;
			} elseif (preg_match('/^#\.\s(.+)$/', $line, $matches)) {
				$comments[] = $matches[1];
			} elseif (preg_match('/^#\:\s(.+):([0-9]+)$/', $line, $matches)) {
				$occurrences[] = array('file' => $matches[1], 'line' => $matches[2]);
			} elseif (preg_match('/^msgid\s"(.+)"$/', $line, $matches)) {
				if ($id) {
					$data = $this->_merge($data, compact(
						'id', 'ids', 'translated',
						'flags', 'occurrences', 'comments'
					));
					extract($defaults, EXTR_OVERWRITE);
				}
				$id = $ids['singular'] = $matches[1];
			} elseif (preg_match('/^msgid_plural\s"(.+)"$/', $line, $matches)) {
				$ids['plural'] = $matches[1];
			} elseif (preg_match('/^msgstr\s"(.+)"$/', $line, $matches)) {
				$translated[0] = $matches[1];
			} elseif (preg_match('/^msgstr\[(\d+)\]\s"(.+)"$/', $line, $matches)) {
				$translated[$matches[1]] = $matches[2];
			} elseif ($translated && preg_match('/^"(.+)"$/', $line, $matches)) {
				$translated[key($translated)] .= $matches[1];
			}
		}
		return $this->_merge($data, compact(
			'id', 'ids', 'translated',
			'flags', 'occurrences', 'comments'
		));
	}

	/**
	 * Parses portable object template (POT) format.
	 *
	 * @param resource $stream
	 * @return array
	 */
	protected function _parsePot($stream) {
		return $this->_parsePo($stream);
	}

	/**
	 * Parses machine object (MO) format, independent of the machine's endian it
	 * was created on. Both 32bit and 64bit systems are supported.
	 *
	 * @param resource $stream
	 * @return array
	 * @throws Exception If stream content has an invalid format.
	 */
	protected function _parseMo($stream) {
		$magic = unpack('V1', fread($stream, 4));
		$magic = substr(dechex(current($magic)), -8);

		if ($magic == '950412de') {
			$isBigEndian = false;
		} elseif ($magic == 'de120495') {
			$isBigEndian = true;
		} else {
			throw new Exception("MO stream content has an invalid format");
		}

		$header = array(
			'formatRevision' => null,
			'count' => null,
			'offsetId' => null,
			'offsetTranslated' => null,
			'sizeHashes' => null,
			'offsetHashes' => null
		);
		foreach ($header as &$value) {
			$value = $this->_readLong($stream, $isBigEndian);
		}
		extract($header);
		$data = array();

		for ($i = 0; $i < $count; $i++) {
			$singularId = $pluralId = null;
			$translated = array();

			fseek($stream, $offsetId + $i * 8);

			$length = $this->_readLong($stream, $isBigEndian);
			$offset = $this->_readLong($stream, $isBigEndian);

			if ($length < 1) {
				continue;
			}

			fseek($stream, $offset);
			$singularId = fread($stream, $length);

			if (strpos($singularId, "\000") !== false) {
				list($singularId, $pluralId) = explode("\000", $singularId);
			}

			fseek($stream, $offsetTranslated + $i * 8);
			$length = $this->_readLong($stream, $isBigEndian);
			$offset = $this->_readLong($stream, $isBigEndian);

			fseek($stream, $offset);
			$translated = fread($stream, $length);
			$translated = explode("\000", $translated);

			$data = $this->_merge($data, array(
				'id' => $singularId,
				'ids' => array('singular' => $singularId, 'plural' => $pluralId),
				'translated' => $translated
			));
		}
		return $data;
	}

	/**
	 * Reads an unsigned long from stream respecting endianess.
	 *
	 * @param resource $stream
	 * @param boolean $isBigEndian
	 * @return integer
	 */
	protected function _readLong($stream, $isBigEndian) {
		$result = unpack($isBigEndian ? 'N1' : 'V1', fread($stream, 4));
		$result = current($result);
		return (integer) substr($result, -8);
	}

	/**
	 * Compiles data into portable object (PO) format.
	 *
	 * To improve portability accross libraries the header is generated according
	 * to the format of the output of `xgettext`. This means using the same names for
	 * placeholders as well as including an empty fuzzy entry. The only difference
	 * in the header format is the initial header which just features one line of text.
	 *
	 * @param resource $stream
	 * @param array $data
	 * @return boolean
	 */
	protected function _compilePo($stream, $data) {
		$output[] = '# This file is distributed under the same license as the PACKAGE package.';
		$output[] = '#';
		$output[] = '#, fuzzy';
		$output[] = 'msgid ""';
		$output[] = 'msgstr ""';
		$output[] = '"Project-Id-Version: PACKAGE VERSION\n"';
		$output[] = '"POT-Creation-Date: YEAR-MO-DA HO:MI+ZONE\n"';
		$output[] = '"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"';
		$output[] = '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"';
		$output[] = '"Language-Team: LANGUAGE <EMAIL@ADDRESS>\n"';
		$output[] = '"MIME-Version: 1.0\n"';
		$output[] = '"Content-Type: text/plain; charset=CHARSET\n"';
		$output[] = '"Content-Transfer-Encoding: 8bit\n"';
		$output[] = '';
		$output = implode("\n", $output) . "\n";
		fwrite($stream, $output);

		foreach ($data as $key => $item) {
			$output = array();
			$item = $this->_prepareForWrite($item);

			foreach ($item['occurrences'] as $occurrence) {
				$output[] = "#: {$occurrence['file']}:{$occurrence['line']}";
			}
			foreach ($item['comments'] as $comment) {
				$output[] = "#. {$comment}";
			}
			foreach ($item['flags'] as $flag => $value) {
				$output[] = "#, {$flag}";
			}
			$output[] = "msgid \"{$item['ids']['singular']}\"";

			if (isset($item['ids']['plural'])) {
				$output[] = "msgid_plural \"{$item['ids']['plural']}\"";

				foreach ((array) $item['translated'] ?: array(null, null) as $key => $value) {
					$output[] = "msgstr[{$key}] \"{$value}\"";
				}
			} else {
				if (is_array($item['translated'])) {
					$value = array_pop($item['translated']);
				}
				$output[] = "msgstr \"{$item['translated']}\"";
			}
			$output[] = '';
			$output = implode("\n", $output) . "\n";
			fwrite($stream, $output);
		}
		return true;
	}

	/**
	 * Compiles data into portable object template (POT) format.
	 *
	 * @param resource $stream
	 * @param array $data
	 * @param array $meta
	 * @return boolean Success.
	 */
	protected function _compilePot($stream, $data) {
		return $this->_compilePo($stream, $data);
	}

	/**
	 * Compiles data into machine object (MO) format.
	 *
	 * @param resource $stream
	 * @param array $data
	 * @param array $meta
	 * @return void
	 */
	protected function _compileMo($stream, $data) {}

	/**
	 * Prepares an item before it is being written and escapes fields.
	 *
	 * @param mixed $item
	 * @return mixed
	 * @see lithium\g11n\catalog\adapter\Base::_prepareForWrite()
	 */
	protected function _prepareForWrite($item) {
		$filter = function ($value) use (&$filter) {
			if (is_array($value)) {
				return array_map($filter, $value);
			}
			$value = strtr($value, array("\\'" => "'", "\\\\" => "\\"));
			$value = str_replace("\r\n", "\n", $value);
			$value = addcslashes($value, "\0..\37\\\"");
			return $value;
		};
		$fields = array('id', 'ids', 'translated');

		foreach ($fields as $field) {
			if (isset($item[$field])) {
				$item[$field] = $filter($item[$field]);
			}
		}
		if (!isset($item['ids']['singular'])) {
			$item['ids']['singular'] =& $item['id'];
		}
		return parent::_prepareForWrite($item);
	}

	/**
	 * Merges an item into given data and unescapes fields.
	 *
	 * Please note that items with an id containing exclusively whitespace characters
	 * are **not** being merged. Whitespace characters are space, tab, vertical tab,
	 * line feed, carriage return and form feed.
	 *
	 * @param array $data Data to merge item into.
	 * @param array $item Item to merge into $data.
	 * @return array The merged data.
	 * @see lithium\g11n\catalog\adapter\Base::_merge()
	 */
	protected function _merge($data, $item) {
		$filter = function ($value) use (&$filter) {
			if (is_array($value)) {
				return array_map($filter, $value);
			}
			$value = stripcslashes($value);
			$value = ctype_space($value) ? null : $value;
			return $value;
		};
		$fields = array('id', 'ids', 'translated');

		foreach ($fields as $field) {
			if (isset($item[$field])) {
				$item[$field] = $filter($item[$field]);
			}
		}
        return parent::_merge($data, $item);
    }
}

?>