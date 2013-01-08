<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

class MockMongoGridFS {

	public $queries = array();
	public $results = array();

	public $filesName = NULL;
	public $chunksName = NULL;

	protected $_collection = null;

	/**
	 * From the official MongoDB documentation:
	 * 	GridFS places the collections in a common bucket by prefixing each with the bucket name. By default, GridFS uses two collections
	 *  with names prefixed by fs bucket:
	 *  fs.files
	 *  fs.chunks
	 *
	 * From PHP documentation:
	 * Files as stored across two collections, the first containing file meta information, the second containing chunks of the actual file.
	 * By default, fs.files and fs.chunks are the collection names used
	 */
	public function __construct (MongoDB $db = null, $prefix = "fs", $chunks = "fs"){
		$this->filesName = $prefix . '.files';
		$this->chunksName = $chunks . '.chunks';

		return;
	}

	protected function _record($type, array $data = array()) {
		$collection = $this->_collection;
		$this->queries[] = compact('type', 'collection') + $data;
		return array_pop($this->results);
	}

	public function storeBytes($bytes = null, array $extra = array(), array $options = array()) {
		return;
	}
}

?>