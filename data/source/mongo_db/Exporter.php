<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongo_db;

use lithium\util\Set;

class Exporter extends \lithium\core\StaticObject {

	protected static $_classes = array(
		'array' => 'lithium\data\collection\DocumentArray'
	);

	protected static $_commands = array(
		'create'    => null,
		'update'    => '$set',
		'increment' => '$inc',
		'remove'    => '$unset',
		'rename'    => '$rename'
	);

	protected static $_types = array(
		'MongoId'      => 'id',
		'MongoDate'    => 'date',
		'MongoCode'    => 'code',
		'MongoBinData' => 'binary',
		'datetime'     => 'date',
		'timestamp'    => 'date',
		'int'          => 'integer'
	);

	public static function get($type, $export, array $options = array()) {
		$defaults = array('whitelist' => array());
		$options += $defaults;

		if (!method_exists(get_called_class(), $method = "_{$type}") || !$export) {
			return;
		}
		return static::$method($export, array('finalize' => true) + $options);
	}

	public static function cast($data, $schema, $database, array $options = array()) {
		$defaults = array(
			'handlers' => array(),
			'model' => null,
			'arrays' => true,
			'pathKey' => null
		);
		$options += $defaults;

		foreach ($data as $key => $value) {
			$pathKey = $options['pathKey'] ? "{$options['pathKey']}.{$key}" : $key;

			$field = isset($schema[$pathKey]) ? $schema[$pathKey] : array();
			$field += array('type' => null, 'array' => null);
			$data[$key] = static::_cast($value, $field, $database, compact('pathKey') + $options);
		}
		return $data;
	}

	protected static function _cast($value, $def, $database, $options) {
		if (is_object($value)) {
			return $value;
		}
		$pathKey = $options['pathKey'];

		$typeMap = static::$_types;
		$type = isset($typeMap[$def['type']]) ? $typeMap[$def['type']] : $def['type'];

		$isObject = ($type == 'object');
		$isArray = (is_array($value) && $def['array'] !== false && !$isObject);
		$isArray = $def['array'] || $isArray;

		if (isset($options['handlers'][$type]) && $handler = $options['handlers'][$type]) {
			$value = $isArray ? array_map($handler, (array) $value) : $handler($value);
		}
		if (!$options['arrays']) {
			return $value;
		}

		if (!is_array($value) && !$def['array']) {
			return $value;
		}

		if ($def['array']) {
			$opts = array('class' => 'array') + $options;
			$value = ($value === null) ? array() : $value;
			$value = is_array($value) ? $value : array($value);
		} elseif (is_array($value)) {
			$arrayType = !$isObject && (array_keys($value) === range(0, count($value) - 1));
			$opts = $arrayType ? array('class' => 'array') + $options : $options;
		}

		unset($opts['handlers'], $opts['first']);
		return $database->item($options['model'], $value, compact('pathKey') + $opts);
	}

	public static function toCommand($changes) {
		$result = array();

		foreach (static::$_commands as $from => $to) {
			if (!isset($changes[$from])) {
				continue;
			}
			if (!$to) {
				$result = array_merge($result, $changes[$from]);
			}
			$result[$to] = $changes[$from];
		}
		unset($result['$set']['_id']);
		return $result;
	}

	protected static function _create($export, array $options) {
		$export += array('data' => array(), 'update' => array(), 'key' => '');
		$data = $export['update'];

		$result = array('create' => array());
		$localOpts = array('finalize' => false) + $options;

		foreach ($data as $key => $val) {
			if (is_object($val) && method_exists($val, 'export')) {
				$data[$key] = static::_create($val->export($options), $localOpts);
			}
		}
		return ($options['finalize']) ? array('create' => $data) : $data;
	}

	/**
	 * Calculates changesets for update operations, and produces an array which can be converted to
	 * a set of native MongoDB update operations.
	 *
	 * @todo Implement remove and rename.
	 * @param array $export An array of fields exported from a call to `Document::export()`, and
	 *              should contain the following keys:
	 *              - `'data'` _array_: An array representing the original data loaded from the
	 *                 database for the document.
	 *              - `'update'` _array_: An array representing the current state of the document,
	 *                containing any modifications made.
	 *              - `'key'` _string_: If this is a nested document, this is a dot-separated path
	 *                from the root-level document.
	 * @return array Returns an array representing the changes to be made to the document. These
	 *         are converted to database-native commands by the `toCommand()` method.
	 */
	protected static function _update($export) {
		$export += array(
			'data' => array(),
			'update' => array(),
			'remove' => array(),
			'rename' => array(),
			'key' => ''
		);
		$path = $export['key'] ? "{$export['key']}." : "";
		$result = array('update' => array(), 'remove' => array());
		$left = static::_diff($export['data'], $export['update']);
		$right = static::_diff($export['update'], $export['data']);

		$objects = array_filter($export['update'], function($value) {
			return (is_object($value) && method_exists($value, 'export'));
		});

		array_map(function($key) use (&$left) { unset($left[$key]); }, array_keys($right));
		foreach ($left as $key => $value) {
			$result = static::_append($result, "{$path}{$key}", $value, 'remove');
		}
		$data = (array) $right + (array) $objects;

		foreach ($data as $key => $value) {
			$original = $export['data'];
			$isArray = is_object($value) && get_class($value) == static::$_classes['array'];
			if ($isArray && isset($original[$key]) && $value->data() != $original[$key]->data()) {
				$value = $value->data();
			}
			if ($isArray && !isset($original[$key])) {
				 $value = $value->data();
			}
			$result = static::_append($result, "{$path}{$key}", $value, 'update');
		}
		return array_filter($result);
	}

	/**
	 * Handle diffing operations between `Document` object states. Implemented because all of PHP's
	 * array comparison functions are broken when working with objects.
	 *
	 * @param array $left The left-hand comparison array.
	 * @param array $right The right-hand comparison array.
	 * @return array Returns an array of the differences of `$left` compared to `$right`.
	 */
	protected static function _diff($left, $right) {
		$result = array();

		foreach ($left as $key => $value) {
			if (!isset($right[$key]) || $left[$key] !== $right[$key]) {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Handles appending nested objects to document changesets.
	 *
	 * @param array $changes The full set of changes to be made to the database.
	 * @param string $key The key of the field to append, which may be a dot-separated path if the
	 *               value is or is contained within a nested object.
	 * @param mixed $value The value to append to the changeset. Can be a scalar value, array, a
	 *              nested object, or part of a nested object.
	 * @param string $change The type of change, as to whether update/remove or rename etc.
	 * @return array Returns the value of `$changes`, with any new changed values appended.
	 */
	protected static function _append($changes, $key, $value, $change) {
		$options = array('finalize' => false);

		if (!is_object($value) || !method_exists($value, 'export')) {
			$changes[$change][$key] = ($change == 'update') ? $value : true;
			return $changes;
		}
		if ($value->exists()) {
			if ($change == 'update') {
				$export = $value->export();
				$export['key'] = $key;
				return Set::merge($changes, static::_update($export));
			}

			$children = static::_update($value->export());
			if (!empty($children)) {
				return Set::merge($changes, $children);
			}
			$changes[$change][$key] = true;
			return $changes;
		}
		$changes['update'][$key] = static::_create($value->export(), $options);
		return $changes;
	}
}

?>