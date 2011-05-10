<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

use MongoId;
use MongoDate;

class MockDocumentSource extends \lithium\data\Source {

	protected $_classes = array(
		'entity' => 'lithium\data\entity\Document',
		'array' => 'lithium\data\collection\DocumentArray',
		'set' => 'lithium\data\collection\DocumentSet',
		'relationship' => 'lithium\data\model\Relationship'
	);

	public function connect() {}
	public function disconnect() {}
	public function sources($class = null) {}
	public function describe($entity, array $meta = array()) {}
	public function create($query, array $options = array()) {}
	public function update($query, array $options = array()) {}
	public function delete($query, array $options = array()) {}

	protected $point = 0;
	protected $result = null;

	public function read($query = null, array $options = array()) {
		$this->point = 0;
		$this->result = array(
			array('id' => 1, 'name' => 'Joe'),
			array('id' => 2, 'name' => 'Moe'),
			array('id' => 3, 'name' => 'Roe')
		);
	}

	public function getNext() {
		return $this->result[$this->point++];
	}

	public function cast($entity, array $data, array $options = array()) {
		$defaults = array('schema' => null, 'first' => false, 'pathKey' => null, 'arrays' => true);
		$options += $defaults;
		$model = null;

		if (!$data) {
			return $data;
		}

		if ($entity && !$options['schema']) {
			$options['schema'] = $entity->schema() ?: array('_id' => array('type' => 'id'));
		}
		if ($entity) {
			$model = $entity->model();
		}
		$schema = $options['schema'];
		unset($options['schema']);

		$handlers = array(
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				$v = is_numeric($v) ? intval($v) : strtotime($v);
				return (time() == $v) ? new MongoDate() : new MongoDate($v);
			},
			'regex'   => function($v) { return new MongoRegex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code'    => function($v) { return new MongoCode($v); },
			'binary'  => function($v) { return new MongoBinData($v); }
		);

		$typeMap = array(
			'MongoId'      => 'id',
			'MongoDate'    => 'date',
			'MongoCode'    => 'code',
			'MongoBinData' => 'binary',
			'datetime'     => 'date',
			'timestamp'    => 'date',
			'int'          => 'integer'
		);

		foreach ($data as $key => $value) {
			if (is_object($value)) {
				continue;
			}
			$path = is_int($key) ? null : $key;
			$path = $options['pathKey'] ? trim("{$options['pathKey']}.{$path}", '.') : $path;
			$field = (isset($schema[$path]) ? $schema[$path] : array());
			$field += array('type' => null, 'array' => null);
			$type = isset($typeMap[$field['type']]) ? $typeMap[$field['type']] : $field['type'];
			$isObject = ($type == 'object');
			$isArray = (is_array($value) && $field['array'] !== false && !$isObject);

			if (isset($handlers[$type])) {
				$handler = $handlers[$type];
				$value = $isArray ? array_map($handler, $value) : $handler($value);
			}
			if (!$options['arrays']) {
				$data[$key] = $value;
				continue;
			}
			$pathKey = $path;

			if (is_array($value)) {
				$arrayType = !$isObject && (array_keys($value) === range(0, count($value) - 1));
				$opts = $arrayType ? array('class' => 'array') + $options : $options;
				$value = $this->item($model, $value, compact('pathKey') + $opts);
			} elseif ($field['array']) {
				$opts = array('class' => 'array') + $options;
				$value = $this->item($model, array($value), compact('pathKey') + $opts);
			}
			$data[$key] = $value;
		}
		return $options['first'] ? reset($data) : $data;
	}

	public function result($type, $resource, $context) {
		switch ($type) {
			case 'next':
				$result = $resource->hasNext() ? $resource->getNext() : null;
			break;
			case 'close':
				unset($resource);
				$result = null;
				break;
		}
		return $result;
	}

	public function relationship($class, $type, $name, array $options = array()) {
		$keys = Inflector::camelize($type == 'belongsTo' ? $name : $class::meta('name'));

		$options += compact('name', 'type', 'keys');
		$options['from'] = $class;

		$relationship = $this->_classes['relationship'];
		return new $relationship($options);
	}
}

?>