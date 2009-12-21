<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright	 Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license	   http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\core\Libraries;

class LibrariesTest extends \lithium\test\Unit {

	public function testNamespaceToFileTranslation() {
		$result = Libraries::path('\lithium\core\Libraries');
		$this->assertTrue(strpos($result, '/lithium/core/Libraries.php'));
		$this->assertTrue(file_exists($result));
		$this->assertFalse(strpos($result, '\\'));
	}

	public function testPathTransform() {
		$expected = 'Library/Class/Separated/By/Underscore';
		$result = Libraries::path('Library_Class_Separated_By_Underscore', array(
			'prefix' => 'Library_',
			'transform' => function ($class, $options) {
				return str_replace('_', '/', $class);
			}
		));
		$this->assertEqual($expected, $result);

		$expected = 'Library/Class/Separated/By/Nothing';
		$result = Libraries::path('LibraryClassSeparatedByNothing', array(
			'prefix' => 'Library',
			'transform' => array('/([a-z])([A-Z])/', '$1/$2')
		));
		$this->assertEqual($expected, $result);
	}

	public function testPathFiltering() {
		$tests = Libraries::find('lithium', array('recursive' => true, 'path' => '/tests/cases'));
		$result = preg_grep('/^lithium\\\\tests\\\\cases\\\\/', $tests);
		$this->assertIdentical($tests, $result);

		$all = Libraries::find('lithium', array('recursive' => true));
		$result = array_values(preg_grep('/^lithium\\\\tests\\\\cases\\\\/', $all));
		$this->assertIdentical($tests, $result);

		$tests = Libraries::find('app', array('recursive' => true, 'path' => '/tests/cases'));
		$result = preg_grep('/^app\\\\tests\\\\cases\\\\/', $tests);
		$this->assertIdentical($tests, $result);
	}

	/**
	 * Tests accessing library configurations.
	 *
	 * @return void
	 */
	public function testLibraryConfigAccess() {
		$result = Libraries::get('lithium');
		$expected = array(
			'path' => str_replace('\\', '/', LITHIUM_LIBRARY_PATH) . '/lithium',
			'loader' => 'lithium\\core\\Libraries::load',
			'prefix' => 'lithium\\',
			'suffix' => '.php',
			'transform' => null,
			'bootstrap' => null,
			'defer' => true,
			'includePath' => false
		);
		$this->assertEqual($expected, $result);
		$this->assertNull(Libraries::get('foo'));

		$result = Libraries::get();
		$this->assertTrue(isset($result['lithium']));
		$this->assertTrue(isset($result['app']));
		$this->assertEqual($expected, $result['lithium']);
	}

	/**
	 * Tests the addition and removal of default libraries.
	 *
	 * @return void
	 */
	public function testLibraryAddRemove() {
		$lithium = Libraries::get('lithium');
		$this->assertFalse(empty($lithium));

		$app = Libraries::get('app');
		$this->assertFalse(empty($app));

		Libraries::remove(array('lithium', 'app'));

		$result = Libraries::get('lithium');
		$this->assertTrue(empty($result));

		$result = Libraries::get('app');
		$this->assertTrue(empty($result));

		$result = Libraries::add('lithium', array('bootstrap' => null) + $lithium);
		$this->assertEqual($lithium, $result);

		$result = Libraries::add('app', array('bootstrap' => null) + $app);
		$this->assertEqual(array('bootstrap' => null) + $app, $result);
	}

	/**
	 * Tests the loading of libraries
	 *
	 * @return void
	 */
	public function testLibraryLoad() {
		$this->expectException('Failed to load SomeInvalidLibrary from ');
		Libraries::load('SomeInvalidLibrary', true);
	}

	/**
	 * Tests path caching by calling `path()` twice.
	 *
	 * @return void
	 */
	public function testPathCaching() {
		$path = Libraries::path(__CLASS__);
		$result = Libraries::path(__CLASS__);
		$this->assertEqual($path, $result);
	}

	/**
	 * Tests recursive and non-recursive searching through libraries with paths.
	 *
	 * @return void
	 */
	public function testFindingClasses() {
		$result = Libraries::find('lithium', array(
			'recursive' => true, 'path' => '/tests/cases', 'filter' => '/LibrariesTest/'
		));
		$this->assertIdentical(array(__CLASS__), $result);

		$result = Libraries::find('lithium', array(
			'path' => '/tests/cases/', 'filter' => '/LibrariesTest/'
		));
		$this->assertIdentical(array(), $result);

		$result = Libraries::find('lithium', array(
			'path' => '/tests/cases/core', 'filter' => '/LibrariesTest/'
		));
		$this->assertIdentical(array(__CLASS__), $result);

		$count = Libraries::find('lithium', array('recursive' => true));
		$count2 = Libraries::find(true, array('recursive' => true));
		$this->assertTrue($count < $count2);

		$result = Libraries::find('foo', array('recursive' => true));
		$this->assertNull($result);
	}


