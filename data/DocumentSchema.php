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
		$defaults = array(
			'pathKey' => null,
			'model' => null,
			'database' => null,
			'wrap' => true
		);
		$options += $defaults;
		$basePathKey = $options['pathKey'];
		$model = (!$options['model'] && $object) ? $object->model() : $options['model'];
		$database = $options['database'];
		$classes = $this->_classes;

		if (is_scalar($data) || !$data) {
			return $this->_castType($data, $basePathKey);
		}

		if ($model && !($database = $options['database'])) {
			$database = $model::connection();
		}

		foreach ($data as $key => $val) {
			if ($val instanceof $classes['array'] || $val instanceof $classes['entity']) {
				continue;
			}
			$pathKey = $basePathKey ? $basePathKey . '.' . $key : $key;
			$isArray = $this->is('array', $pathKey);
			$valIsArray = is_array($val);

			if ((is_object($val) || is_object($data)) && !$isArray) {
				continue;
			}
			if (!$valIsArray && !$isArray) {
				$data[$key] = $this->_castType($val, $pathKey);
				continue;
			}
			$numericArray = false;

			if ($valIsArray) {
				$numericArray = !$val || array_keys($val) === range(0, count($val) - 1);
			}
			$options['class'] = 'entity';

			if ($isArray || $numericArray) {
				if ($val) {
					$val = $valIsArray ? $val : array($val);
					$keys = array_fill(0, count($val), $pathKey);
					$val = array_map(array(&$this, '_castType'), $val, $keys);
				}
				$options['class'] = 'array';
			}
			unset($options['first']);

			if ($database && $options['wrap']) {
				$config = compact('pathKey') + array_diff_key($options, $defaults);
				$val = $database->item($options['model'], $val, $config);
			}
			$data[$key] = $val;
		}
		return $data;
	}

	protected function _castType($val, $field) {
		if (!is_scalar($val)) {
			return $val;
		}
		$type = $this->type($field);
		return isset($this->_handlers[$type]) ? $this->_handlers[$type]($val) : $val;
	}
}

?>