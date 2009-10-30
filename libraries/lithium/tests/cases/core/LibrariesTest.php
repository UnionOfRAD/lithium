<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\core\Libraries;

class MockLibraries extends \lithium\core\Libraries {

	public static function paths($type, $libraries, $params = array(), $options = array()) {
		return static::_paths($type, $libraries, $params, $options);
	}

	public static function search($paths, $options = array()) {
		return static::_search($paths, $options);
	}
}

class LibrariesTest extends \lithium\test\Unit {

	public function testNamespaceToFileTranslation() {
		$result = Libraries::path('\lithium\core\Libraries');
		$this->assertTrue(strpos($result, '/lithium/core/Libraries.php'));
		$this->assertTrue(file_exists($result));
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
			'path' => LITHIUM_LIBRARY_PATH . '/lithium',
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
		$this->assertTrue(array_key_exists('lithium', $result));
		$this->assertTrue(array_key_exists('app', $result));
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
		$expected = array(
			'lithium\template\view\adapters\File', 'lithium\template\view\adapters\Simple'
		);
		$result = Libraries::locate('adapters.template.view');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests locating service objects.  These tests may fail if not run on a stock install, as other
	 * objects may preceed the core objects in load order.
	 *
	 * @return void
	 */
	public function testServiceLocation() {
		$this->assertNull(Libraries::locate('adapters.view', 'File'));

		$result = Libraries::locate('adapters.template.view', 'File');
		$this->assertEqual('lithium\template\view\adapters\File', $result);

		$result = Libraries::locate('adapters.storage.cache', 'File');
		$expected = 'lithium\storage\cache\adapters\File';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('dataSources', 'Database');
		$expected = 'lithium\data\source\Database';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('dataSources.database', 'MySql');
		$expected = 'lithium\data\source\database\adapter\MySql';
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateApp() {
		$result = Libraries::locate('controllers', 'HelloWorld');
		$expected = 'app\controllers\HelloWorldController';
		$this->assertEqual($expected, $result);
	}

}

?>