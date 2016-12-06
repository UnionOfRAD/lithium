<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source;

use MongoId;

class MockMongoManager {

	public $queries = [];

	public $results = [];

	public function executeBulkWrite($namespace, $bulk, $writeConcern = null) {
		return $this->_record(__FUNCTION__, compact('namespace', 'bulk', 'writeConcern'));
	}

	public function executeCommand($database, $command, $readPreference = null) {
		return $this->_record(__FUNCTION__, compact('database', 'command', 'readPreference'));
	}

	public function executeQuery($namespace, $query, $readPreference = null) {
		return $this->_record(__FUNCTION__, compact('namespace', 'query', 'readPreference'));
	}

	protected function _record($type, array $data = []) {
		$this->queries[] = compact('type') + $data;
		$result = array_pop($this->results);
		return $result === null ? false : $result;
	}
}

?>