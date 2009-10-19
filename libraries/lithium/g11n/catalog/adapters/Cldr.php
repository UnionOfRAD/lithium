<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapters;

use \Exception;
use \SimpleXmlElement;
use \lithium\util\Inflector;
use \lithium\g11n\Locale;

class Cldr extends \lithium\g11n\catalog\adapters\Base {

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

	public function __construct($config = array()) {
		$defaults = array('path' => null, 'scope' => null);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		if (!is_dir($this->_config['path'])) {
			throw new Exception("Cldr directory does not exist at `{$this->_config['path']}`");
		}
	}

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

	protected function _parseXml($file, $query, $yield, $post) {
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

	public function write($category, $locale, $scope, $data) {}
}

?>