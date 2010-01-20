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

	protected $_queries = array(
		'select' => "
			SELECT {:fields} From {:table}
			{:joins} {:conditions} {:group} {:order} {:limit};{:comment}
		",
		'create' => "INSERT INTO {:table} ({:fields}) VALUES ({:values});{:comment}",
		'update' => "UPDATE {:table} SET {:fields} {:conditions};{:comment}",
		'delete' => "DELETE {:flags} From {:table} {:aliases} {:conditions};{:comment}",
		'schema' => "CREATE TABLE {:table} (\n{:columns}{:indexes});{:comment}",
		'join'   => "{:type} JOIN {:table} {:constraint}"
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
	public function __construct($config = array()) {
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

	public function value($value) {
		return $value;
	}

	public function create($record, $options = array()) {

	}

	/**
	 * Reads records from a database using a `Query` object or raw SQL string.
	 *
	 * @param string $query
	 * @param string $options
	 * @return void
	 */
	public function read($query, $options = array()) {
		$defaults = array('return' => 'resource');
		$options += $defaults;
		$params = compact('query', 'options');

		return $this->_filter(__METHOD__, $params, function($self, $params, $chain) {
			extract($params);

			if (!is_string($query)) {
				$query = $self->renderCommand('select', $query->export($self), $query);
			}
			$result = $self->invokeMethod('_execute', array($query));

			switch ($options['return']) {
				case 'resource':
				break;
				case 'array':
					$columns = $self->columns($query, $result);
					$records = array();

					while ($data = $self->result('next', $result, null)) {
						$records[] = array_combine($columns, $data);
					}
					$self->result('close', $result, null);
					$result = $records;
				break;
			}
			return $result;
		});
	}

	public function update($query, $options) {

	}

	public function delete($query, $options) {

	}

	public function renderCommand($type, $data, $context) {
		if (!isset($this->_queries[$type])) {
			throw new InvalidArgumentException("Invalid query type '{$type}'");
		}
		return String::insert($this->_queries[$type], $data, array('clean' => true));
	}

	public function columns($query, $resource = null, $context = null) {
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

	public function conditions($conditions, $context) {
		if (empty($conditions)) {
			return '';
		}
	}

	public function fields($fields, $context) {
		return empty($fields) ? '*' : join(', ', $fields);
	}

	public function limit($limit, $context) {
		if (empty($limit)) {
			return '';
		}
		$result = '';
		$offset = $context->offset() ?: '';

		if (!empty($offset)) {
			$offset .= ', ';
		}
		return "LIMIT {$offset}{$limit}";
	}

	function order($order, $context) {
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
			preg_match_all('/([a-zA-Z0-9_]{1,})\\.([a-zA-Z0-9_]{1,})/', $keys, $result, PREG_PATTERN_ORDER);
			$pregCount = count($result[0]);

			for ($i = 0; $i < $pregCount; $i++) {
				if (!is_numeric($result[0][$i])) {
					$keys = preg_replace('/' . $result[0][$i] . '/', $this->name($result[0][$i]), $keys);
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
	 * Returns a fully-qualified table name (i.e. with prefix), quoted.
	 *
	 * @param string $entity
	 * @return string
	 */
	protected function _entityName($entity) {
		return $this->name($entity);
	}
}

?>