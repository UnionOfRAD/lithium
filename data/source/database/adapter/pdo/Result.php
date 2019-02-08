<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
				return [$this->_iterator++, $result];
			}
		} catch (PDOException $e) {
			$this->close();
			return false;
		}
		return null;
	}
}

?>