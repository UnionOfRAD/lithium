<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright	 Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license	   http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \SplFileInfo;
use \lithium\core\Libraries;

class LibrariesTest extends \lithium\test\Unit {

	public function testNamespaceToFileTranslation() {
		$result = Libraries::path('\lithium\core\Libraries');
		$this->assertTrue(strpos($result, '/lithium/core/Libraries.php'));
		$this->assertTrue(file_exists($result));
		$this->assertFalse(strpos($result, '\\'));
	}

	public function testPathTemplate() {
		$expected = array('{:app}/libraries/{:name}', '{:root}/libraries/{:name}');
		$result = Libraries::paths('libraries');
		$this->assertEqual($expected, $result);

		$this->assertNull(Libraries::locate('authAdapter', 'Form'));

		$paths = Libraries::paths();
		$test  = array('authAdapter' => array('lithium\security\auth\adapter\{:name}'));
		Libraries::paths($test);

		$this->assertEqual($paths + $test, Libraries::paths());

		$class = Libraries::locate('authAdapter', 'Form');
		$expected = 'lithium\security\auth\adapter\Form';
		$this->assertEqual($expected, $class);
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
	 * Tests that non-class files are always filtered out of `find()` results unless an alternate
	 * filter is specified.
	 *
	 * @return void
	 */
	public function testExcludeNonClassFiles() {
		$result = Libraries::find('lithium');
		$this->assertFalse($result);

		$result = Libraries::find('lithium', array('namespaces' => true));

		$this->assertTrue(in_array('lithium\action', $result));
		$this->assertTrue(in_array('lithium\core', $result));
		$this->assertTrue(in_array('lithium\util', $result));

		$this->assertFalse(in_array('lithium\LICENSE.txt', $result));
		$this->assertFalse(in_array('lithium\readme.wiki', $result));

		$this->assertFalse(Libraries::find('lithium'));
		$result = Libraries::find('lithium', array('path' => '/test/filter/reporter/template'));
		$this->assertFalse($result);

		$result = Libraries::find('lithium', array(
			'path' => '/test/filter/reporter/template',
			'namespaces' => true
		));
		$this->assertFalse($result);
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
		$this->assertFalse(Libraries::cache(false));
		$path = Libraries::path(__CLASS__);
		$this->assertEqual(__FILE__, realpath($path));

		$result = Libraries::cache();
		$this->assertEqual(realpath($result[__CLASS__]), __FILE__);
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

	public function testFindingClassesAndNamespaces() {
		$result = Libraries::find('app', array('namespaces' => true));
		$this->assertTrue(in_array('app\config', $result));
		$this->assertTrue(in_array('app\controllers', $result));
		$this->assertTrue(in_array('app\models', $result));
		$this->assertFalse(in_array('app\index', $result));
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
		$this->assertTrue(count($result) > 7);

		$expected = array('lithium\console\command\g11n\Extract');
		$result = Libraries::locate('command.g11n');
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
		$result = Libraries::locate('command.g11n', 'Extract');
		$expected = 'lithium\console\command\g11n\Extract';
		$this->assertEqual($expected, $result);
	}

	public function testCaseSensitivePathLookups() {
		Libraries::cache(false);
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

	public function testFindingClassesWithCallableFilters() {
		$result = Libraries::find('lithium', array(
			'recursive' => true, 'path' => '/tests/cases',
			'format' => function($file, $config) {
				return new SplFileInfo($file);
			},
			'filter' =>  function($file) {
				if ($file->getFilename() === 'LibrariesTest.php') {
					return $file;
				}
			}
		));
		$this->assertEqual(1, count($result));
		$this->assertIdentical(__FILE__, $result[0]->getRealPath());
	}

	public function testFindingClassesWithCallableExcludes() {
		$result = Libraries::find('lithium', array(
			'recursive' => true, 'path' => '/tests/cases',
			'format' => function($file, $config) {
				return new SplFileInfo($file);
			},
			'filter' => null,
			'exclude' =>  function($file) {
				if ($file->getFilename() == 'LibrariesTest.php') {
					return true;
				}
			}
		));
		$this->assertEqual(1, count($result));
		$this->assertIdentical(__FILE__, $result[0]->getRealPath());
	}

	public function testFindWithOptions() {
		$result = Libraries::find('lithium', array(
			'path' => '/console/command/create/template',
			'namespaces' => false, 'suffix' => false,
			'filter' => false, 'exclude' => false, 'format' => function ($file, $config) {
				return basename($file);
			},
		));
		$this->assertTrue(count($result) > 3);
		$this->assertTrue(array_search('controller.txt.php', $result));
		$this->assertTrue(array_search('model.txt.php', $result));
		$this->assertTrue(array_search('plugin.phar.gz', $result));
	}
	
	public function testLocateWithDotSyntax() {
		$expected = 'app\controllers\PagesController';
		$result = Libraries::locate('controllers', 'app.Pages');
		$this->assertEqual($expected, $result);
	}
}

?>