	public function testFindingClassesWithExclude() {
		$expected = array();
		$options = array(
			'recursive' => true,
			'filter' => false,
			'exclude' => '/\w+Test$|webroot|index$|^app\\\\config|^\w+\\\\views\/|\./'
		);
		$classes = Libraries::find('lithium', $options);
		$result = preg_grep('/\w+Test/', $classes);
		$this->assertEqual($expected, $result);

		$expected = Libraries::find('lithium', array(
			'filter' => '/\w+Test$/', 'recursive' => true
		));
		$result = preg_grep('/\w+Test/', $expected);
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateAll() {
		$result = Libraries::locate('tests');
		$this->assertTrue(count($result) > 30);

		$expected = array(
			'lithium\template\view\adapter\File', 'lithium\template\view\adapter\Simple'
		);
		$result = Libraries::locate('adapter.template.view');
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateAllCommands() {
		$result = Libraries::locate('command');
		$this->assertTrue(count($result) > 10);

		$expected = array(
			'lithium\console\command\docs\Generator', 'lithium\console\command\docs\Todo'
		);
		$result = Libraries::locate('command.docs');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests locating service objects.  These tests may fail if not run on a stock install, as other
	 * objects may preceed the core objects in load order.
	 *
	 * @return void
	 */
	public function testServiceLocation() {
		$this->assertNull(Libraries::locate('adapter', 'File'));
		$this->assertNull(Libraries::locate('adapter.view', 'File'));
		$this->assertNull(Libraries::locate('invalid_package', 'InvalidClass'));

		$result = Libraries::locate('adapter.template.view', 'File');
		$this->assertEqual('lithium\template\view\adapter\File', $result);

		$result = Libraries::locate('adapter.storage.cache', 'File');
		$expected = 'lithium\storage\cache\adapter\File';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('data.source', 'Database');
		$expected = 'lithium\data\source\Database';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('adapter.data.source.database', 'MySql');
		$expected = 'lithium\data\source\database\adapter\MySql';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate(null, '\lithium\data\source\Database');
		$expected = '\lithium\data\source\Database';
		$this->assertEqual($expected, $result);

		$expected = new \stdClass();
		$result = Libraries::locate(null, $expected);
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateApp() {
		$result = Libraries::locate('controllers', 'HelloWorld');
		$expected = 'app\controllers\HelloWorldController';
		$this->assertEqual($expected, $result);

		// Tests caching of paths
		$result = Libraries::locate('controllers', 'HelloWorld');
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateCommand() {
		$result = Libraries::locate('command.docs', 'Generator');
		$expected = 'lithium\console\command\docs\Generator';
		$this->assertEqual($expected, $result);
	}

	public function testCaseSensitivePathLookups() {
		$library = Libraries::get('lithium');
		$base = $library['path'] . '/';

		$expected = $base . 'template/view.php';
		$result = Libraries::path('\lithium\template\view');
		$this->assertEqual($expected, $result);

		$result = Libraries::path('lithium\template\view');
		$this->assertEqual($expected, $result);

		$expected = $base . 'template/View.php';

		$result = Libraries::path('\lithium\template\View');
		$this->assertEqual($expected, $result);

		$result = Libraries::path('lithium\template\View');
		$this->assertEqual($expected, $result);

		$expected = $base . 'template/view';

		$result = Libraries::path('\lithium\template\view', array('dirs' => true));
		$this->assertEqual($expected, $result);

		$result = Libraries::path('lithium\template\view', array('dirs' => true));
		$this->assertEqual($expected, $result);
	}

	public function testPathDirectoryLookups() {
		$library = Libraries::get('lithium');
		$base = $library['path'] . '/';

		$result = Libraries::path('lithium\template\View', array('dirs' => true));
		$expected = $base . 'template/View.php';
		$this->assertEqual($expected, $result);

		$result = Libraries::path('lithium\template\views', array('dirs' => true));
		$this->assertNull($result);
	}

	public function testAddingMulitplePlugins() {
		$plugins = Libraries::add('plugin', array(
			'li3_foo_blog' => array('bootstrap' => false),
			'li3_foo_forum'
		));
		$expected = array('li3_foo_blog', 'li3_foo_forum');
		$this->assertEqual($expected, array_keys($plugins));

		$this->assertIdentical(false, $plugins['li3_foo_blog']['bootstrap']);
		$this->assertIdentical(false, $plugins['li3_foo_forum']['bootstrap']);
	}
}

?>