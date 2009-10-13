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

use \lithium\util\Set;

abstract class Base extends \lithium\core\Object {

	protected $_categories = array(
		'inflection' => array(
			'plural'            => array('read' => false, 'write' => false),
			'singular'          => array('read' => false, 'write' => false),
			'uninflectedPlural' => array('read' => false, 'write' => false),
			'irregularPluar'    => array('read' => false, 'write' => false),
			'transliteration'   => array('read' => false, 'write' => false),
			'template'          => array('read' => false, 'write' => false)
		),
		'list'       => array(
			'language'          => array('read' => false, 'write' => false),
			'script'            => array('read' => false, 'write' => false),
			'territory'         => array('read' => false, 'write' => false),
			'timezone'          => array('read' => false, 'write' => false),
			'currency'          => array('read' => false, 'write' => false),
			'template'          => array('read' => false, 'write' => false)
		),
		'message'    => array(
			'page'              => array('read' => false, 'write' => false),
			'plural'            => array('read' => false, 'write' => false),
			'direction'         => array('read' => false, 'write' => false),
			'template'          => array('read' => false, 'write' => false)
		),
		'validation' => array(
			'phone'             => array('read' => false, 'write' => false),
			'postalCode'        => array('read' => false, 'write' => false),
			'ssn'               => array('read' => false, 'write' => false),
			'template'          => array('read' => false, 'write' => false)
	));

	protected function _init() {
		parent::_init();
		$properties = get_class_vars(__CLASS__);
		$this->_categories = Set::merge($properties['_categories'], $this->_categories);
	}

	public function isSupported($category, $operation) {
		$category = explode('.', $category, 2);
		return $this->_categories[$category[0]][$category[1]][$operation];
	}

	/**
	 * Reads data.
	 *
	 * @param string $category For a list of all valid categories {@see $_categories}.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current request.
	 * @return mixed
	 */
	abstract public function read($category, $locale, $scope);

	/**
	 * Writes data.  Existing data is silently overwritten.
	 *
	 * @param string $category For a list of all valid categories {@see $_categories}.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current request.
	 * @param mixed $data The data to write.
	 * @return void
	 */
	abstract public function write($category, $locale, $scope, $data);

	protected function _formatMessageItem($key, $value) {
		if (!is_array($value) || !isset($value['translated'])) {
			return array('singularId' => $key, 'translated' => (array)$value);
		}
		return $value;
	}

	/**
	 * Merges a message item into given data.
	 *
	 * @param array $data Data to merge item into.
	 * @param array $item Item to merge into $data.
	 * @return void
	 */
	protected function _mergeMessageItem(&$data, $item) {
		$id = $item['singularId'];

		$defaults = array(
			'singularId' => null,
			'pluralId' => null,
			'translated' => array(),
			'fuzzy' => false,
			'comments' => array(),
			'occurrences' => array()
		);
		$item += $defaults;

		if (!isset($data[$id])) {
			$data[$id] = $item;
			return;
		}

		if ($data[$id]['pluralId'] === null) {
			$data[$id]['singularId'] = $item['singularId'];
			$data[$id]['pluralId'] = $item['pluralId'];
			$data[$id]['translated'] += $item['translated'];
		}
		if ($data[$id]['fuzzy'] === false) {
			$data[$id]['fuzzy'] = $item['fuzzy'];
		}
		$data[$id]['comments'] = array_merge($data[$id]['comments'], $item['comments']);
		$data[$id]['occurrences'] = array_merge($data[$id]['occurrences'], $item['occurrences']);
	}
}

?>