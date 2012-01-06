<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Analysis\Logger\Adapter;

use Lithium\Core\Libraries;
use Lithium\Util\Collection\Filters;
use Lithium\Analysis\Logger\Adapter\File;

class FileTest extends \Lithium\Test\Unit {

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
		$this->subject = new File(array('path' => $this->path));
		$priority = 'debug';
		$message = 'This is a debug message';
		$function = $this->subject->write($priority, $message);
		$now = date('Y-m-d H:i:s');
		$function('Lithium\Analysis\Logger', compact('priority', 'message'), new Filters());

		$log = file_get_contents("{$this->path}/debug.log");
		$this->assertEqual("{$now} This is a debug message\n", $log);
	}

	public function testWithoutTimestamp() {
		$this->subject = new File(array(
			'path' => $this->path, 'timestamp' => false, 'format' => "{:message}\n"
		));
		$priority = 'debug';
		$message = 'This is a debug message';
		$function = $this->subject->write($priority, $message);
		$now = date('Y-m-d H:i:s');
		$function('Lithium\Analysis\Logger', compact('priority', 'message'), new Filters());

		$log = file_get_contents("{$this->path}/debug.log");
		$this->assertEqual("This is a debug message\n", $log);
	}
}

?>