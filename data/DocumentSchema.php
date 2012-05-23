<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data;

use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use MongoBinData;

class DocumentSchema extends \lithium\data\Schema {

	protected $_classes = array(
		'entity' => 'lithium\data\entity\Document',
		'array' => 'lithium\data\collection\DocumentArray'
	);

	protected $_handlers = array();

	protected function _init() {
		$this->_autoConfig[] = 'handlers';
		parent::_init();
	}

	public function cast($object, $data, array $options = array()) {
		$defaults = array('pathKey' => null, 'model' => null, 'wrap' => true, 'first' => false);
		$options += $defaults;

		$basePathKey = $options['pathKey'];
		$model = (!$options['model'] && $object) ? $object->model() : $options['model'];
		$classes = $this->_classes;

		if (is_scalar($data) || !$data) {
			return $this->_castType($data, $basePathKey);
		}

		foreach ($data as $key => $val) {
			$fieldName = is_int($key) ? null : $key;

			if($fieldName) {
				$pathKey = $basePathKey ? "{$basePathKey}.{$fieldName}" : $fieldName;
			} else {
				$pathKey = $basePathKey;	
			}

			if ($val instanceof $classes['array'] || $val instanceof $classes['entity']) {
				continue;
			}
			if ((is_object($val) || is_object($data)) && !$this->is('array', $pathKey)) {
				continue;
			}
			$data[$key] = $this->_castArray($object, $val, $pathKey, $options, $defaults);
		}
		return $data;
	}

	protected function _castArray($object, $val, $pathKey, $options, $defaults) {
		$isArray = $this->is('array', $pathKey) && (!$object instanceof $this->_classes['array']);
		$isObject = ($this->type($pathKey) == 'object');
		$valIsArray = is_array($val);
		$numericArray = false;
		$class = 'entity';

		if (!$valIsArray && !$isArray) {
			return $this->_castType($val, $pathKey);
		}

		if ($valIsArray) {
			$numericArray = !$val || array_keys($val) === range(0, count($val) - 1);
		}

		if (($isArray && !$isObject) || $numericArray) {
			if ($val) {
				$val = $valIsArray ? $val : array($val);
				$keys = array_fill(0, count($val), $pathKey);
				$val = array_map(array(&$this, '_castType'), $val, $keys);
			} else {
				$val = array();
			}
			$class = 'array';
		}

		if ($options['wrap']) {
			$config = array('data' => $val, 'model' => $options['model'], 'schema' => $this);
			$config += compact('pathKey') + array_diff_key($options, $defaults);
			$val = $this->_instance($class, $config);
		}
		return $val;
	}

	/**
	 * Casts a scalar (non-object/array) value to its corresponding database-native value or custom
	 * value object based on a handler assigned to `$field`'s data type.
	 *
	 * @param mixed $value The value to be cast.
	 * @param string $field The name of the field that `$value` is or will be stored in. If it is a
	 *               nested field, `$field` should be the full dot-separated path to the
	 *               sub-object's field.
	 * @return mixed Returns the result of `$value`, modified by a matching handler data type
	 *               handler, if available.
	 */
	protected function _castType($value, $field) {
		if (!is_scalar($value)) {
			return $value;
		}
		$type = $this->type($field);
		return isset($this->_handlers[$type]) ? $this->_handlers[$type]($value) : $value;
	}
}

?>