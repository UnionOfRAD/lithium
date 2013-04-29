<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\entity;

/**
 * `Record` class. Represents data such as a row from a database. Records have fields (often known
 * as columns in databases).
 */
class Record extends \lithium\data\Entity {

	/**
	 * PHP magic method used when accessing fields as document properties, i.e. `$record->id`.
	 *
	 * Also manage scalar datas for relations. Indeed, on form submission relations datas are
	 * provided by a select input which generally provided the following array:
	 *
	 * {{{
	 * array(
	 *     'id' => 3
	 *     'Comment' => array(
	 *         '5', '6', '9
	 *     );
	 * }}}
	 *
	 * To avoid painfull pre-processing, this function will automagically manage such relation
	 * array by reformating it into the following expected format:
	 *
	 * {{{
	 * 'Post' => array(
	 *     'id' => 3
	 *     'Comment' => array(
	 *         array(
	 *             'id' => '5'
	 *         },
	 *         array(
	 *             'id' => '6'
	 *         },
	 *         array(
	 *             'id' => '9'
	 *         },
	 *     );
	 * }}}
	 *
	 * @param $name The field name, as specified with an object property.
	 * @return mixed Returns the value of the field specified in `$name`, and wraps complex data
	 *         types in sub-`Document` objects.
	 */
	public function &__get($name) {
		$result = parent::__get($name);
		$model = $this->_model;
		if (!is_object($result) && ($model = $this->_model) && ($rel = $model::relations($name))) {
			$primary = $model::key();
			$primary = !is_array($primary) ? $primary : null;
			$type = $rel->type();
			$modelTo = $rel->to();
			if ($type === 'hasOne' || $type === 'belongsTo') {
				$exists = false;
				if (is_scalar($result) && $primary) {
					$result = array($primary => $result);
					$exists = true;
				}
				if (is_array($result)) {
					$result = $model::connection()->item($modelTo, $result, array(
						'class' => 'entity', 'exists' => $exists
					));
				}
			} else {
				$result = $result ? (array) $result : array();
				foreach ($result as $key => $entity) {
					if (!is_object($entity)) {
						$exists = false;
						if (is_scalar($entity) && $primary) {
							$result[$key] = array($primary => $entity);
							$exists = true;
						}
						$result[$key] = $model::connection()->item($modelTo, $result[$key], array(
							'class' => 'entity', 'exists' => $exists
						));
					}
				}
				$result = $model::connection()->item($modelTo, $result, array('class' => 'set'));
			}
			$this->_updated[$name] = $result;
		}
		return $result;
	}

	/**
	 * Converts a `Record` object to another specified format.
	 *
	 * @param string $format The format used by default is `array`
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
		$defaults = array('handlers' => array(
			'stdClass' => function($item) { return $item; }
		));
		$options += $defaults;
		return parent::to($format, $options);
	}
}

?>