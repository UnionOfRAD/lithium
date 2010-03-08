<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\view;

use \lithium\template\view\Compiler;

class CompilerTest extends \lithium\test\Unit {

	protected $_path;

	protected $_file = 'resources/tmp/tests/template.html.php';

	public function skip() {
		$path = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$this->skipIf(!is_writable($path), "{$path} is not writable.");
	}

	public function setUp() {
		$this->_path = str_replace('\\', '/', LITHIUM_APP_PATH);
		file_put_contents("{$this->_path}/{$this->_file}", "
			<?php echo 'this is unescaped content'; ?" . ">
			<?='this is escaped content'; ?" . ">
			<?=\$alsoEscaped; ?" . ">
			<?=\$this->escape('this is also escaped content'); ?" . ">
			<?=\$this->escape(
				'this, too, is escaped content'
			); ?" . ">
			<?='This is
				escaped content
				that breaks over
				several lines
			'; ?" . ">
		");
	}

	public function tearDown() {
		foreach (glob("{$this->_path}/resources/tmp/cache/templates/*.php") as $file) {
			unlink($file);
		}
		unlink("{$this->_path}/{$this->_file}");
	}

	public function testTemplateContentRewriting() {
		$template = Compiler::template("{$this->_path}/{$this->_file}");

		$this->assertTrue(file_exists($template));

		$result = array_map('trim', explode("\n", trim(file_get_contents($template))));

		$expected = "<?php echo 'this is unescaped content'; ?" . ">";
		$this->assertEqual($expected, $result[0]);

		$expected = "<?php echo \$h('this is escaped content'); ?" . ">";
		$this->assertEqual($expected, $result[1]);

		$expected = "<?php echo \$h(\$alsoEscaped); ?" . ">";
		$this->assertEqual($expected, $result[2]);

		$expected = "<?php echo \$this->escape('this is also escaped content'); ?" . ">";
		$this->assertEqual($expected, $result[3]);

		$expected = '<?php echo $this->escape(';
		$this->assertEqual($expected, $result[4]);

		$expected = "'this, too, is escaped content'";
		$this->assertEqual($expected, $result[5]);

		$expected = '); ?>';
		$this->assertEqual($expected, $result[6]);

		$expected = "<?php echo \$h('This is";
		$this->assertEqual($expected, $result[7]);

		$expected = 'escaped content';
		$this->assertEqual($expected, $result[8]);

		$expected = 'that breaks over';
		$this->assertEqual($expected, $result[9]);

		$expected = 'several lines';
		$this->assertEqual($expected, $result[10]);

		$expected = "'); ?>";
		$this->assertEqual($expected, $result[11]);
	}

	public function testFallbackWithNonWritableDirectory() {
		$this->expectException('/failed to open stream/');
		$result = Compiler::template("{$this->_path}/{$this->_file}", array(
			'path' => LITHIUM_APP_PATH . '/foo',
			'fallback' => true
		));
		$this->assertEqual("{$this->_path}/{$this->_file}", $result);

		$this->expectException('/Could not write compiled template/');
		$this->expectException('/failed to open stream/');
		$result = Compiler::template("{$this->_path}/{$this->_file}", array(
			'path' => LITHIUM_APP_PATH . '/foo',
			'fallback' => false
		));
	}

	public function testTemplateCacheHit() {
		$path = LITHIUM_APP_PATH . '/resources/tmp/cache/templates';
		$original = Compiler::template("{$this->_path}/{$this->_file}", compact('path'));
		$cache = glob("{$path}/*");
		clearstatcache();

		$cached = Compiler::template("{$this->_path}/{$this->_file}", compact('path'));
		$this->assertEqual($original, $cached);
		$this->assertEqual($cache, glob("{$path}/*"));

		file_put_contents("{$this->_path}/{$this->_file}", "Updated");
		clearstatcache();
		$updated = Compiler::template("{$this->_path}/{$this->_file}", compact('path'));
		$newCache = glob("{$path}/*");

		$this->assertNotEqual($cache, $updated);
		$this->assertEqual(count($cache), count($newCache));
		$this->assertNotEqual($cache, $newCache);
	}
}

?>