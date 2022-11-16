<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\analysis;

use lithium\analysis\Inspector;
use lithium\core\Libraries;
use lithium\tests\mocks\analysis\MockEmptyClass;
use lithium\action\Controller;

class InspectorTest extends \lithium\test\Unit {

	public $test = 'foo';

	public static $test2 = 'bar';

	protected $_test = 'baz';

	/**
	 * Tests that basic method lists and information are queried properly.
	 */
	public function testBasicMethodInspection() {
		$class = 'lithium\console\command\Test';
		$parent = 'lithium\console\Command';

		$expected = array_diff(get_class_methods($class), get_class_methods($parent));
		$result = array_keys(Inspector::methods($class, 'extents'));
		$this->assertEqual(array_intersect($result, $expected), $result);

		$result = array_keys(Inspector::methods($class, 'extents', [
			'self' => true, 'public' => true
		]));
		$this->assertEqual($expected, $result);

		$this->assertNull(Inspector::methods('lithium\core\Foo'));

		$result = Inspector::methods('stdClass', 'extents');
		$this->assertEqual([], $result);
	}

	public function testMethodInspection() {
		$result = Inspector::methods($this, null);
		$this->assertInstanceOf('ReflectionMethod', $result[0]);

		$result = Inspector::info('lithium\core\AutoConfigurable::_init()');
		$expected = '_init';
		$this->assertEqual($expected, $result['name']);

		$expected = 'void';
		$this->assertEqual($expected, $result['tags']['return']);
	}

