<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

use lithium\aop\Filters;
use lithium\data\model\Query;
use lithium\data\entity\Record;
use lithium\data\collection\RecordSet;

class MockComment extends \lithium\data\Model {

	public $belongsTo = ['MockPost'];

	protected $_meta = ['connection' => false, 'key' => 'comment_id'];

	public static function find($type, array $options = []) {
		$defaults = [
			'conditions' => null, 'fields' => null, 'order' => null, 'limit' => null, 'page' => 1
		];
		$options += $defaults;
		$params = compact('type', 'options');
		$self = static::_object();

		$implementation = function($params) {
			$query = new Query(['type' => 'read'] + $params['options']);

			return new RecordSet([
				'query'    => $query,
				'data'    => array_map(
					function($data) {
						return new Record(compact('data') + ['model' => __CLASS__]);
					},
					[
						['comment_id' => 1, 'author_id' => 123, 'text' => 'First comment'],
						['comment_id' => 2, 'author_id' => 241, 'text' => 'Second comment'],
						['comment_id' => 3, 'author_id' => 451, 'text' => 'Third comment']
					]
				)
			]);
		};
		if (isset($self->_finders[$type])) {
			$finder = $self->_finders[$type];

			$implementation = function($params) use ($finder, $implementation) {
				return $finder($params, $implementation);
			};
		}
		return Filters::run(get_called_class(), __FUNCTION__, $params, $implementation);
	}
}

?>