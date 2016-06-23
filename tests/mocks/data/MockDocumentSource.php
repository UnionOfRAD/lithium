<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockDocumentSource extends \lithium\tests\mocks\data\MockSource {

	protected $_classes = [
		'entity' => 'lithium\data\entity\Document',
		'set' => 'lithium\data\collection\DocumentSet',
		'relationship' => 'lithium\data\model\Relationship',
		'schema' => 'lithium\data\DocumentSchema'
	];
}

?>