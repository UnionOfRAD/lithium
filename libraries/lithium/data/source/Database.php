<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use \lithium\util\String;
use \lithium\util\Inflector;
use \InvalidArgumentException;

abstract class Database extends \lithium\data\Source {

	/**
	 * The supported column types and their default values
	 *
	 * @var array
	 */
	protected $_columns = array(
		'string' => array('length' => 255)
	);

	/**
	 * Strings used to render the given statement
	 *
	 * @see \lithium\data\source\Database::renderCommand()
	 * @var string
	 */
	protected $_strings = array(
		'read' => "
			SELECT {:fields} From {:table} {:joins} {:conditions} {:group} {:order} {:limit};
			{:comment}
		",
		'create' => "INSERT INTO {:table} ({:fields}) VALUES ({:values});{:comment}",
		'update' => "UPDATE {:table} SET {:fields} {:conditions};{:comment}",
		'delete' => "DELETE {:flags} From {:table} {:aliases} {:conditions};{:comment}",
		'schema' => "CREATE TABLE {:table} (\n{:columns}{:indexes});{:comment}",
		'join'   => "{:type} JOIN {:table} ON {:constraint}"
	);

	/**
	 * Classes used by `Database`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'record' => '\lithium\data\model\Record',
		'recordSet' => '\lithium\data\collection\RecordSet'
	);

	/**
	 * A pair of opening/closing quote characters used for quoting identifiers in SQL queries.
	 *
	 * @var array
	 */
	protected $_quotes = array();

	/**
	 * Getter/Setter for the connection's encoding
	 * Abstract. Must be defined by child class.
	 *
	 * @param mixed $encoding
	 * @return mixed.
	 */
	abstract public function encoding($encoding = null);

	/**
	 * Handle the result return from the
	 * Abstract. Must be defined by child class.
	 *
	 * @param string $type next|close The current step in the iteration.
	 * @param mixed $resource The result resource returned from the database.
	 * @param \lithium\data\model\Query $context The given query.
	 * @return void
	 */
	abstract public function result($type, $resource, $context);

	/**
	 * Return the last errors produced by a the execution of a query.
 	 * Abstract. Must be defined by child class.
 	 *
	 */
	abstract public function error();

	/**
	 * Execute a given query
 	 * Abstract. Must be defined by child class.
 	 *
 	 * @see \lithium\data\source\Database::renderCommand()
	 * @param string $sql The sql string to execute
	 * @return resource
	 */
	abstract protected function _execute($sql);

	/**
	 * Get the last insert id from the database.
	 * Abstract. Must be defined by child class.
	 *
	 * @param \lithium\data\model\Query $context The given query.
	 * @return void
	 */
	abstract protected function _insertId($query);

	/**
	 * Creates the database object and set default values for it.
	 *
	 * Options defined:
	 *  - 'database' _string_ Name of the database to use. Defaults to 'lithium'.
	 *  - 'host' _string_ Name/address of server to connect to. Defaults to 'localhost'.
	 *  - 'login' _string_ Username to use when connecting to server. Defaults to 'root'.
	 *  - 'password' _string_ Password to use when connecting to server. Defaults to none.
	 *  - 'persistent' _boolean_ If true a persistent connection will be attempted, provided the
	 *    adapter supports it. Defaults to `true`.
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
			'database'   => null,
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Field name handler to ensure proper escaping.
	 *
	 * @param string $name
	 * @return string
	 */
	public function name($name) {
		$open  = reset($this->_quotes);
		$close = next($this->_quotes);
		return $name ? "{$open}{$name}{$close}" : null;
	}

