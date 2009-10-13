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

namespace lithium\g11n\catalog\adapters;

use \Exception;
use \lithium\util\String;

class Gettext extends \lithium\g11n\catalog\adapters\Base {

	protected $_categories = array(
		'message' => array(
			'page' => array('read' => true, 'write' => true),
			'template' => array('read' => true, 'write' => true)
	));

	public function __construct($config = array()) {
		$defaults = array('path' => null);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		if (!is_dir($this->_config['path'])) {
			throw new Exception("Gettext directory does not exist at `{$this->_config['path']}`");
		}
	}

	public function read($category, $locale, $scope) {
		$files = $this->_files($category, $locale, $scope);

		foreach ($files as $file) {
			$method = '_parse' . ucfirst(pathinfo($file, PATHINFO_EXTENSION));

			if (!$stream = fopen($file, 'rb')) {
				continue;
			}
			$data = $this->invokeMethod($method, array($stream));
			fclose($stream);

			if ($data) {
				return $data;
			}
		}
	}

	/**
	 * @todo readd meta data support
	 */
	public function write($category, $locale, $scope, $data) {
		$files = $this->_files($category, $locale, $scope);

		foreach ($files as $file) {
			$method = '_compile' . ucfirst(pathinfo($file, PATHINFO_EXTENSION));

			if (!$stream = fopen($file, 'wb')) {
				return false;
			}
			$this->invokeMethod($method, array($stream, $data, array()));
			fclose($stream);
		}
		return true;
	}

	/**
	 * Returns absolute paths to files according to configuration.
	 *
	 * @param string $category
	 * @param string|void $locale
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
				$singularId = stripcslashes($matches[1]);
			} elseif (preg_match('/^msgid_plural\s"(.+)"$/', $line, $matches)) {
				$pluralId = stripcslashes($matches[1]);
			} elseif (preg_match('/^msgstr\s"(.+)"$/', $line, $matches)) {
				$translated[0] = stripcslashes($matches[1]);
			} elseif (preg_match('/^msgstr\[(\d+)\]\s"(.+)"$/', $line, $matches)) {
				$translated[$matches[1]] = stripcslashes($matches[2]);
			} elseif ($translated && preg_match('/^"(.+)"$/', $line, $matches)) {
				$translated[key($translated)] .= stripcslashes($matches[1]);
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
	 * Parses machine object (MO) format.
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
		return (integer)substr($result, -8);
	}

	/**
	 * Compiles data into portable object (PO) format.
	 *
	 * @param resource $stream
	 * @param array $data
	 * @param array $meta
	 * @return boolean
	 */
	protected function _compilePo($stream, $data, $meta) {
		$defaults =  array(
			'locale' => 'LOCALE',
			'package' => 'NAME',
			'packageVersion' => 'VERSION',
			'copyright' => 'NAME',
			'copyrightYear' => 'YEAR',
			'copyrightEmail' => 'EMAIL',
			'templateCreationDate' => 'DATE',
			'revisionDate' => 'DATE',
			'lastTranslator' => 'NAME',
			'lastTranslatorEmail' => 'EMAIL',
			'reportBugsTo' => 'EMAIL',
			'languageTeamEmail' => 'EMAIL',
			'pluralFormNumber' => 'NUMBER',
			'pluralFormRule' => 'EXPRESSION',
			'mimeVersion' => '1.0',
			'contentType' => 'text/plain',
			'contentTypeCharset' => 'UTF-8',
			'contentTypeEncoding' => '8bit',
		);
		$meta += $defaults;

		$output = array();
		$output[] = '# {:locale} translation of {:package} messages.';
		$output[] = '# Copyright {:copyrightYear} {:copyright} <{:copyrightEmail}>';
		$output[] = '# This file is distributed under the same license as the {:package} package.';
		$output[] = '#';
		$output[] = '"Project-Id-Version: {:package} {:packageVersion}\n"';
		$output[] = '"POT-Creation-Date: {:templateCreationDate}\n"';
		$output[] = '"PO-Revision-Date: {:revisionDate}\n"';
		$output[] = '"Last-Translator: {:lastTranslator} <{:lastTranslatorEmail}>\n"';
		$output[] = '"Language-Team: {:locale} <{:languageTeamEmail}>\n"';
		$output[] = '"MIME-Version: {:mimeVersion}\n"';
		$output[] = '"Content-Type: {:contentType}; charset={:contentTypeCharset}\n"';
		$output[] = '"Content-Transfer-Encoding: {:contentTypeEncoding}\n"';
		$output[] = '"Plural-Forms: nplurals={:pluralFormNumber}; plural={:pluralFormRule};\n"';
		$output[] = '';
		$output = String::insert(implode("\n", $output) . "\n", $meta);
		fwrite($stream, $output);

		foreach ($data as $key => $item) {
			$output = array();
			$item = $this->_formatMessageItem($key, $item);

			foreach ($item['occurrences'] as $occurrence) {
				$output[] = '#: ' . $occurrence['file'] . ':' . $occurrence['line'];
			}
			foreach ($item['comments'] as $comment) {
				$output[] = '#. ' . $comment;
			}
			if ($item['fuzzy']) {
				$output[] = '#, fuzzy';
			}

			$output[] = 'msgid "' . $item['singularId'] . '"';

			if (!isset($item['pluralId'])) {
				$output[] = 'msgstr "' . $item['translated'] . '"';
			} else {
				$output[] = 'msgid_plural "' . $item['pluralId'] . '"';

				foreach ($item['translated'] as $key => $value) {
					$output[] = 'msgstr[' . $key . '] "' . $value . '"';
				}
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
	protected function _compilePot($stream, $data, $meta) {
		return $this->_compilePo($stream, $data, $meta);
	}

	/**
	 * Compiles data into machine object (MO) format.
	 *
	 * @param resource $stream
	 * @param array $data
	 * @param array $meta
	 * @return void
	 */
	protected function _compileMo($stream, $data, $meta) {}
}

?>