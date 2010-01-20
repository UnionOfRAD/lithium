<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapter;

use \Exception;
use \SimpleXmlElement;
use \lithium\util\Inflector;
use \lithium\g11n\Locale;

/**
 * The `Cldr` class is an adapter which allows reading from the Common Locale Data Repository
 * maintained by the Unicode Consortium.
 *
 * The directory the `'path'` configuration setting is pointing to should contain the contents
 * from the CLDR as distributed with i.e. `core.zip` which can be downloaded from the unicode site.
 *
 * The directory as configured by the `'path'` setting which equals the `commons` directory
 * from `core.zip` should be structured according to the following example.
 *
 * {{{
 * | - `main`
 * | - `supplemental`
 * | - ...
 * }}}
 *
 * @link http://unicode.org/cldr
 * @link http://unicode.org/Public/cldr/1.7.0/core.zip
 */
class Cldr extends \lithium\g11n\catalog\adapter\Base {

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
			throw new Exception("Cldr directory does not exist at `{$this->_config['path']}`");
		}
	}

	/**
	 * Reads data.
	 *
	 * @param string $category A category. The following categories are supported:
	 *               - `'currency'`
	 *               - `'language'`
	 *               - `'script'`
	 *               - `'territory'`
	 *               - `'validation'`
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return array|void
	 */
	public function read($category, $locale, $scope) {
		if ($scope != $this->_config['scope']) {
			return null;
		}
		$path = $this->_config['path'];

		switch ($category) {
			case 'currency':
				$data = $this->_readCurrency($path, $locale);
			break;
			case 'language':
			case 'script':
			case 'territory':
				$data = $this->_readList($path, $category, $locale);
			break;
			case 'validation':
				$data = $this->_readValidation($path, $locale);
			break;
			default:
				return null;
		}
		return $data;
	}

	protected function _readValidation($path, $locale) {
		if (!$territory = Locale::territory($locale)) {
			return null;
		}
		$data = array();

		$file = "{$path}/supplemental/postalCodeData.xml";
		$query  = "/supplementalData/postalCodeData";
		$query .= "/postCodeRegex[@territoryId=\"{$territory}\"]";

		$nodes = $this->_parseXml($file, $query);
		$regex =  (string)current($nodes);

		return $this->_merge($data, array(
			'id' => 'postalCode',
			'translated' => "/^{$regex}$/"
		));
	}

	protected function _readList($path, $category, $locale) {
		$plural = Inflector::pluralize($category);

		$file = "{$path}/main/{$locale}.xml";
		$query = "/ldml/localeDisplayNames/{$plural}/{$category}";

		$nodes = $this->_parseXml($file, $query);
		$data = array();

		foreach ($nodes as $node) {
			$data = $this->_merge($data, array(
				'id' => (string)$node['type'],
				'translated' => (string)$node
			));
		}
		return $data;
	}

	protected function _readCurrency($path, $locale) {
		$file = "{$path}/main/{$locale}.xml";
		$query = "/ldml/numbers/currencies/currency";

		$nodes = $this->_parseXml($file, $query);
		$data = array();

		foreach ($nodes as $node) {
			$displayNames = $node->xpath('displayName');

			$data = $this->_merge($data, array(
				'id' => (string)$node['type'],
				'translated' => (string)current($displayNames)
			));
		}
		return $data;
	}

	/**
	 * Parses a XML file and retrieves data from it using an XPATH query
	 * and a given closure.
	 *
	 * @param string $file Absolute path to the XML file.
	 * @param string $query An XPATH query to select items.
	 * @return array
	 */
	protected function _parseXml($file, $query) {
		$document = new SimpleXmlElement($file, LIBXML_COMPACT, true);
		return $document->xpath($query);
	}
}

?>