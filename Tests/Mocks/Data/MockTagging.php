<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data;

class MockTagging extends \Lithium\Data\Model {

	protected $_meta = array(
		'connection' => 'mock-source',
		'source' => 'posts_tags', 'key' => array('post_id', 'tag_id')
	);
}

?>