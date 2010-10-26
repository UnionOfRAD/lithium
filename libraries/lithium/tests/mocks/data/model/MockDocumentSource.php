<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

use MongoId;
use MongoDate;
use lithium\data\model\Relationship;

class MockDocumentSource extends \lithium\data\Source {

	protected $_classes = array(
		'entity' => 'lithium\data\entity\Document',
		'array' => 'lithium\data\collection\DocumentArray',
		'set' => 'lithium\data\collection\DocumentSet',
		'relationship' => 'lithium\data\model\Relationship'
	);

	public function connect() {}
	public function disconnect() {}
	public function entities($class = null) {}
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

	public function cast($model, array $data, array $options = array()) {
		$defaults = array('schema' => null, 'first' => false, 'pathKey' => null, 'arrays' => true);
		$options += $defaults;

		if ($model && !$options['schema']) {
			$options['schema'] = $model::schema();
		}

		$handlers = array(
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new MongoId($v) : $v;
			},
			'date' => function($v) {
				return new MongoDate(is_numeric($v) ? intval($v) : strtotime($v));
			},
			'regex' => function($v) {
				return new MongoRegex($v);
			}
		);

		$typeMap = array(
			'MongoId'   => 'id',
			'MongoDate' => 'date',
			'datetime'  => 'date',
			'timestamp' => 'date',
		);

		foreach ($data as $key => $value) {
			if (is_object($value)) {
				continue;
			}
			$schema = isset($options['schema'][$key]) ? $options['schema'][$key] : array();
			$schema += array('type' => null, 'array' => null);
			$type = isset($typeMap[$schema['type']]) ? $typeMap[$schema['type']] : $schema['type'];
			$isArray = (is_array($value) && $schema['array'] !== false);

			if (isset($handlers[$type])) {
				$handler = $handlers[$type];
				$value = $isArray ? array_map($handler, $value) : $handler($value);
			}
			if ($options['arrays']) {
				if (is_array($value)) {
					$arrayType = (array_keys($value) === range(0, count($value) - 1));
					$options = $arrayType ? array('class' => 'array') + $options : $options;
					$value = $this->item($model, $value, $options);
				} elseif ($schema['array']) {
					$value = $this->item($model, array($value), array('class' => 'array') + $options);
				}
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