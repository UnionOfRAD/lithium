<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\view;

use \lithium\template\view\Stream;

class StreamTest extends \lithium\test\Unit {

	public function setUp() {
		file_put_contents(LITHIUM_APP_PATH . '/tmp/tests/template.html.php', "
			<?php echo 'this is unescaped content'; ?" . ">
			<?='this is escaped content'; ?" . ">
			<?=\$alsoEscaped; ?" . ">
			<?=\$this->escape('this is also escaped content'); ?" . ">
		");
	}

	public function tearDown() {
		unlink(LITHIUM_APP_PATH . '/tmp/tests/template.html.php');
	}

	public function testPathFailure() {
		$stream = new Stream();
		$null = null;
		$result = $stream->stream_open(null, null, null, $null);
		$this->assertFalse($result);
	}

	public function testStreamContentRewriting() {
		$stream = new Stream();
		$null = null;
		$path = 'lithium.template://' . LITHIUM_APP_PATH . '/tmp/tests/template.html.php';

		$stream->stream_open($path, null, null, $null);
		$result = array_map('trim', explode("\n", trim($stream->stream_read(999))));

		$expected = "<?php echo 'this is unescaped content'; ?" . ">";
		$this->assertEqual($expected, $result[0]);

		$expected = "<?php echo \$h('this is escaped content'); ?" . ">";
		$this->assertEqual($expected, $result[1]);

		$expected = "<?php echo \$h(\$alsoEscaped); ?" . ">";
		$this->assertEqual($expected, $result[2]);

		$expected = "<?php echo \$this->escape('this is also escaped content'); ?" . ">";
		$this->assertEqual($expected, $result[3]);
	}
}

?>