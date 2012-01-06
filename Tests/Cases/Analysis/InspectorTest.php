<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Analysis;

use ReflectionMethod;
use Lithium\Analysis\Inspector;

class InspectorTest extends \Lithium\Test\Unit {

	public $test = 'foo';

	public static $test2 = 'bar';

	protected $_test = 'baz';

	/**
	 * Tests that basic method lists and information are queried properly.
	 *
	 * @return void
	 */
	public function testBasicMethodInspection() {
		$class = '\Lithium\Analysis\Inspector';
		$parent = '\Lithium\Core\StaticObject';

		$expected = array_diff(get_class_methods($class), get_class_methods($parent));
		$result = array_keys(Inspector::methods($class, 'extents'));
		$this->assertEqual(array_intersect($result, $expected), $result);

		$result = array_keys(Inspector::methods($class, 'extents', array(
			'self' => true, 'public' => true
		)));
		$this->assertEqual($expected, $result);

		$this->assertNull(Inspector::methods('\Lithium\Core\Foo'));

		$result = Inspector::methods('stdClass', 'extents');
		$this->assertEqual(array(), $result);
	}

	public function testMethodInspection() {
		$result = Inspector::methods($this, null);
		$this->assertTrue($result[0] instanceof ReflectionMethod);

		$result = Inspector::info('Lithium\Core\Object::_init()');
		$expected = '_init';
		$this->assertEqual($expected, $result['name']);

		$expected = 'void';
		$this->assertEqual($expected, $result['tags']['return']);
	}

