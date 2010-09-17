<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

use lithium\core\Libraries;
use lithium\util\Inflector;

/**
 * The `Relationship` class encapsulates the data and functionality necessary to link two model
 * classes together.
 */
class Relationship extends \lithium\core\Object {

	/**
	 * A relationship linking type defined by one document or record (or multiple) being embedded
	 * within another.
	 */
	const LINK_EMBEDDED = 'embedded';

	/**
	 * The reciprocal of `LINK_EMBEDDED`, this defines a linking type wherein an embedded document
	 * references the document that contains it.
	 */
	const LINK_CONTAINED = 'contained';

	/**
	 * A one-to-one or many-to-one relationship in which a key contains an ID value linking to
	 * another document or record.
	 */
	const LINK_KEY = 'key';

	/**
	 * A many-to-many relationship in which a key contains an embedded array of IDs linking to other
	 * records or documents.
	 */
	const LINK_KEY_LIST = 'keylist';

	/**
	 * A relationship defined by a database-native reference mechanism, linking a key to an
	 * arbitrary record or document in another data collection or entirely separate database.
	 */
	const LINK_REF = 'ref';

	public function __construct(array $config = array()) {
		$defaults = array(
			'name' => null,
			'keys' => array(),
			'type' => null,
			'to'   => null,
			'from' => null,
			'link' => self::LINK_KEY,
			'fields' => true,
			'fieldName' => null,
			'conditions' => null,
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		$config = $this->_config;
		$singularName = $config['name'];

		if ($config['type'] == 'hasMany') {
			$singularName = Inflector::singularize($config['name']);
		}
		if (!$config['to']) {
			$assoc = preg_replace("/\\w+$/", "", $config['from']) . $singularName;
			$config['to'] = class_exists($assoc) ? $assoc : Libraries::locate('models', $assoc);
		}
		if (!$config['fieldName']) {
			$config['fieldName'] = lcfirst($config['name']);
		}
		$config['keys'] = $this->_keys($config['keys'], $config);
		$this->_config = $config;
	}

	public function data($key = null) {
		if (!$key) {
			return $this->_config;
		}
		return isset($this->_config[$key]) ? $this->_config[$key] : null;
	}

	public function __get($name) {
		return $this->data($name);
	}

	protected function _keys($keys, $config) {
		if (!($related = ($config['type'] == 'belongsTo') ? $config['to'] : $config['from'])) {
			return array();
		}
		return array_combine((array) $keys, (array) $related::key());
	}
}

?>