	/**
	 * Tests that the range of executable lines of this test method is properly calculated.
	 * Recursively meta.
	 */
	public function testMethodRange() {
		$result = Inspector::methods(__CLASS__, 'ranges', ['methods' => __FUNCTION__]);
		$expected = [__FUNCTION__ => [__LINE__ - 1, __LINE__, __LINE__ + 1]];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Gets the executable line numbers of this file based on a manual entry of line ranges. Will
	 * need to be updated manually if this method changes.
	 */
	public function testExecutableLines() {
		do {
			// These lines should be ignored
			//

			/* And these as well are ignored */

			/**
			 * Testing never proves the absence of faults,
			 * it only shows their presence.
			 * - Dijkstra
			 */
		} while (false);

		$result = Inspector::executable($this, ['methods' => __FUNCTION__]);
		$expected = [__LINE__ - 1, __LINE__, __LINE__ + 1];
		$this->assertEqual($expected, $result);
	}

	public function testExecutableLinesOnEmptyClass() {
		$result = Inspector::executable(new MockEmptyClass());
		$this->assertEqual([], $result);
	}

	/**
	 * Tests reading specific line numbers of a file.
	 */
	public function testLineIntrospection() {
		$backup = error_reporting();
		error_reporting(E_ALL);

		$result = Inspector::lines(__FILE__, [__LINE__ - 4]);
		$expected = [__LINE__ - 5 => "\tpublic function testLineIntrospection() {"];
		$this->assertEqual($expected, $result);

		$result = Inspector::lines(__CLASS__, [17]);
		$expected = [17 => 'class InspectorTest extends \lithium\test\Unit {'];
		$this->assertEqual($expected, $result);

		$lines = 'This is the first line.' . PHP_EOL . 'And this the second.';
		$result = Inspector::lines($lines, [2]);
		$expected = [2 => 'And this the second.'];
		$this->assertEqual($expected, $result);

		$this->assertException('/(Missing argument 2|Too few arguments.*1 passed.*2 expected)/i', function() {
			Inspector::lines('lithium\core\Foo');
		});
		$this->assertNull(Inspector::lines(__CLASS__, []));

		error_reporting($backup);
	}

	/**
	 * Tests reading specific line numbers of a file that has CRLF line endings.
	 */
	public function testLineIntrospectionWithCRLFLineEndings() {
		$tmpPath = Libraries::get(true, 'resources') . '/tmp/tests/inspector_crlf';
		$contents = implode("\r\n", ['one', 'two', 'three', 'four', 'five']);
		file_put_contents($tmpPath, $contents);

		$result = Inspector::lines($tmpPath, [2]);
		$expected = [2 => 'two'];
		$this->assertEqual($expected, $result);

		$result = Inspector::lines($tmpPath, [1,5]);
		$expected = [1 => 'one', 5 => 'five'];
		$this->assertEqual($expected, $result);

		$this->_cleanUp();
	}

	/**
	 * Tests getting a list of parent classes from an object or string class name.
	 */
	public function testClassParents() {
		$result = Inspector::parents($this);
		$this->assertEqual('lithium\test\Unit', current($result));

		$result2 = Inspector::parents(__CLASS__);
		$this->assertEqual($result2, $result);

		$this->assertFalse(Inspector::parents('lithium\core\Foo', ['autoLoad' => false]));
	}

	public function testClassFileIntrospection() {
		$result = Inspector::classes(['file' => __FILE__]);
		$this->assertEqual([__CLASS__ => __FILE__], $result);

		$result = Inspector::classes(['file' => __FILE__, 'group' => 'files']);
		$this->assertCount(1, $result);
		$this->assertEqual(__FILE__, key($result));

		$result = Inspector::classes(['file' => __FILE__, 'group' => 'foo']);
		$this->assertEqual([], $result);
	}

	/**
	 * Tests that names of classes, methods, properties and namespaces are parsed properly from
	 * strings.
	 */
	public function testTypeDetection() {
		$this->assertEqual('namespace', Inspector::type('lithium\util'));
		$this->assertEqual('namespace', Inspector::type('lithium\analysis'));
		$this->assertEqual('class', Inspector::type('lithium\analysis\Inspector'));
		$this->assertEqual('property', Inspector::type('Inspector::$_classes'));
		$this->assertEqual('method', Inspector::type('Inspector::type'));
		$this->assertEqual('method', Inspector::type('Inspector::type()'));

		$this->assertEqual('class', Inspector::type('\lithium\security\Auth'));
		$this->assertEqual('class', Inspector::type('lithium\security\Auth'));

		$this->assertEqual('namespace', Inspector::type('\lithium\security\auth'));
		$this->assertEqual('namespace', Inspector::type('lithium\security\auth'));
	}

	/**
	 * Tests getting reflection information based on a string identifier.
	 */
	public function testIdentifierIntrospection() {
		$result = Inspector::info(__METHOD__);
		$this->assertEqual(['public'], $result['modifiers']);
		$this->assertEqual(__FUNCTION__, $result['name']);

		$this->assertNull(Inspector::info('\lithium\util'));

		$info = Inspector::info('\lithium\analysis\Inspector');
		$result = str_replace('\\', '/', $info['file']);
		$this->assertNotEmpty(strpos($result, '/analysis/Inspector.php'));
		$this->assertEqual('lithium\analysis', $info['namespace']);
		$this->assertEqual('Inspector', $info['shortName']);

		$result = Inspector::info('\lithium\analysis\Inspector::$_methodMap');
		$this->assertEqual('_methodMap', $result['name']);

		$expected = 'Maps reflect method names to result array keys.';
		$this->assertEqual($expected, $result['description']);
		$this->assertEqual(['var' => 'array'], $result['tags']);

		$result = Inspector::info('\lithium\analysis\Inspector::info()', [
			'modifiers', 'namespace', 'foo'
		]);
		$this->assertEqual(['modifiers', 'namespace'], array_keys($result));

		$this->assertNull(Inspector::info('\lithium\analysis\Inspector::$foo'));

		$this->assertNull(Inspector::info('\lithium\core\Foo::$foo'));
	}

	public function testClassDependencies() {
		$expected = [
			'Exception',
			'RuntimeException',
			'ReflectionClass',
			'ReflectionProperty',
			'ReflectionException',
			'InvalidArgumentException',
			'SplFileObject',
			'lithium\core\Libraries',
			'lithium\analysis\Docblock',
		];

		$result = Inspector::dependencies($this->subject(), ['type' => 'static']);
		$this->assertEqual($expected, $result);

		$expected[] = 'lithium\\util\\Collection';
		$result = Inspector::dependencies($this->subject());
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that class and namepace names which are equivalent in a case-insensitive search still
	 * match properly.
	 */
	public function testCaseSensitiveIdentifiers() {
		$result = Inspector::type('lithium\storage\Cache');
		$expected = 'class';
		$this->assertEqual($expected, $result);

		$result = Inspector::type('lithium\storage\cache');
		$expected = 'namespace';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests getting static and non-static properties from various types of classes.
	 */
	public function testGetClassProperties() {
		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties($this)
		);
		$expected = ['test', 'test2'];
		$this->assertEqual($expected, $result);

		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties($this, ['public' => false])
		);
		$expected = ['test', 'test2', '_test'];
		$this->assertEqual($expected, $result);

		$result = Inspector::properties($this);
		$expected = [
			[
				'modifiers' => ['public'],
				'value' => 'foo',
				'docComment' => false,
				'name' => 'test'
			],
			[
				'modifiers' => ['public', 'static'],
				'value' => 'bar',
				'docComment' => false,
				'name' => 'test2'
			]
		];
		$this->assertEqual($expected, $result);

		$controller = new Controller(['init' => false]);

		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties($controller)
		);
		$this->assertTrue(in_array('request', $result));
		$this->assertTrue(in_array('response', $result));
		$this->assertFalse(in_array('_render', $result));
		$this->assertFalse(in_array('_classes', $result));

		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties($controller, ['public' => false])
		);
		$this->assertTrue(in_array('request', $result));
		$this->assertTrue(in_array('response', $result));
		$this->assertTrue(in_array('_render', $result));
		$this->assertTrue(in_array('_classes', $result));