	/**
	 * Tests that the range of executable lines of this test method is properly calculated.
	 * Recursively meta.
	 *
	 * @return void
	 */
	public function testMethodRange() {
		$result = Inspector::methods(__CLASS__, 'ranges', array('methods' => __FUNCTION__));
		$expected = array(__FUNCTION__ => array(__LINE__ - 1, __LINE__, __LINE__ + 1));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Gets the executable line numbers of this file based on a manual entry of line ranges. Will
	 * need to be updated manually if this method changes.
	 *
	 * @return void
	 */
	public function testExecutableLines() {
		do {
			// These lines should be ignored
		} while (false);

		$result = Inspector::executable($this, array('methods' => __FUNCTION__));
		$expected = array(__LINE__ - 1, __LINE__, __LINE__ + 1);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests reading specific line numbers of a file.
	 *
	 * @return void
	 */
	public function testLineIntrospection() {
		$result = Inspector::lines(__FILE__, array(__LINE__ - 1));
		$expected = array(__LINE__ - 2 => "\tpublic function testLineIntrospection() {");
		$this->assertEqual($expected, $result);

		$result = Inspector::lines(__CLASS__, array(14));
		$expected = array(14 => 'class InspectorTest extends \Lithium\Test\Unit {');
		$this->assertEqual($expected, $result);

		$this->expectException('/Missing argument 2/');
		$this->assertNull(Inspector::lines('\Lithium\Core\Foo'));
		$this->assertNull(Inspector::lines(__CLASS__, array()));
	}

	/**
	 * Tests getting a list of parent classes from an object or string class name.
	 *
	 * @return void
	 */
	public function testClassParents() {
		$result = Inspector::parents($this);
		$this->assertEqual('Lithium\Test\Unit', current($result));

		$result2 = Inspector::parents(__CLASS__);
		$this->assertEqual($result2, $result);

		$this->assertFalse(Inspector::parents('Lithium\Core\Foo', array('autoLoad' => false)));
	}

	public function testClassFileIntrospection() {
		$result = Inspector::classes(array('file' => __FILE__));
		$this->assertEqual(array(__CLASS__ => __FILE__), $result);

		$result = Inspector::classes(array('file' => __FILE__, 'group' => 'files'));
		$this->assertEqual(1, count($result));
		$this->assertEqual(__FILE__, key($result));

		$result = Inspector::classes(array('file' => __FILE__, 'group' => 'foo'));
		$this->assertEqual(array(), $result);
	}

	/**
	 * Tests that names of classes, methods, properties and namespaces are parsed properly from
	 * strings.
	 *
	 * @return void
	 */
	public function testTypeDetection() {
		$this->assertEqual('namespace', Inspector::type('\Lithium\Util'));
		$this->assertEqual('namespace', Inspector::type('\Lithium\Analysis'));
		$this->assertEqual('class', Inspector::type('\Lithium\Analysis\Inspector'));
		$this->assertEqual('property', Inspector::type('Inspector::$_classes'));
		$this->assertEqual('method', Inspector::type('Inspector::type'));
		$this->assertEqual('method', Inspector::type('Inspector::type()'));

		$this->assertEqual('class', Inspector::type('\Lithium\Security\Auth'));
		$this->assertEqual('class', Inspector::type('Lithium\Security\Auth'));

		$this->assertEqual('namespace', Inspector::type('\Lithium\Security\Auth'));
		$this->assertEqual('namespace', Inspector::type('Lithium\Security\Auth'));
	}

	/**
	 * Tests getting reflection information based on a string identifier.
	 *
	 * @return void
	 */
	public function testIdentifierIntrospection() {
		$result = Inspector::info(__METHOD__);
		$this->assertEqual(array('public'), $result['modifiers']);
		$this->assertEqual(__FUNCTION__, $result['name']);

		$this->assertNull(Inspector::info('\Lithium\Util'));

		$info = Inspector::info('\Lithium\Analysis\Inspector');
		$result = str_replace('\\', '/', $info['file']);
		$this->assertTrue(strpos($result, '/Analysis/Inspector.php'));
		$this->assertEqual('Lithium\Analysis', $info['namespace']);
		$this->assertEqual('Inspector', $info['shortName']);

		$result = Inspector::info('\Lithium\Analysis\Inspector::$_methodMap');
		$this->assertEqual('_methodMap', $result['name']);

		$expected = 'Maps reflect method names to result array keys.';
		$this->assertEqual($expected, $result['description']);
		$this->assertEqual(array('var' => 'array'), $result['tags']);

		$result = Inspector::info('\Lithium\Analysis\Inspector::info()', array(
			'modifiers', 'namespace', 'foo'
		));
		$this->assertEqual(array('modifiers', 'namespace'), array_keys($result));

		$this->assertNull(Inspector::info('\Lithium\Analysis\Inspector::$foo'));

		$this->assertNull(Inspector::info('\Lithium\Core\Foo::$foo'));
	}

	public function testClassDependencies() {
		$expected = array(
			'Exception', 'ReflectionClass', 'ReflectionProperty', 'ReflectionException',
			'Lithium\\Core\\Libraries'
		);
		$result = Inspector::dependencies($this->subject(), array('type' => 'static'));
		$this->assertEqual($expected, $result);

		$expected[] = 'Lithium\\Util\\Collection';
		$result = Inspector::dependencies($this->subject());
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that class and namepace names which are equivalent in a case-insensitive search still
	 * match properly.
	 *
	 * @return void
	 */
	public function testCaseSensitiveIdentifiers() {
		$result = Inspector::type('Lithium\Storage\Cache');
		$expected = 'class';
		$this->assertEqual($expected, $result);

		$result = Inspector::type('Lithium\Storage\Cache');
		$expected = 'namespace';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests getting static and non-static properties from various types of classes.
	 *
	 * @return void
	 */
	public function testGetClassProperties() {
		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties(__CLASS__)
		);
		$expected = array('test', 'test2');
		$this->assertEqual($expected, $result);

		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties(__CLASS__, array('public' => false))
		);
		$expected = array('test', 'test2', '_test');
		$this->assertEqual($expected, $result);

		$result = Inspector::properties(__CLASS__);
		$expected = array(
			array(
				'modifiers' => array('public'),
				'docComment' => false,
				'name' => 'test',
				'value' => null
			),
			array(
				'modifiers' => array('public', 'static'),
				'docComment' => false,
				'name' => 'test2',
				'value' => 'bar'
			)
		);
		$this->assertEqual($expected, $result);

		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties('Lithium\Action\Controller')
		);
		$this->assertTrue(in_array('request', $result));
		$this->assertTrue(in_array('response', $result));
		$this->assertFalse(in_array('_render', $result));
		$this->assertFalse(in_array('_classes', $result));

		$result = array_map(
			function($property) { return $property['name']; },
			Inspector::properties('Lithium\Action\Controller', array('public' => false))
		);
		$this->assertTrue(in_array('request', $result));
		$this->assertTrue(in_array('response', $result));
		$this->assertTrue(in_array('_render', $result));
		$this->assertTrue(in_array('_classes', $result));

		$this->assertNull(Inspector::properties('\Lithium\Core\Foo'));
	}
}

?>
