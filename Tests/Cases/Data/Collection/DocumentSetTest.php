<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Data\Collection;

use Lithium\Data\Connections;
use Lithium\Data\Source\MongoDb;
use Lithium\Data\Source\Http\Adapter\CouchDb;
use Lithium\Data\Entity\Document;
use Lithium\Data\Collection\DocumentSet;
use Lithium\Tests\Mocks\Data\Model\MockDocumentPost;
use Lithium\Tests\Mocks\Data\Source\MongoDb\MockResult;
use Lithium\Tests\Mocks\Data\Model\MockDocumentMultipleKey;

/**
 * DocumentSet tests
 */
class DocumentSetTest extends \Lithium\Test\Unit {

	protected $_model = 'Lithium\Tests\Mocks\Data\Model\MockDocumentPost';

	protected $_backup = array();

	public function skip() {
		$this->skipIf(!MongoDb::enabled(), 'MongoDb is not enabled');
		$this->skipIf(!CouchDb::enabled(), 'CouchDb is not enabled');
	}

	public function setUp() {
		if (empty($this->_backup)) {
			foreach (Connections::get() as $conn) {
				$this->_backup[$conn] = Connections::get($conn, array('config' => true));
			}
		}
		Connections::reset();

		Connections::add('mongo', array('type' => 'MongoDb', 'autoConnect' => false));
		Connections::add('couch', array('type' => 'Http', 'adapter' => 'CouchDb'));

		MockDocumentPost::config(array('connection' => 'mongo'));
		MockDocumentMultipleKey::config(array('connection' => 'couch'));
	}

	public function tearDown() {
		foreach ($this->_backup as $name => $config) {
			Connections::add($name, $config);
		}
	}

	public function testPopulateResourceClose() {
		$resource = new MockResult();
		$doc = new DocumentSet(array('model' => $this->_model, 'result' => $resource));
		$model = $this->_model;

		$result = $doc->rewind();
		$this->assertTrue($result instanceof Document);
		$this->assertTrue(is_object($result['_id']));

		$expected = array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar');
		$this->assertEqual($expected, $result->data());

		$expected = array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo');
		$this->assertEqual($expected, $doc->next()->data());

		$expected = array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib');
		$result = $doc->next()->data();
		$this->assertEqual($expected, $result);

		$this->assertNull($doc->next());
	}

	public function testMappingToNewDocumentSet() {
		$result = new MockResult();
		$model = $this->_model;
		$doc = new DocumentSet(compact('model', 'result'));

		$mapped = $doc->map(function($data) { return $data; });
		$this->assertEqual($doc->data(), $mapped->data());
		$this->assertEqual($model, $doc->model());
		$this->assertEqual($model, $mapped->model());
	}
}

?>
