<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
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

	abstract public function encoding($encoding = null);

	abstract public function result($type, $resource, $context);

	public function __construct($config = array()) {
		$defaults = array(
			'persistent' => true,
			'host'       => 'localhost',
			'login'      => 'root',
			'password'   => '',
			'database'   => 'lithium',
		);
		parent::__construct((array)$config + $defaults);
	}

	/**
	 * Checks the connection status of this database. If the `'autoConnect'` option is set to true
	 * and the database connection is not currently active, an attempt will be made to connect
	 * to the database before returning the result of the connection status.
	 *
	 * @param array $options The options available for this method:
	 *              - 'autoConnect': If true, and the database connection is not currently active,
	 *                calls `connect()` on this object. Defaults to `false`.
	 * @return boolean Returns the current value of `$_isConnected`, indicating whether or not
	 *         the database connection is currently active.  This value may not always be accurate,
	 *         as the database session could have timed out or the database may have gone offline
	 *         during the course of the request.
	 */
	public function isConnected($options = array()) {
		$defaults = array('autoConnect' => false);
		$options += $defaults;

		if (!$this->_isConnected && $options['autoConnect']) {
			$this->connect();
		}
		return $this->_isConnected;
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
		$defaults = array('return' => 'array');
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


















	function generateAssociationQuery(&$model, &$linkModel, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet) {
		if (empty($queryData['fields'])) {
			$queryData['fields'] = $this->fields($model, $model->alias);
		} elseif (!empty($model->hasMany) && $model->recursive > -1) {
			$assocFields = $this->fields($model, $model->alias, array("{$model->alias}.{$model->primaryKey}"));
			$passedFields = $this->fields($model, $model->alias, $queryData['fields']);

			if (count($passedFields) === 1) {
				$match = strpos($passedFields[0], $assocFields[0]);
				$match1 = strpos($passedFields[0], 'COUNT(');
				if ($match === false && $match1 === false) {
					$queryData['fields'] = array_merge($passedFields, $assocFields);
				} else {
					$queryData['fields'] = $passedFields;
				}
			} else {
				$queryData['fields'] = array_merge($passedFields, $assocFields);
			}
			unset($assocFields, $passedFields);
		}

		if ($linkModel == null) {
			return $this->buildStatement(
				array(
					'fields' => array_unique($queryData['fields']),
					'table' => $this->fullTableName($model),
					'alias' => $model->alias,
					'limit' => $queryData['limit'],
					'offset' => $queryData['offset'],
					'joins' => $queryData['joins'],
					'conditions' => $queryData['conditions'],
					'order' => $queryData['order'],
					'group' => $queryData['group']
				),
				$model
			);
		}
		if ($external && !empty($assocData['finderQuery'])) {
			return $assocData['finderQuery'];
		}

		$alias = $association;
		$self = ($model->name == $linkModel->name);
		$fields = array();

		if ((!$external && in_array($type, array('hasOne', 'belongsTo')) && $this->__bypass === false) || $external) {
			$fields = $this->fields($linkModel, $alias, $assocData['fields']);
		}
		if (empty($assocData['offset']) && !empty($assocData['page'])) {
			$assocData['offset'] = ($assocData['page'] - 1) * $assocData['limit'];
		}
		$assocData['limit'] = $this->limit($assocData['limit'], $assocData['offset']);

		switch ($type) {
			case 'hasOne':
			case 'belongsTo':
				$conditions = $this->__mergeConditions(
					$assocData['conditions'],
					$this->getConstraint($type, $model, $linkModel, $alias, array_merge($assocData, compact('external', 'self')))
				);

				if (!$self && $external) {
					foreach ($conditions as $key => $condition) {
						if (is_numeric($key) && strpos($condition, $model->alias . '.') !== false) {
							unset($conditions[$key]);
						}
					}
				}

				if ($external) {
					$query = array_merge($assocData, array(
						'conditions' => $conditions,
						'table' => $this->fullTableName($linkModel),
						'fields' => $fields,
						'alias' => $alias,
						'group' => null
					));
					$query = array_merge(array('order' => $assocData['order'], 'limit' => $assocData['limit']), $query);
				} else {
					$join = array(
						'table' => $this->fullTableName($linkModel),
						'alias' => $alias,
						'type' => isset($assocData['type']) ? $assocData['type'] : 'LEFT',
						'conditions' => trim($this->conditions($conditions, true, false, $model))
					);
					$queryData['fields'] = array_merge($queryData['fields'], $fields);

					if (!empty($assocData['order'])) {
						$queryData['order'][] = $assocData['order'];
					}
					if (!in_array($join, $queryData['joins'])) {
						$queryData['joins'][] = $join;
					}
					return true;
				}
			break;
			case 'hasMany':
				$assocData['fields'] = $this->fields($linkModel, $alias, $assocData['fields']);
				if (!empty($assocData['foreignKey'])) {
					$assocData['fields'] = array_merge($assocData['fields'], $this->fields($linkModel, $alias, array("{$alias}.{$assocData['foreignKey']}")));
				}
				$query = array(
					'conditions' => $this->__mergeConditions($this->getConstraint('hasMany', $model, $linkModel, $alias, $assocData), $assocData['conditions']),
					'fields' => array_unique($assocData['fields']),
					'table' => $this->fullTableName($linkModel),
					'alias' => $alias,
					'order' => $assocData['order'],
					'limit' => $assocData['limit'],
					'group' => null
				);
			break;
		}
		if (isset($query)) {
			return $this->buildStatement($query, $model);
		}
		return null;
	}

	function queryAssociation(&$model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive, $stack) {
		if ($query = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet)) {
			if (!isset($resultSet) || !is_array($resultSet)) {
				if (Configure::read() > 0) {
					e('<div style = "font: Verdana bold 12px; color: #FF0000">' . sprintf(__('SQL Error in model %s:', true), $model->alias) . ' ');
					if (isset($this->error) && $this->error != null) {
						e($this->error);
					}
					e('</div>');
				}
				return null;
			}
			$count = count($resultSet);

			if ($type === 'hasMany' && empty($assocData['limit']) && !empty($assocData['foreignKey'])) {
				$ins = $fetch = array();
				for ($i = 0; $i < $count; $i++) {
					if ($in = $this->insertQueryData('{$__lithiumID__$}', $resultSet[$i], $association, $assocData, $model, $linkModel, $stack)) {
						$ins[] = $in;
					}
				}

				if (!empty($ins)) {
					$fetch = $this->fetchAssociated($model, $query, $ins);
				}

				if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {
						foreach ($linkModel->__associations as $type1) {
							foreach ($linkModel->{$type1} as $assoc1 => $assocData1) {
								$deepModel =& $linkModel->{$assoc1};
								$tmpStack = $stack;
								$tmpStack[] = $assoc1;

								if ($linkModel->useDbConfig === $deepModel->useDbConfig) {
									$db =& $this;
								} else {
									$db =& ConnectionManager::getDataSource($deepModel->useDbConfig);
								}
								$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1, $tmpStack);
							}
						}
					}
				}
				$this->__filterResults($fetch, $model);
				return $this->__mergeHasMany($resultSet, $fetch, $association, $model, $linkModel, $recursive);
			}

			for ($i = 0; $i < $count; $i++) {
				$row =& $resultSet[$i];
				$selfJoin = false;

				if ($linkModel->name === $model->name) {
					$selfJoin = true;
				}

				if (!empty($fetch) && is_array($fetch)) {
					if ($recursive > 0) {
						foreach ($linkModel->__associations as $type1) {
							foreach ($linkModel->{$type1} as $assoc1 => $assocData1) {
								$deepModel =& $linkModel->{$assoc1};

								if (($type1 === 'belongsTo') || ($deepModel->alias === $model->alias && $type === 'belongsTo') || ($deepModel->alias != $model->alias)) {
									$tmpStack = $stack;
									$tmpStack[] = $assoc1;
									if ($linkModel->useDbConfig == $deepModel->useDbConfig) {
										$db =& $this;
									} else {
										$db =& ConnectionManager::getDataSource($deepModel->useDbConfig);
									}
									$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1, $tmpStack);
								}
							}
						}
					}
					$this->__mergeAssociation($resultSet[$i], $fetch, $association, $type, $selfJoin);
					if (isset($resultSet[$i][$association])) {
						$resultSet[$i][$association] = $linkModel->afterFind($resultSet[$i][$association]);
					}
				} else {
					$tempArray[0][$association] = false;
					$this->__mergeAssociation($resultSet[$i], $tempArray, $association, $type, $selfJoin);
				}
			}
		}
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
