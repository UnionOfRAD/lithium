<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2017, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongo_db;

use MongoGridFSFile;

/**
 * This is the result class for all MongoDB. It needs a `MongoCursor` as
 * a resource to operate on.
 *
 * @link http://php.net/manual/en/class.mongocursor.php
 */
class Result extends \lithium\data\source\Result {

	/**
	 * Fetches the next result from the resource.
	 *
	 * @return array|boolean|null Returns a key/value pair for the next result,
	 *         `null` if there is none, `false` if something bad happened.
	 */
	protected function _fetch() {
		if (!$this->_resource) {
			return false;
		}
		if (!$this->_resource->hasNext()) {
			return null;
		}
		$result = $this->_resource->getNext();

		if ($result instanceof MongoGridFSFile) {
			$result = array('file' => $result) + $result->file;
		}
		return array($this->_iterator, $result);
	}
}

?>