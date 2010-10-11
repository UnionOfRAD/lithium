<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\entity;

/**
 * `Record` class. Represents data such as a row from a database. Records have fields (often known
 * as columns in databases).
 */
class Record extends \lithium\data\Entity {

	/**
	 * Overloading for reading inaccessible properties.
	 *
	 * @param string $name Property name.
	 * @return mixed Result.
	 */
	public function &__get($name) {
		$data = null;
		$null  = null;

		if (isset($this->_relationships[$name])) {
			return $this->_relationships[$name];
		}

		if (($model = $this->_model) && $this->_handle) {
			foreach ($model::relations() as $relation => $config) {
				$linkKey = $config->data('fieldName');
				$type = $config->data('type') == 'hasMany' ? 'set' : 'entity';
				$class = $this->_classes[$type];

				if ($linkKey === $name) {
					$data = isset($this->_data[$name]) ? $this->_data[$name] : array();
					$this->_relationships[$name] = new $class();
					return $this->_relationships[$name];
				}
			}
		}
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		return $null;
	}
}

?>