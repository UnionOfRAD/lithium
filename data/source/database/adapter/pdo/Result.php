<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\database\adapter\pdo;

use PDO;
use PDOStatement;
use PDOException;

/**
 * This is the result class for all PDO based databases. It needs a `PDOStatement` as
 * a resource to operate on. Results will be fetched using `PDO::FETCH_NUM` as a numerically
 * indexed array.
 *
 * @link http://php.net/manual/class.pdostatement.php The PDOStatement class.
 */
class Result extends \lithium\data\source\Result {

	/**
	 * Fetches the next result from the resource.
	 *
	 * @return array|boolean|null Returns a key/value pair for the next result,
	 *         `null` if there is none, `false` if something bad happened.
	 */
	protected function _fetch() {
		if (!$this->_resource instanceof PDOStatement) {
			$this->close();
			return false;
		}
		try {
			if ($result = $this->_resource->fetch(PDO::FETCH_NUM)) {
				return array($this->_iterator++, $result);
			}
		} catch (PDOException $e) {
			$this->close();
			return false;
		}
		return null;
	}
}

?>