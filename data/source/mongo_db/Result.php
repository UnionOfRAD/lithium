<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongo_db;

use MongoGridFSFile;
use Exception;

class Result extends \lithium\data\source\Result {

	public function prev() {
		return null;
	}

	protected function _fetchFromCache() {
		return null;
	}

	/**
	 * Fetches the result from the resource and caches it.
	 *
	 * @return boolean Return `true` on success or `false` if it is not valid.
	 */
	protected function _fetchFromResource() {
		if ($this->_resource && $this->_resource->hasNext()) {
			try{
		            $result = $this->_resource->getNext();
		        }
			catch (Exception $e){
		            return false;
		        }
			$isFile = ($result instanceof MongoGridFSFile);
			$result = $isFile ? array('file' => $result) + $result->file : $result;
			$this->_key = $this->_iterator;
			$this->_current = $result;
			return true;
		}
		return false;
	}
}

?>
