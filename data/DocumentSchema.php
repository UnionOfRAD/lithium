<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data;

use lithium\core\Libraries;

class DocumentSchema extends \lithium\data\Schema {

	protected $_classes = [
		'entity' => 'lithium\data\entity\Document',
		'set'    => 'lithium\data\collection\DocumentSet'
	];

	protected $_handlers = [];

	public function __construct(array $config = []) {
		$this->_autoConfig[] = 'handlers';
		parent::__construct($config);
	}

	public function cast($object, $key, $data, array $options = []) {
		$defaults = [
			'parent' => null,
			'pathKey' => null,
			'model' => null,
			'wrap' => true,
			'asContent' => false,
			'first' => false
		];
		$options += $defaults;

		$basePathKey = $options['pathKey'];
		$classes = $this->_classes;

		$fieldName = is_int($key) ? null : $key;
		$pathKey = $basePathKey;

		if ($fieldName) {
			$pathKey = $basePathKey ? "{$basePathKey}.{$fieldName}" : $fieldName;
		}

		if ($data instanceof $classes['set'] || $data instanceof $classes['entity']) {
			return $data;
		}
		if (is_object($data) && !$this->is('array', $pathKey) && !$options['asContent']) {
			return $data;
		}
		return $this->_castArray($object, $data, $pathKey, $options, $defaults);
	}

	protected function _castArray($object, $val, $pathKey, $options, $defaults) {
		$isArray = (
			$this->is('array', $pathKey) &&
			!$options['asContent'] &&
			(!$object instanceof $this->_classes['set'])
		);

		$isObject = ($this->type($pathKey) === 'object');
		$valIsArray = is_array($val);
		$numericArray = false;
		$class = 'entity';

		if (!$valIsArray && !$isArray) {
			return $this->_castType($val, $pathKey);
		}

		if ($valIsArray) {
			$numericArray = !$val || array_keys($val) === range(0, count($val) - 1);
		}

		if ($isArray || ($numericArray && !$isObject)) {
			$val = $valIsArray ? $val : [$val];
			$class = 'set';
		}

		if ($options['wrap']) {
			$config = [
				'parent' => $options['parent'],
				'model' => (!$options['model'] && $object) ? $object->model() : $options['model'],
				'schema' => $this
			];
			$config += compact('pathKey') + array_diff_key($options, $defaults);

			if (!$pathKey && $model = $options['model']) {
				$exists = is_object($object) ? $object->exists() : false;
				$config += ['class' => $class, 'exists' => $exists, 'defaults' => false];
				$val = $model::create($val, $config);
			} else {
				$config['data'] = $val;
				$val = Libraries::instance(null, $class, $config, $this->_classes);
			}
		} elseif ($class === 'set') {
			$val = $val ?: [];
			foreach ($val as &$value) {
				$value = $this->_castType($value, $pathKey);
			}
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
		if ($this->is('null', $field) && ($value === null || $value === "")) {
			return null;
		}
		if (!is_scalar($value)) {
			return $value;
		}
		$type = $this->type($field);
		return isset($this->_handlers[$type]) ? $this->_handlers[$type]($value) : $value;
	}
}

?>