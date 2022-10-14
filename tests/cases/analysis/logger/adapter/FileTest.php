<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\analysis\logger\adapter;

use lithium\core\Libraries;
use lithium\analysis\logger\adapter\File;

class FileTest extends \lithium\test\Unit {

	public $path;

	public $subject;

	public function skip() {
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/logs');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
	}

	public function setUp() {
		$this->path = Libraries::get(true, 'resources') . '/tmp/logs';
		$this->tearDown();
	}

	public function tearDown() {
		if (file_exists("{$this->path}/debug.log")) {
			unlink("{$this->path}/debug.log");
		}
	}

	public function testWriting() {
		$this->subject = new File(['path' => $this->path]);
		$priority = 'debug';
		$message = 'This is a debug message';
		$function = $this->subject->write($priority, $message);
		$now = date('Y-m-d H:i:s');
		$function(compact('priority', 'message'));

		$log = file_get_contents("{$this->path}/debug.log");
		$this->assertEqual("{$now} This is a debug message\n", $log);
	}

	public function testWithoutTimestamp() {
		$this->subject = new File([
			'path' => $this->path, 'timestamp' => false, 'format' => "{:message}\n"
		]);
		$priority = 'debug';
		$message = 'This is a debug message';
		$function = $this->subject->write($priority, $message);
		$now = date('Y-m-d H:i:s');
		$function(compact('priority', 'message'));

		$log = file_get_contents("{$this->path}/debug.log");
		$this->assertEqual("This is a debug message\n", $log);
	}
}

?>