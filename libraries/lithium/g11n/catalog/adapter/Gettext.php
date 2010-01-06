<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapter;

use \Exception;
use \lithium\util\String;

/**
 * The `Gettext` class is an adapter for reading and writing PO and MO files without the
 * requirement of having the gettext extension enabled or installed.  Moreover it doesn't
 * require the usage of the non thread safe `setlocale()`.
 *
 * The adapter expects a directory configured by the path options to be structured
 * according to the following example.
 *
 * {{{
 * | - `<path>`: This is the configured path.
 *   | - `<locale>`: The directory for the well-formed <locale> i.e `'fr' or `'en_US'`.
 *   | | - `LC_MESSAGES`: The directory for the message category.
 *   |   | - `default.po`: The PO file.
 *   |   | - `default.mo`: The MO file.
 *   |   | - `<scope>.po`: The PO file for <scope>.
 *   |   | - `<scope>.mo`: The MO file for <scope>.
 *   | - `message_default.pot`: The message template.
 *   | - `message_<scope>.pot`: The message template for <scope>.
 * }}}
 *
 * @see lithium\g11n\Locale
 * @link http://php.net/setlocale
 */
class Gettext extends \lithium\g11n\catalog\adapter\Base {

	/**
	 * Supported categories.
	 *
	 * @var array
	 */
	protected $_categories = array(
		'message' => array(
			'page' => array('read' => true, 'write' => true),
			'template' => array('read' => true, 'write' => true)
	));

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
	}

	/**
	 * Reads data.
	 *
	 * @param string $category Dot-delimited category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return mixed
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
	 * @param string $category Dot-delimited category.
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
		$files = array();
		$scope = $scope ?: 'default';

		switch ($category) {
			case 'message.page':
				$files[] = "{$path}/{$locale}/LC_MESSAGES/{$scope}.mo";
				$files[] = "{$path}/{$locale}/LC_MESSAGES/{$scope}.po";
			break;
			case 'message.template':
				$files[] = "{$path}/message_{$scope}.pot";
			break;
		}
		return $files;
	}

	/**
	 * Parses portable object (PO) format.
	 *
	 * @param resource $stream
	 * @return array
	 */
	protected function _parsePo($stream) {
		$defaults = array(
			'singularId' => null,
			'pluralId' => null,
			'translated' => array(),
			'fuzzy' => false,
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

			if (preg_match('/^#\,\sfuzzy$/', $line)) {
				$fuzzy = true;
			} elseif (preg_match('/^#\.\s(.+)$/', $line, $matches)) {
				$comments[] = $matches[1];
			} elseif (preg_match('/^#\:\s(.+):([0-9]+)$/', $line, $matches)) {
				$occurrences[] = array('file' => $matches[1], 'line' => $matches[2]);
			} elseif (preg_match('/^msgid\s"(.+)"$/', $line, $matches)) {
				if ($singularId) {
					$this->_mergeMessageItem($data, compact(
						'singularId', 'pluralId', 'translated',
						'fuzzy', 'occurrences', 'comments'
					));
					extract($defaults, EXTR_OVERWRITE);
				}
				$singularId = $matches[1];
			} elseif (preg_match('/^msgid_plural\s"(.+)"$/', $line, $matches)) {
				$pluralId = $matches[1];
			} elseif (preg_match('/^msgstr\s"(.+)"$/', $line, $matches)) {
				$translated[0] = $matches[1];
			} elseif (preg_match('/^msgstr\[(\d+)\]\s"(.+)"$/', $line, $matches)) {
				$translated[$matches[1]] = $matches[2];
			} elseif ($translated && preg_match('/^"(.+)"$/', $line, $matches)) {
				$translated[key($translated)] .= $matches[1];
			}
		}
		$this->_mergeMessageItem($data, compact(
			'singularId', 'pluralId', 'translated',
			'fuzzy', 'occurrences', 'comments'
		));
		return $data;
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

			if (strpos($translated, "\000") !== false) {
				$translated = explode("\000", $translated);
			} else {
				$translated = array($translated);
			}

			$this->_mergeMessageItem($data,  compact(
				'singularId', 'pluralId', 'translated'
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
			$item = $this->_formatMessageItem($key, $item);

			foreach ($item['occurrences'] as $occurrence) {
				$output[] = "#: {$occurrence['file']}:{$occurrence['line']}";
			}
			foreach ($item['comments'] as $comment) {
				$output[] = "#. {$comment}";
			}
			if ($item['fuzzy']) {
				$output[] = "#, fuzzy";
			}

			$output[] = "msgid \"{$item['singularId']}\"";

			if (isset($item['pluralId'])) {
				$output[] = "msgid_plural \"{$item['pluralId']}\"";

				foreach ($item['translated'] ?: array(null, null) as $key => $value) {
					$output[] = "msgstr[{$key}] \"{$value}\"";
				}
			} else {
				$value = array_pop($item['translated']);
				$output[] = "msgstr \"{$value}\"";
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
	 * Formats a message item if neccessary and escapes fields.
	 *
	 * @param string $key The potential message ID.
	 * @param string|array $value The message value.
	 * @return array Message item formatted into internal/verbose format.
	 * @see lithium\g11n\catalog\adapter\Base::_formatMessageItem()
	 */
	protected function _formatMessageItem($key, $value) {
		$escape = function ($value) use (&$escape) {
			if (is_array($value)) {
				return array_map($escape, $value);
			}
			$value = strtr($value, array("\\'" => "'", "\\\\" => "\\"));
			$value = str_replace("\r\n", "\n", $value);
			$value = addcslashes($value, "\0..\37\\\"");
			return $value;
		};
		$fields = array('singularId', 'pluralId', 'translated');
		$item = parent::_formatMessageItem($key, $value);

		foreach ($fields as $field) {
			if (isset($item[$field])) {
				$item[$field] = $escape($item[$field]);
			}
		}
		return $item;
	}

	/**
	 * Merges a message item into given data and unescapes fields.
	 *
	 * @param array $data Data to merge item into.
	 * @param array $item Item to merge into $data.
	 * @return void
	 * @see lithium\g11n\catalog\adapter\Base::_mergeMessageItem()
	 */
	protected function _mergeMessageItem(&$data, $item) {
		$unescape = function ($value) use (&$unescape) {
			if (is_array($value)) {
				return array_map($unescape, $value);
			}
			return stripcslashes($value);
		};
		$fields = array('singularId', 'pluralId', 'translated');

		foreach ($fields as $field) {
			if (isset($item[$field])) {
				$item[$field] = $unescape($item[$field]);
			}
		}
        return parent::_mergeMessageItem($data, $item);
    }
}

?>