	/**
	 * Converts a given value into the proper type based on a given schema definition.
	 *
	 * @see \lithium\data\source\Database::schema()
	 * @param mixed $value The value to be converted. Arrays will be recursively converted.
	 * @param array $schema Formatted array from `\lithium\data\source\Database::schema()`
	 * @return mixed value with converted type
	 */
	public function value($value, array $schema = array()) {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = $this->value($val, $schema);
			}
			return $value;
		}
		if ($value === null) {
			return 'NULL';
		}
		switch ($type = isset($schema['type']) ? $schema['type'] : $this->_introspectType($value)) {
			case 'boolean':
				return $this->_toBoolean($value);
			case 'float':
				return floatval($value);
			case 'integer':
				return intval($value);
		}
		return "'{$value}'";
	}

	/**
	 * Inserts a new record into the database based on a the `Query`. The record is updated
	 * with the id of the insert.
	 *
	 * @param object $query A `\lithium\data\model\Query` object
	 * @param array $options none
	 * @return boolean
	 * @filter
	 */
	public function create($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];
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
			if ($self->invokeMethod('_execute', array($sql))) {
				$id = null;

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
	 * Reads records from a database using a `\lithium\data\model\Query` object or raw SQL string.
	 *
	 * @param string|object $query `\lithium\data\model\Query` object or sql string
	 * @param string $options
	 *               - `return` : switch return between `'array'`, `'item'`, or `'resource'`.
	 *               default: `item`. Requires a `Query` object
	 * @return mixed Determined by `$options['return'].
	 * @filter
	 */
	public function read($query, array $options = array()) {
		$defaults = array('return' => 'item');
		$options += $defaults;

		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];
			$options = $params['options'];

			$sql = is_string($query) ? $query : $self->renderCommand($query);
			$result = $self->invokeMethod('_execute', array($sql));

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
				case 'item':
					return $self->item($query->model(), array(), compact('query', 'result') + array(
						'class' => 'recordSet',
						'handle' => $self,
					));
			}
		});
	}

	/**
	 * Updates a record in the database based on the given `Query`.
	 *
	 * @param object $query A `\lithium\data\model\Query` object
	 * @param array $options none
	 * @return boolean
	 */
	public function update($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];
			$model = $query->model();
			$data = $query->export($self);
			$schema = (array) $model::schema();
			$fields = array();

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

	/**
	 * Deletes a record in the database based on the given `Query`.
	 *
	 * @param object $query A `\lithium\data\model\Query` object
	 * @param array $options none
	 * @return boolean
	 */
	public function delete($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			extract($params);
			$data = $query->export($self);

			if (!$data['conditions']) {
				return false;
			}
			$sql = $self->renderCommand('delete', $data, $query);
			return (boolean) $self->invokeMethod('_execute', array($sql));
		});
	}

	/**
	 * Defines or modifies the default settings of a relationship between two models.
	 *
	 * @param string $class
	 * @param string $type
	 * @param string $name
	 * @param array $options
	 * @return array Returns an array containing the configuration for a model relationship.
	 */
	public function relationship($class, $type, $name, array $options = array()) {
		$key = Inflector::underscore($type == 'belongsTo' ? $name : $class::meta('name'));
		$defaults = array(
			'type' => $type,
			'class' => null,
			'fields' => true,
			'key' => $key . '_id'
		);
		$options += $defaults;

		if (!$options['class']) {
			$assoc = preg_replace("/\\w+$/", "", $class) . $name;
			$options['class'] = class_exists($assoc) ? $assoc : Libraries::locate('models', $assoc);
		}
		return $options + $defaults;
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
		$class = $this->_classes[isset($options['class']) ? $options['class'] : 'record'];
		return new $class(compact('model', 'data') + $options);
	}

	/**
	 * Returns a given `type` statement for the given data, rendered from the Database::$_strings
	 *
	 * @param string $type create|read|update|delete|join
	 * @param string $data The data to replace in the string
	 * @param string $context
	 * @return string
	 */
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

	/**
	 * Builds an array of keyed on the fully-namespaced `Model` with array of fields as values
	 * for the given `Query`
	 *
	 * @param object $query A `\lithium\data\model\Query` object
	 * @param string $resource
	 * @param string $context
	 * @return void
	 */
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

	/**
	 * Returns a string of formatted conditions to be inserted into the query statement
	 *
	 * @param string|array $conditions The conditions for this query.
	 * @param object $context The current `\lithium\data\model\Query`.
	 * @param array $options
	 *               - `prepend` : added before WHERE clause
	 * @return void
	 */
	public function conditions($conditions, $context, array $options = array()) {
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

	/**
	 * Returns
	 *
	 * @param string $fields
	 * @param string $context
	 * @return void
	 */
	public function fields($fields, $context) {
		switch ($context->type()) {
			case 'create':
			case 'update':
				return $fields ?: $context->data();
			default:
				return empty($fields) ? '*' : join(', ', $fields);
		}
	}

	/**
	 * Returns a LIMIT statement from the given limit and the offset of the context object.
	 *
	 * @param integer $limit An
	 * @param object $context The `\lithium\data\model\Query` object
	 * @return string
	 */
	public function limit($limit, $context) {
		if (empty($limit)) {
			return;
		};
		if ($offset = $context->offset() ?: '') {
			$offset .= ', ';
		}
		return "LIMIT {$offset}{$limit}";
	}

	/**
	 * Returns a join statement for given array of query objects
	 *
	 * @param object|array $joins A single or array of `\lithium\data\model\Query` objects
	 * @param object $context The parent `\lithium\data\model\Query` object
	 * @return string
	 */
	public function joins($joins, $context) {
		$result = null;
		foreach ((array) $joins as $join) {
			$result .= $this->renderCommand('join', $join->export($this));
		}
		return $result;
	}

	/**
	 * Return formatted clause for order.
	 *
	 * @param mixed $order The `order` clause to be formatted
	 * @param object $context
	 * @return mixed Formatted `order` clause.
	 */
	public function order($order, $context) {
		$direction = 'ASC';
		$model = $context->model();

		if (is_string($order)) {
			if ($model::schema($order)) {
				$order = array($order => $direction);
			} elseif (!preg_match('/\s+(A|DE)SC/i', $order)) {
				return "ORDER BY {$order} {$direction}";
			} else {
				return "ORDER BY {$order}";
			}
		}

		if (is_array($order)) {
			$result = array();

			foreach ($order as $column => $dir) {
				if (is_int($column)) {
					$column = $dir;
					$dir = $direction;
				}
				if (!in_array($dir, array('ASC', 'asc', 'DESC', 'desc'))) {
					$dir = $direction;
				}
				if ($field = $model::schema($column)) {
					$name = $this->name($model::meta('name')) . '.' . $this->name($column);
					$result[] = "{$name} {$dir}";
				}
			}
			$order = join(', ', $result);
			return "ORDER BY {$order}";
		}
	}

	/**
	 * Adds formatting to SQL comments before they're embedded in queries.
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment) {}

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
		return (boolean) $value;
	}
}

?>