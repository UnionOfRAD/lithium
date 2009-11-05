<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapters;

use \Exception;
use \SimpleXmlElement;
use \lithium\util\Inflector;
use \lithium\g11n\Locale;

/**
 * The `Cldr` class is an adapter which allows reading from the Common Locale Data Repository
 * maintained by the Unicode Consortium. Writing and deleting is not supported.
 */
class Cldr extends \lithium\g11n\catalog\adapters\Base {

	/**
	 * Supported categories.
	 *
	 * @var array
	 */
	protected $_categories = array(
		'validation' => array(
			'postalCode' => array('read' => true)
		),
		'lists' => array(
			'language' => array('read' => true),
			'script' => array('read' => true),
			'territory' => array('read' => true),
			'currency' => array('read' => true)
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
			throw new Exception("Cldr directory does not exist at `{$this->_config['path']}`");
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
		if ($scope !== $this->_config['scope']) {
			return null;
		}
		$path = $this->_config['path'];
		$file = $query = $yield = $post = null;

		switch ($category) {
			case 'validation.postalCode':
				if (!$territory = Locale::territory($locale)) {
					return null;
				}
				$file = "{$path}/supplemental/postalCodeData.xml";
				$query  = "/supplementalData/postalCodeData";
				$query .= "/postCodeRegex[@territoryId=\"{$territory}\"]";

				$yield = function($nodes) {
					return (string)current($nodes);
				};
				$post =	function($data) {
					return "/^{$data}$/";
				};
			break;
			case 'list.language':
			case 'list.script':
			case 'list.territory':
				list(, $singular) = explode('.', $category, 2);
				$plural = Inflector::pluralize($singular);

				$file = "{$path}/main/{$locale}.xml";
				$query = "/ldml/localeDisplayNames/{$plural}/{$singular}";

				$yield = function($nodes) {
					$data = null;

					foreach ($nodes as $node) {
						$data[(string)$node['type']] = (string)$node;
					}
					return $data;
				};
			break;
			case 'list.currency':
				$file = "{$path}/main/{$locale}.xml";
				$query = "/ldml/numbers/currencies/currency";

				$yield = function($nodes) {
					$data = null;

					foreach ($nodes as $node) {
						$displayNames = $node->xpath('displayName');
						$data[(string)$node['type']] = (string)current($displayNames);
					}
					return $data;
				};
			break;
		}
		return $this->_parseXml($file, $query, $yield, $post);
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
	 * Parses a XML file and retrieves data from it using an XPATH query
	 * and a given closure.
	 *
	 * @param string $file Absolute path to the XML file.
	 * @param string $query An XPATH query to select items.
	 * @param callback $yield A closure which is passed the data from the XPATH query.
	 * @param callback $post A closure for applying formatting to the yielded results.
	 * @return mixed
	 */
	protected function _parseXml($file, $query, $yield, $post = null) {
		$document = new SimpleXmlElement($file, LIBXML_COMPACT, true);
		$nodes = $document->xpath($query);

		if (!$data = $yield($nodes)) {
			return null;
		}
		if ($post) {
			return $post($data);
		}
		return $data;
	}
}

?>