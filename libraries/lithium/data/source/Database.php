<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use \lithium\util\String;
use \InvalidArgumentException;

abstract class Database extends \lithium\data\Source {

	protected $_columns = array();

	protected $_strings = array(
		'read' => "
			SELECT {:fields} From {:table}
			{:joins} {:conditions} {:group} {:order} {:limit};{:comment}
		",
		'create' => "INSERT INTO {:table} ({:fields}) VALUES ({:values});{:comment}",
		'update' => "UPDATE {:table} SET {:fields} {:conditions};{:comment}",
		'delete' => "DELETE {:flags} From {:table} {:aliases} {:conditions};{:comment}",
		'schema' => "CREATE TABLE {:table} (\n{:columns}{:indexes});{:comment}",
		'join'   => "{:type} JOIN {:table} {:constraint}"
	);

	protected $_classes = array(
		'record' => '\lithium\data\model\Record',
		'recordSet' => '\lithium\data\collection\RecordSet'
	);

	/**
	 * Abstract. Must be defined by child class.
	 * Getter/Setter for the connection's encoding
	 *
	 * @param mixed $encoding
	 * @return mixed.
	 */
	abstract public function encoding($encoding = null);

	/**
	 * Abstract. Must be defined by child class.
	*/
	abstract public function result($type, $resource, $context);

	/**
	 * Abstract. Must be defined by child class.
	*/
	abstract public function error();

