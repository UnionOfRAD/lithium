<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\catalog\adapters;

/**
 * The `Memory` class is an adapter for reading and writing data during runtime.  Written data
 * is stored in memory and lost after the end of the excution. The adapter is also very useful
 * for testing.
 */
class Memory extends \lithium\g11n\catalog\adapters\Base {

	/**
	 * Supported categories.
	 *
	 * @var array
	 */
	protected $_categories = array(
		'inflection' => array(
			'plural'            => array('read' => true, 'write' => true),
			'singular'          => array('read' => true, 'write' => true),
			'uninflectedPlural' => array('read' => true, 'write' => true),
			'irregularPluar'    => array('read' => true, 'write' => true),
			'transliteration'   => array('read' => true, 'write' => true),
			'template'          => array('read' => true, 'write' => true)
		),
		'list'       => array(
			'language'          => array('read' => true, 'write' => true),
			'script'            => array('read' => true, 'write' => true),
			'territory'         => array('read' => true, 'write' => true),
			'timezone'          => array('read' => true, 'write' => true),
			'currency'          => array('read' => true, 'write' => true),
			'template'          => array('read' => true, 'write' => true)
		),
		'message'    => array(
			'page'              => array('read' => true, 'write' => true),
			'plural'            => array('read' => true, 'write' => true),
			'direction'         => array('read' => true, 'write' => true),
			'template'          => array('read' => true, 'write' => true)
		),
		'validation' => array(
			'phone'             => array('read' => true, 'write' => true),
			'postalCode'        => array('read' => true, 'write' => true),
			'ssn'               => array('read' => true, 'write' => true),
			'template'          => array('read' => true, 'write' => true)
	));

	/**
	 * Holds data during runtime.
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Reads data.
	 *
	 * @param string $category Dot-delimited category.
	 * @param string $locale A locale identifier.
	 * @param string $scope The scope for the current operation.
	 * @return mixed
	 */
	public function read($category, $locale, $scope) {
		if (isset($this->_data[$scope][$category][$locale])) {
			return $this->_data[$scope][$category][$locale];
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
		switch ($category) {
			case 'message.page':
			case 'message.template':
				foreach ($data as $key => $item) {
					$item = $this->_formatMessageItem($key, $item);
					$this->_mergeMessageItem($this->_data[$scope][$category][$locale], $item);
				}
			break;
			default:
				if (is_array($data)) {
					if (!isset($this->_data[$scope][$category][$locale])) {
						$this->_data[$scope][$category][$locale] = array();
					}
					$this->_data[$scope][$category][$locale] += $data;
				} else {
					$this->_data[$scope][$category][$locale] = $data;
				}
			break;
		}
		return true;
	}
}

?>