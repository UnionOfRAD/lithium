<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\template\view;

use lithium\core\Libraries;
use lithium\template\view\Compiler;

class CompilerTest extends \lithium\test\Unit {

	protected $_path;

	protected $_file = 'template.html.php';

	public function skip() {
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/tests');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");

		$path = realpath(Libraries::get(true, 'resources') . '/tmp/cache/templates');
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
	}

	public function setUp() {
		$this->_path = realpath(
			str_replace('\\', '/', Libraries::get(true, 'resources')) . '/tmp/tests'
		);

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
			<?=\$h('This is pre-escaped content'); ?>
		");
	}

	public function tearDown() {
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/cache/templates');

		foreach (glob("{$path}/*.php") as $file) {
			unlink($file);
		}
		unlink("{$this->_path}/{$this->_file}");
	}

	public function testTemplateContentRewriting() {
		$template = Compiler::template("{$this->_path}/{$this->_file}");
		$this->assertFileExists($template);

		$expected = [
			"<?php echo 'this is unescaped content'; ?" . ">",
			"<?php echo \$h('this is escaped content'); ?" . ">",
			"<?php echo \$h(\$alsoEscaped); ?" . ">",
			"<?php echo \$this->escape('this is also escaped content'); ?" . ">",
			'<?php echo $this->escape(',
			"'this, too, is escaped content'",
			'); ?>',
			"<?php echo \$h('This is",
			'escaped content',
			'that breaks over',
			'several lines',
			"'); ?>",
			"<?php echo \$h('This is pre-escaped content'); ?>"
		];
		$result = array_map('trim', explode("\n", trim(file_get_contents($template))));
		$this->assertEqual($expected, $result);
	}

	public function testFallbackWithNonWritableDirectory() {
		$backup = error_reporting();
		error_reporting(E_ALL);

		$path = $this->_path;
		$file = $this->_file;

		$this->assertException('/failed to open stream/i', function() use ($path, $file) {
			Compiler::template("{$path}/{$file}", [
				'path' => Libraries::get(true, 'path') . '/foo',
				'fallback' => true
			]);
		});

		$expected = '/(Could not write compiled template|failed to open stream)/i';
		$this->assertException($expected, function() use ($path, $file) {
			$result = Compiler::template("{$path}/{$file}", [
				'path' => Libraries::get(true, 'path') . '/foo',
				'fallback' => false
			]);
		});

		error_reporting($backup);
	}

	public function testTemplateCacheHit() {
		$path = Libraries::get(true, 'resources') . '/tmp/cache/templates';
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