	/**
	 * Creates the database object and set default values for it.
	 *
	 * Options defined:
	 *  - 'database' _string_ Name of the database to use. Defaults to 'lithium'.
	 *  - 'host' _string_ Name/address of server to connect to. Defaults to 'localhost'.
	 *  - 'login' _string_ Username to use when connecting to server. Defaults to 'root'.
	 *  - 'password' _string_ Password to use when connecting to server. Defaults to none.
	 *  - 'persistent' _boolean_ If true a persistent connection will be attempted, provided the
	 *    adapter supports it. Defaults to true.
	 *
	 * @param $config array Array of configuration options.
	 * @return Database object.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'persistent' => true,
			'host'       => 'localhost',
			'login'      => 'root',
			'password'   => '',
			'database'   => 'lithium',
		);
		parent::__construct((array) $config + $defaults);
	}

	public function name($name) {
		return $name;
	}

	public function value($value, array $schema = array()) {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = $this->value($val, $schema);
			}
		}
		return $value;
	}

	public function create($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			extract($params);

			$model = $query->model();
			$fields = $values = array();
			$data = $query->export($self);
			$schema = (array) $model::schema();

			while (list($field, $value) = each($data['fields'])) {
				$schema += array($field => array('default' => null));
				if ($value === null && $schema[$field]['default'] === null) {
					continue;
				}
				$fields[] = $self->name($field);
				$values[] = $self->value($value, $schema[$field]);
			}
			$fields = join(', ', $fields);
			$values = join(', ', $values);
			$sql = $self->renderCommand('create', compact('fields', 'values') + $data, $query);
			$id = null;

			if ($self->invokeMethod('_execute', array($sql))) {
				if (!$model::key($query->record())) {
					$id = $self->invokeMethod('_insertId', array($query));
				}
				$query->record()->update($id);
				return true;
			}
			return false;
		});
	}

	/**
	 * Reads records from a database using a `Query` object or raw SQL string.
	 *
	 * @param string $query
	 * @param string $options
	 * @return void
	 */
	public function read($query, array $options = array()) {
		$defaults = array('return' => 'resource');
		$options += $defaults;
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) {
			extract($params);

			if (!is_string($query)) {
				$query = $self->renderCommand($query);
			}
			$result = $self->invokeMethod('_execute', array($query));

			switch ($options['return']) {
				case 'resource':
					return $result;
				case 'array':
					$columns = $self->schema($query, $result);
					$records = array();

					while ($data = $self->result('next', $result, null)) {
						$records[] = array_combine($columns, $data);
					}
					$self->result('close', $result, null);
					return $records;
			}
		});
	}

	public function update($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			extract($params);

			$fields = array();
			$model = $query->model();
			$data = $query->export($self);
			$schema = (array) $model::schema();

			while (list($field, $value) = each($data['fields'])) {
				$schema += array($field => array());
				$fields[] = $self->name($field) . ' = ' . $self->value($value, $schema[$field]);
			}
			$fields = join(', ', $fields);
			$sql = $self->renderCommand('update', compact('fields') + $data, $query);

			if ($self->invokeMethod('_execute', array($sql))) {
				$query->record()->update();
				return true;
			}
			return false;
		});
	}

	public function delete($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			extract($params);
			$data = $query->export($self);

			if (!$data['conditions']) {
				return false;
			}
			$sql = $self->renderCommand('delete', $data, $query);
			return (bool) $self->invokeMethod('_execute', array($sql));
		});
	}

	/**
	 * Returns a newly-created `Record` object, bound to a model and populated with default data
	 * and options.
	 *
	 * @param string $model A fully-namespaced class name representing the model class to which the
	 *               `Record` object will be bound.
	 * @param array $data The default data with which the new `Record` should be populated.
	 * @param array $options Any additional options to pass to the `Record`'s constructor.
	 * @return object Returns a new, un-saved `Record` object bound to the model class specified in
	 *         `$model`.
	 */
	public function item($model, array $data = array(), array $options = array()) {
		$class = $this->_classes['record'];
		return new $class(compact('model', 'data') + $options);
	}

	public function renderCommand($type, $data = null, $context = null) {
		if (is_object($type)) {
			$context = $type;
			$data = $context->export($this);
			$type = $context->type();
		}
		if (!isset($this->_strings[$type])) {
			throw new InvalidArgumentException("Invalid query type '{$type}'");
		}
		$data = array_filter($data);
		return trim(String::insert($this->_strings[$type], $data, array('clean' => true)));
	}

	public function schema($query, $resource = null, $context = null) {
		$model = $query->model();
		$fields = $query->fields();
		$relations = $model::relations();
		$result = array();

		$ns = function($class) use ($model) {
			static $namespace;
			$namespace = $namespace ?: preg_replace('/\w+$/', '', $model);
			return "{$namespace}{$class}";
		};

		if (empty($fields)) {
			return array($model => array_keys($model::schema()));
		}

		foreach ($fields as $scope => $field) {
			switch (true) {
				case (is_numeric($scope) && $field == '*'):
					$result[$model] = array_keys($model::schema());
				break;
				case (is_numeric($scope) && in_array($field, $relations)):
					$scope = $field;
				case (in_array($scope, $relations, true) && $field == '*'):
					$scope = $ns($scope);
					$result[$scope] = array_keys($scope::schema());
				break;
				case (in_array($scope, $relations)):
					$result[$scope] = $fields;
				break;
			}
		}
		return $result;
	}

	public function conditions($conditions, $context, $options = array()) {
		$defaults = array('prepend' => true);
		$options += $defaults;
		$model = $context->model();
		$schema = $model ? $model::schema() : array();

		switch (true) {
			case empty($conditions):
				return '';
			case is_string($conditions):
				return ($options['prepend']) ? "WHERE {$conditions}" : $conditions;
			case !is_array($conditions):
				return null;
		}

		$result = array();
		$boolean = 'AND';

		foreach ($conditions as $key => $value) {
			$schema[$key] = isset($schema[$key]) ? $schema[$key] : array();

			switch (true) {
				case (is_numeric($key) && is_string($value)):
					$result[] = $value;
				break;
				case (is_string($key) && is_object($value)):
					$value = trim(rtrim($this->renderCommand($value), ';'));
					$result[] = "{$key} IN ({$value})";
				break;
				case (is_string($key) && is_array($value)):
					$value = join(', ', $this->value($value, $schema[$key]));
					$result[] = "{$key} IN ({$value})";
				break;
				default:
					$value = $this->value($value, $schema[$key]);
					$result[] = "{$key} = {$value}";
				break;
			}
		}
		$result = join(" {$boolean} ", $result);
		return ($options['prepend'] && !empty($result)) ? "WHERE {$result}" : $result;
	}

	public function fields($fields, $context) {
		switch ($context->type()) {
			case 'create':
			case 'update':
				return $fields ?: $context->data();
			default:
				return empty($fields) ? '*' : join(', ', $fields);
		}
	}

	public function limit($limit, $context) {
		if (empty($limit)) {
			return '';
		}
		$result = '';

		if ($offset = $context->offset() ?: '') {
			$offset .= ', ';
		}
		return "LIMIT {$offset}{$limit}";
	}

	public function order($order, $context) {
		if (is_string($order) && strpos($order, ',') && !preg_match('/\(.+\,.+\)/', $order)) {
			$order = array_map('trim', explode(',', $order));
		}
		$order = (is_array($order) ? array_filter($order) : $order);

		if (empty($order)) {
			return '';
		}

		if (is_array($keys)) {
			$keys = (Set::countDim($keys) > 1) ? array_map(array(&$this, 'order'), $keys) : $keys;

			foreach ($keys as $key => $value) {
				if (is_numeric($key)) {
					$key = $value = ltrim(str_replace('ORDER BY ', '', $this->order($value)));
					$value = (!preg_match('/\\x20ASC|\\x20DESC/i', $key) ? ' ' . $direction : '');
				} else {
					$value = ' ' . $value;
				}

				if (!preg_match('/^.+\\(.*\\)/', $key) && !strpos($key, ',')) {
					if (preg_match('/\\x20ASC|\\x20DESC/i', $key, $dir)) {
						$dir = $dir[0];
						$key = preg_replace('/\\x20ASC|\\x20DESC/i', '', $key);
					} else {
						$dir = '';
					}
					$key = trim($key);
					if (!preg_match('/\s/', $key)) {
						$key = $this->name($key);
					}
					$key .= ' ' . trim($dir);
				}
				$order[] = $this->order($key . $value);
			}
			return ' ORDER BY ' . trim(str_replace('ORDER BY', '', join(',', $order)));
		}
		$keys = preg_replace('/ORDER\\x20BY/i', '', $keys);

		if (strpos($keys, '.')) {
			preg_match_all(
				'/([a-zA-Z0-9_]{1,})\\.([a-zA-Z0-9_]{1,})/', $keys, $result, PREG_PATTERN_ORDER
			);
			$pregCount = count($result[0]);

			for ($i = 0; $i < $pregCount; $i++) {
				if (!is_numeric($result[0][$i])) {
					$keys = preg_replace(
						'/' . $result[0][$i] . '/', $this->name($result[0][$i]), $keys
					);
				}
			}
			$result = ' ORDER BY ' . $keys;
			return $result . (!preg_match('/\\x20ASC|\\x20DESC/i', $keys) ? ' ' . $direction : '');

		} elseif (preg_match('/(\\x20ASC|\\x20DESC)/i', $keys, $match)) {
			$direction = $match[1];
			return ' ORDER BY ' . preg_replace('/' . $match[1] . '/', '', $keys) . $direction;
		}
		return ' ORDER BY ' . $keys . ' ' . $direction;
	}

	/**
	 * Adds formatting to SQL comments before they're embedded in queries.
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment) {
	}

	abstract protected function _execute($query);

	abstract protected function _insertId($query);

	/**
	 * Adds formatting to SQL comments before they're embedded in queries.
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment) {
	}

	abstract protected function _execute($query);

	abstract protected function _insertId($query);

	/**
	 * Returns a fully-qualified table name (i.e. with prefix), quoted.
	 *
	 * @param string $entity
	 * @return string
	 */
	protected function _entityName($entity) {
		return $this->name($entity);
	}

	/**
	 * Attempts to automatically determine the column type of a value. Used by the `value()` method
	 * of various database adapters to determine how to prepare a value if the schema is not
	 * specified.
	 *
	 * @param mixed $value The value to be prepared for an SQL query.
	 * @return string Returns the name of the column type which `$value` most likely belongs to.
	 */
	protected function _introspectType($value) {
		switch (true) {
			case (is_bool($value)):
				return 'boolean';
			case (is_float($value) || preg_match('/^\d+\.\d+$/', $value)):
				return 'float';
			case (is_int($value) || preg_match('/^\d+$/', $value)):
				return 'integer';
			case (is_string($value) && strlen($value) <= $this->_columns['string']['length']):
				return 'string';
			default:
				return 'text';
		}
	}

	/**
	 * Casts a value which is being written or compared to a boolean-type database column.
	 *
	 * @param mixed $value A value of unknown type to be cast to boolean. Numeric values not equal
	 *              to zero evaluate to `true`, otherwise `false`. String values equal to `'true'`,
	 *              `'t'` or `'T'` evaluate to `true`, all others to `false`. In all other cases,
	 *               uses PHP's default casting.
	 * @return boolean Returns a boolean representation of `$value`, based on the comparison rules
	 *         specified above. Database adapters may override this method if boolean type coercion
	 *         is required and falls outside the rules defined.
	 */
	protected function _toBoolean($value) {
		if (is_bool($value)) {
			return $value;
		}
		if (is_int($value) || is_float($value)) {
			return ($value !== 0);
		}
		if (is_string($value)) {
			return ($value == 't' || $value == 'T' || $value == 'true');
		}
		return (bool) $value;
	}
}

?>