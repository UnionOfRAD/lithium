<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongo_db;

use MongoId;
use MongoCode;
use MongoDate;
use MongoRegex;
use MongoBinData;

class Schema extends \lithium\data\Schema {

	protected $_handlers = array();

	protected $_types = array(
		'MongoId' => 'id',
		'MongoDate' => 'date',
		'MongoCode' => 'code',
		'MongoBinData' => 'binary',
		'datetime' => 'date',
		'timestamp' => 'date',
		'int' => 'integer'
	);

	public function __construct(array $config = array()) {
		$defaults = array('fields' => array('_id' => array('type' => 'id')));
		parent::__construct(array_filter($config) + $defaults);
	}

	protected function _init() {
		$this->_autoConfig[] = 'handlers';
		parent::_init();

		$this->_handlers += array(
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				$v = is_numeric($v) ? intval($v) : strtotime($v);
				return (!$v || time() == $v) ? new MongoDate() : new MongoDate($v);
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code'    => function($v) { return new MongoCode($v); },
			'binary'  => function($v) { return new MongoBinData($v); }
		);
	}

	public function cast($object, $data, array $options = array()) {
		$defaults = array(
			'pathKey' => null, 'model' => null, 'schema' => $this, 'database' => null
		);
		$options += $defaults;
		$basePathKey = $options['pathKey'];
		if (is_scalar($data)) {
			return $this->_castType($data, $basePathKey);
		}

		$model = method_exists($object, 'model') ? $object->model() : null;
		$model = $model ? $model : $options['model'];
		$database = $model ? $model::connection() : $options['database'];

		foreach ($data as $key => $val) {
			if (is_object($val)) {
				continue;
			}
			$pathKey = $basePathKey ? $basePathKey . '.' . $key : $key;
			$isArray = $this->is('array', $pathKey) || $this->type($pathKey) == 'array';
			if (is_array($val) || $isArray) {
				$val = (array) $val;
				$numericArray = !$val || array_keys($val) === range(0, count($val) - 1);
				$options['class'] = 'entity';
				if (($isArray && $numericArray) || $numericArray) {
					$options['class'] = 'array';
				}
				unset($options['first']);
				$val = $database->item($options['model'], $val, compact('pathKey') + $options);
				$data[$key] = $val;
				continue;
			}
			$data[$key] = $this->_castType($val, $pathKey);
		}
		return $data;
	}

	protected function _castType($val, $pathKey) {
		if (!is_scalar($val)) {
			return $val;
		}
		$type = $this->type($pathKey);
		$type = isset($this->_types[$type]) ? $this->_types[$type] : $type;
		$handler = isset($this->_handlers[$type]) ? $this->_handlers[$type] : null;
		return $handler ? $handler($val) : $val;
	}
}

?>