		$this->assertNull(Inspector::properties('\lithium\core\Foo'));
	}

	public function testCallableObjectWithBadMethods() {
		$stdObj = new MockEmptyClass;
		$this->assertFalse(Inspector::isCallable($stdObj, 'foo', 0));
		$this->assertFalse(Inspector::isCallable($stdObj, 'bar', 0));
		$this->assertFalse(Inspector::isCallable($stdObj, 'baz', 0));
	}

	public function testCallableClassWithBadMethods() {
		$this->assertFalse(Inspector::isCallable('lithium\action\Dispatcher', 'foo', 0));
		$this->assertFalse(Inspector::isCallable('lithium\action\Dispatcher', 'bar', 0));
		$this->assertFalse(Inspector::isCallable('lithium\action\Dispatcher', 'baz', 0));
	}

	public function testCallableObjectWithRealMethods() {
		$obj = new Controller(['init' => false]);
		$this->assertTrue(Inspector::isCallable($obj, 'render', 0));
	}

	public function testCallableClassWithRealMethods() {
		$this->assertTrue(Inspector::isCallable('lithium\action\Dispatcher', 'config', 0));
		$this->assertTrue(Inspector::isCallable('lithium\action\Dispatcher', 'run', 0));
		$this->assertTrue(Inspector::isCallable('lithium\action\Dispatcher', 'applyRules', 0));
	}

	public function testCallableVisibility() {
		$obj = new Controller(['init' => false]);
		$this->assertTrue(Inspector::isCallable($obj, 'render', 0));
		$this->assertTrue(Inspector::isCallable($obj, 'render', 1));
		$this->assertFalse(Inspector::isCallable('lithium\action\Dispatcher', '_callable', 0));
		$this->assertTrue(Inspector::isCallable('lithium\action\Dispatcher', '_callable', 1));
	}
}

?>