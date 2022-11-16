<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

use Phar;
use SplFileInfo;
use lithium\core\Libraries;
use lithium\tests\mocks\core\MockInitMethod;
use lithium\util\Inflector;
use stdClass;

class LibrariesTest extends \lithium\test\Unit {

	public $hasApp;

	protected $_cache = [];

	public function setUp() {
		$this->_cache = Libraries::cache();
		Libraries::cache(false);
		$this->hasApp = Libraries::get(true, 'name') !== 'lithium';
	}

	public function tearDown() {
		Libraries::cache(false);
		Libraries::cache($this->_cache);
		unset($this->hasApp);
		$this->_cleanUp();
	}

	public function testNamespaceToFileTranslation() {
		$ds = DIRECTORY_SEPARATOR;
		$invalidDS = $ds === '/' ? '\\' : '/';

		$result = Libraries::path('\lithium\core\Libraries');
		$this->assertNotEmpty(strpos($result, "{$ds}lithium{$ds}core{$ds}Libraries.php"));
		$this->assertFileExists($result);
		$this->assertFalse(strpos($result, $invalidDS));

		$result = Libraries::path('lithium\core\Libraries');
		$this->assertNotEmpty(strpos($result, "{$ds}lithium{$ds}core{$ds}Libraries.php"));
		$this->assertFileExists($result);
		$this->assertFalse(strpos($result, $invalidDS));
	}

	public function testPathTemplate() {
		$expected = ['{:app}/libraries/{:name}', '{:root}/{:name}'];
		$result = Libraries::paths('libraries');
		$this->assertEqual($expected, $result);

		$this->assertNull(Libraries::locate('authAdapter', 'Form'));

		$paths = Libraries::paths();
		$test = ['authAdapter' => ['lithium\security\auth\adapter\{:name}']];
		Libraries::paths($test);
		$this->assertEqual($paths + $test, Libraries::paths());

		$class = Libraries::locate('authAdapter', 'Form');
		$expected = 'lithium\security\auth\adapter\Form';
		$this->assertEqual($expected, $class);

		Libraries::paths($paths + ['authAdapter' => false]);
		$this->assertEqual($paths, Libraries::paths());
	}

	public function testPathTransform() {
		$expected = 'Library/Class/Separated/By/Underscore';
		$result = Libraries::path('Library_Class_Separated_By_Underscore', [
			'prefix' => 'Library_',
			'transform' => function ($class, $options) {
				return str_replace('_', '/', $class);
			}
		]);
		$this->assertEqual($expected, $result);

		$expected = 'Library/Class/Separated/By/Nothing';
		$result = Libraries::path('LibraryClassSeparatedByNothing', [
			'prefix' => 'Library',
			'transform' => ['/([a-z])([A-Z])/', '$1/$2']
		]);
		$this->assertEqual($expected, $result);
	}

	public function testPathFiltering() {
		$tests = Libraries::find('lithium', ['recursive' => true, 'path' => '/tests/cases']);
		$result = preg_grep('/^lithium\\\\tests\\\\cases\\\\/', $tests);
		$this->assertIdentical($tests, $result);

		$all = Libraries::find('lithium', ['recursive' => true]);
		$result = array_values(preg_grep('/^lithium\\\\tests\\\\cases\\\\/', $all));
		$this->assertIdentical($tests, $result);

		if ($this->hasApp) {
			$config = Libraries::get(true);
			$tests = Libraries::find($config['name'], [
				'recursive' => true, 'path' => '/tests/cases']
			);
			$prefix = preg_quote($config['prefix']);
			$result = preg_grep('/^' . $prefix . 'tests\\\\cases\\\\/', $tests);
			$this->assertIdentical($tests, $result);
		}
	}

	/**
	 * Tests accessing library configurations.
	 */
	public function testLibraryConfigAccess() {
		$config = Libraries::get('lithium'); // => ['path' => '/path/to/lithium', ...]
		$expected = [
			'name' => 'lithium',
			'path' => str_replace('\\', '/', realpath(Libraries::get('lithium', 'path'))),
			'prefix' => 'lithium\\',
			'suffix' => '.php',
			'loader' => 'lithium\\core\\Libraries::load',
			'includePath' => false,
			'transform' => null,
			'bootstrap' => false,
			'defer' => true,
			'default' => false
		];

		if (!$this->hasApp) {
			$expected['resources'] = sys_get_temp_dir();
			$expected['default'] = true;
		}

		$this->assertEqual($expected, $config);
		$this->assertNull(Libraries::get('foo'));

		$configs = Libraries::get(); // => ['lithium' => ['path' => ...], 'myapp' => [...], ...]
		$this->assertArrayHasKey('lithium', $configs);
		$this->assertEqual($expected, $configs['lithium']);

		if ($this->hasApp) {
			$this->assertArrayHasKey(Libraries::get(true, 'name'), $configs);
		}

		$configs = Libraries::get(['lithium']); // => ['lithium' => ['path' => '...', ...]]
		$this->assertEqual(['lithium'], array_keys($configs));
		$this->assertEqual($expected, $configs['lithium']);

		$prefixes = Libraries::get(['lithium'], 'prefix'); // => ['lithium' => 'lithium\\']
		$this->assertEqual(['lithium' => 'lithium\\'], $prefixes);

		$allPre = Libraries::get(null, 'prefix'); // => ['my' => 'my\\', 'lithium' => 'lithium\\']
		$this->assertNotEmpty($allPre);
		$this->assertEqual(array_keys(Libraries::get()), array_keys($allPre));

		foreach ($allPre as $prefix) {
			$this->assertTrue(is_string($prefix) || is_bool($prefix));
		}

		$library = Libraries::get('lithium\core\Libraries'); // 'lithium'
		$this->assertEqual('lithium', $library);
		$this->assertNull(Libraries::get('foo\bar\baz'));
	}

	public function testLibraryNameConfigAccess() {
		$original = Libraries::get(true);
		Libraries::remove($original['name']);

		Libraries::add('myapp', ['default' => true]);
		$this->assertIdentical('myapp', Libraries::get(true, 'name'));

		Libraries::remove('myapp');
		Libraries::add($original['name'], $original);
	}

	/**
	 * Tests the addition and removal of default libraries.
	 */
	public function testLibraryAddRemove() {
		$lithium = Libraries::get('lithium');
		$this->assertNotEmpty($lithium);

		$app = Libraries::get(true);
		$this->assertNotEmpty($app);

		Libraries::remove(['lithium', 'app']);

		$result = Libraries::get('lithium');
		$this->assertEmpty($result);

		$result = Libraries::get('app');
		$this->assertEmpty($result);

		$result = Libraries::add('lithium', ['bootstrap' => false] + $lithium);
		$this->assertEqual($lithium, $result);

		$result = Libraries::add('app', ['bootstrap' => false] + $app);
		$this->assertEqual(['bootstrap' => false] + $app, $result);
	}

	/**
	 * Tests that an exception is thrown when a library is added which could not be found.
	 */
	public function testAddInvalidLibrary() {
		$this->assertException("Library `invalid_foo` not found.", function() {
			Libraries::add('invalid_foo');
		});
	}

	/**
	 * Tests that non-prefixed (poorly named or structured) libraries can still be added.
	 */
	public function testAddNonPrefixedLibrary() {
		$tmpDir = realpath(Libraries::get(true, 'resources') . '/tmp');
		$this->skipIf(!is_writable($tmpDir), "Can't write to resources directory.");

		$fakeDir = $tmpDir . '/fake';
		$fake = "<?php class Fake {} ?>";
		$fakeFilename = $fakeDir . '/fake.php';
		mkdir($fakeDir, 0777, true);
		file_put_contents($fakeFilename, $fake);

		Libraries::add('bad', [
			'prefix' => false,
			'path' => $fakeDir,
			'transform' => function($class, $config) { return ''; }
		]);

		Libraries::add('fake', [
			'path' => $fakeDir,
			'includePath' => true,
			'prefix' => false,
			'transform' => function($class, $config) {
				return $config['path'] . '/' . Inflector::underscore($class) . '.php';
			}
		]);

		$this->assertFalse(class_exists('Fake', false));
		$this->assertTrue(class_exists('Fake'));
		unlink($fakeFilename);
		rmdir($fakeDir);
		Libraries::remove('fake');
	}

	/**
	 * Tests that non-class files are always filtered out of `find()` results unless an alternate
	 * filter is specified.
	 */
	public function testExcludeNonClassFiles() {
		$result = Libraries::find('lithium');
		$this->assertEmpty($result);

		$result = Libraries::find('lithium', ['namespaces' => true]);

		$this->assertTrue(in_array('lithium\action', $result));
		$this->assertTrue(in_array('lithium\core', $result));
		$this->assertTrue(in_array('lithium\util', $result));

		$this->assertFalse(in_array('lithium\LICENSE.txt', $result));
		$this->assertFalse(in_array('lithium\readme.wiki', $result));

		$this->assertEmpty(Libraries::find('lithium'));
		$result = Libraries::find('lithium', ['path' => '/test/filter/reporter/template']);
		$this->assertEmpty($result);

		$result = Libraries::find('lithium', [
			'path' => '/test/filter/reporter/template',
			'namespaces' => true
		]);
		$this->assertEmpty($result);
	}

	public function testSearchOptimizedNamespacesWithOnlyDir() {
		$result = Libraries::find('lithium', [
			'namespaces' => true,
			'filter' => false
		]);
		$this->assertFalse(in_array('lithium\LICENSE.txt', $result));
	}

	/**
	 * Tests the loading of libraries
	 */
	public function testLibraryLoad() {
		$expected = 'Failed to load class `SomeInvalidLibrary` from path ``.';
		$this->assertException($expected, function() {
			Libraries::load('SomeInvalidLibrary', true);
		});
	}

	/**
	 * Tests path caching by calling `path()` twice.
	 */
	public function testPathCaching() {
		$this->assertEmpty(Libraries::cache(false));
		$path = Libraries::path(__CLASS__);
		$this->assertEqual(__FILE__, realpath($path));

		$result = Libraries::cache();
		$this->assertEqual(realpath($result[__CLASS__]), __FILE__);
	}

	public function testCacheControl() {
		$this->assertNull(Libraries::path('Foo'));
		$cache = Libraries::cache();
		Libraries::cache(['Foo' => 'Bar']);
		$this->assertEqual('Bar', Libraries::path('Foo'));

		Libraries::cache(false);
		Libraries::cache($cache);
	}

	/**
	 * Tests recursive and non-recursive searching through libraries with paths.
	 */
	public function testFindingClasses() {
		$result = Libraries::find('lithium', [
			'recursive' => true, 'path' => '/tests/cases', 'filter' => '/LibrariesTest/'
		]);
		$this->assertIdentical([__CLASS__], $result);

		$result = Libraries::find('lithium', [
			'path' => '/tests/cases/', 'filter' => '/LibrariesTest/'
		]);
		$this->assertIdentical([], $result);

		$result = Libraries::find('lithium', [
			'path' => '/tests/cases/core', 'filter' => '/LibrariesTest/'
		]);
		$this->assertIdentical([__CLASS__], $result);

		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp . '/tests/cases/models', 0777, true);
		Libraries::add('test_app', ['path' => $testApp]);

		$body = <<<EOD
<?php
namespace test_app\\tests\\cases\\models;
class UserTest extends \\lithium\\test\\Unit {
	public function testMe() {
		\$this->assertTrue(true);
	}
}
?>
EOD;

		$filepath = $testApp . '/tests/cases/models/UserTest.php';
		file_put_contents($filepath, $body);

		$count = Libraries::find('lithium', ['recursive' => true]);
		$count2 = Libraries::find(true, ['recursive' => true]);
		$this->assertTrue($count < $count2);

		$result = Libraries::find('foo', ['recursive' => true]);
		$this->assertNull($result);
	}

	public function testFindingClassesAndNamespaces() {
		$result = Libraries::find('lithium', ['namespaces' => true]);
		$this->assertTrue(in_array('lithium\net', $result));
		$this->assertTrue(in_array('lithium\test', $result));
		$this->assertTrue(in_array('lithium\util', $result));
		$this->assertFalse(in_array('lithium\readme', $result));
		$this->assertFalse(in_array('lithium\readme.wiki', $result));
	}

	public function testFindingClassesWithExclude() {
		$options = [
			'recursive' => true,
			'filter' => false,
			'exclude' => '/\w+Test$|webroot|index$|^app\\\\config|^\w+\\\\views\/|\./'
		];
		$classes = Libraries::find('lithium', $options);

		$this->assertTrue(in_array('lithium\util\Set', $classes));
		$this->assertTrue(in_array('lithium\util\Collection', $classes));
		$this->assertTrue(in_array('lithium\core\Libraries', $classes));
		$this->assertTrue(in_array('lithium\action\Dispatcher', $classes));

		$this->assertFalse(in_array('lithium\tests\integration\data\SourceTest', $classes));
		$this->assertEmpty(preg_grep('/\w+Test$/', $classes));

		$expected = Libraries::find('lithium', [
			'filter' => '/\w+Test$/', 'recursive' => true
		]);
		$result = preg_grep('/\w+Test/', $expected);
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateAll() {
		$result = Libraries::locate('tests');
		$this->assertTrue(count($result) > 30);

		$expected = [
			'lithium\template\view\adapter\File',
			'lithium\template\view\adapter\Simple'
		];
		$result = Libraries::locate('adapter.template.view');
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('test.filter');
		$this->assertTrue(count($result) >= 4);
		$this->assertTrue(in_array('lithium\test\filter\Affected', $result));
		$this->assertTrue(in_array('lithium\test\filter\Complexity', $result));
		$this->assertTrue(in_array('lithium\test\filter\Coverage', $result));
		$this->assertTrue(in_array('lithium\test\filter\Profiler', $result));
	}

	public function testServiceLocateInstantiation() {
		$result = Libraries::instance('adapter.template.view', 'Simple');
		$this->assertInstanceOf('lithium\template\view\adapter\Simple', $result);

		$expected = "Class `Foo` of type `adapter.template.view` not found.";
		$this->assertException($expected, function() {
			Libraries::instance('adapter.template.view', 'Foo');
		});
	}

	public function testServiceLocateAllCommands() {
		$result = Libraries::locate('command');
		$this->assertTrue(count($result) > 7);

		$expected = ['lithium\console\command\g11n\Extract'];
		$result = Libraries::locate('command.g11n');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests locating service objects.  These tests may fail if not run on a stock install, as other
	 * objects may preceed the core objects in load order.
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

		$expected = new stdClass();
		$result = Libraries::locate(null, $expected);
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateApp() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp, 0777, true);
		Libraries::add('test_app', ['path' => $testApp]);

		mkdir($testApp . '/controllers', 0777, true);
		$body = <<<EOD
<?php
namespace test_app\\controllers;
class HelloWorldCustomTestController extends \\lithium\\action\\Controller {
	public function index() {}
}
?>
EOD;
		$filepath = $testApp . '/controllers/HelloWorldCustomTestController.php';
		file_put_contents($filepath, $body);
		Libraries::cache(false);

		$result = Libraries::locate('controllers', 'HelloWorldCustomTest');
		$expected = 'test_app\controllers\HelloWorldCustomTestController';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('controllers', 'HelloWorldCustomTest');
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

		$expected = realpath($base . 'template/View.php');

		$result = Libraries::path('\lithium\template\View');
		$this->assertEqual($expected, $result);

		$result = Libraries::path('lithium\template\View');
		$this->assertEqual($expected, $result);

		$expected = realpath($base . 'template/view');

		$result = Libraries::path('\lithium\template\view', ['dirs' => true]);
		$this->assertEqual($expected, $result);

		$result = Libraries::path('lithium\template\view', ['dirs' => true]);
		$this->assertEqual($expected, $result);
	}

	public function testPathDirectoryLookups() {
		$library = Libraries::get('lithium');
		$base = $library['path'] . '/';

		$result = Libraries::path('lithium\template\View', ['dirs' => true]);
		$expected = realpath($base . 'template/View.php');
		$this->assertEqual($expected, $result);

		$result = Libraries::path('lithium\template\views', ['dirs' => true]);
		$this->assertNull($result);
	}

	public function testFindingClassesWithCallableFilters() {
		$result = Libraries::find('lithium', [
			'recursive' => true, 'path' => '/tests/cases', 'format' => function($file, $config) {
				return new SplFileInfo($file);
			},
			'filter' => function($file) {
				if ($file->getFilename() === 'LibrariesTest.php') {
					return $file;
				}
			}
		]);
		$this->assertCount(1, $result);
		$this->assertIdentical(__FILE__, $result[0]->getRealPath());
	}

	public function testFindingClassesWithCallableExcludes() {
		$result = Libraries::find('lithium', [
			'recursive' => true, 'path' => '/tests/cases',
			'format' => function($file, $config) {
				return new SplFileInfo($file);
			},
			'filter' => null,
			'exclude' => function($file) {
				if ($file->getFilename() === 'LibrariesTest.php') {
					return true;
				}
			}
		]);
		$this->assertCount(1, $result);
		$this->assertIdentical(__FILE__, $result[0]->getRealPath());
	}

	public function testFindWithOptions() {
		$result = Libraries::find('lithium', [
			'path' => '/console/command/create/template',
			'namespaces' => false,
			'suffix' => false,
			'filter' => false,
			'exclude' => false,
			'format' => function ($file, $config) {
				return basename($file);
			}
		]);
		$this->assertTrue(count($result) > 3);
		$this->assertNotIdentical(array_search('controller.txt.php', $result), false);
		$this->assertNotIdentical(array_search('model.txt.php', $result), false);
	}

	public function testLocateWithDotSyntax() {
		$expected = 'lithium\template\helper\Html';
		$result = Libraries::locate('helper', 'lithium.Html');
		$this->assertEqual($expected, $result);
	}

	public function testLocateCommandInLithium() {
		$expected = [
			'lithium\console\command\Create',
			'lithium\console\command\G11n',
			'lithium\console\command\Help',
			'lithium\console\command\Route',
			'lithium\console\command\Test'
		];
		$result = Libraries::locate('command', null, [
			'library' => 'lithium', 'recursive' => false
		]);
		$this->assertEqual($expected, $result);
	}

	public function testLocateCommandInLithiumRecursiveTrue() {
		$expected = [
			'lithium\console\command\Create',
			'lithium\console\command\G11n',
			'lithium\console\command\Help',
			'lithium\console\command\Route',
			'lithium\console\command\Test',
			'lithium\console\command\g11n\Extract',
			'lithium\console\command\create\Controller',
			'lithium\console\command\create\Mock',
			'lithium\console\command\create\Model',
			'lithium\console\command\create\Test',
			'lithium\console\command\create\View'
		];
		$result = Libraries::locate('command', null, [
			'library' => 'lithium', 'recursive' => true
		]);
		$this->assertEqual($expected, $result);
	}

	public function testLocateWithLibrary() {
		$expected = [];
		$result = (array) Libraries::locate("tests", null, ['library' => 'doesntExist']);
		$this->assertIdentical($expected, $result);
	}

	public function testLocateWithLithiumLibrary() {
		$expected = (array) Libraries::find('lithium', [
			'path' => '/tests',
			'preFilter' => '/[A-Z][A-Za-z0-9]+Test\./',
			'recursive' => true,
			'filter' => '/cases|integration|functional|mocks/'
		]);
		$result = (array) Libraries::locate("tests", null, ['library' => 'lithium']);
		$this->assertEqual($expected, $result);
	}

	public function testLocateWithTestAppLibrary() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp, 0777, true);
		Libraries::add('test_app', ['path' => $testApp]);

		mkdir($testApp . '/tests/cases/models', 0777, true);
		$body = <<<EOD
<?php
namespace test_app\\tests\\cases\\models;
class UserTest extends \\lithium\\test\\Unit {
	public function testMe() {
		\$this->assertTrue(true);
	}
}
?>
EOD;
		$filepath = $testApp . '/tests/cases/models/UserTest.php';
		file_put_contents($filepath, $body);
		Libraries::cache(false);

		$expected = ['test_app\tests\cases\models\UserTest'];
		$result = (array) Libraries::locate("tests", null, ['library' => 'test_app']);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that `Libraries::realPath()` correctly resolves paths to files inside Phar archives.
	 */
	public function testPathsInPharArchives() {
		$this->skipIf(!Phar::canWrite(), '`Phar support is read only.');
		$path = realpath(Libraries::get(true, 'resources') . '/tmp/tests');

		$file = $path . '/test.phar';
		$phar = new Phar($file);
		$phar->addFromString('/controllers/HelloWorldController.php', '<?php "Hello World" ?>');

		$expected = "phar://{$file}/controllers/HelloWorldController.php";
		$result = Libraries::realPath($expected);
		$this->assertEqual($expected, $result);

		unset($phar);
		unlink($file);
	}

	public function testInstanceWithClasses() {
		$result = Libraries::instance(null, 'view', [], [
			'view' => 'lithium\template\view\adapter\Simple'
		]);
		$this->assertInstanceOf('lithium\template\view\adapter\Simple', $result);
	}

	public function testInstanceWithObject() {
		$result = Libraries::instance(null, new stdClass());
		$this->assertInstanceOf('stdClass', $result);
	}

	public function testInstanceWithSubnamespace() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp);
		$paths = ["/controllers", "/controllers/admin"];

		foreach ($paths as $path) {
			$namespace = str_replace('/', '\\', $path);
			$dotsyntax = str_replace('/', '.', trim($path, '/'));
			$class = 'Posts';

			Libraries::add('test_app', ['path' => $testApp]);

			$body = <<<EOD
<?php
namespace test_app{$namespace};
class {$class}Controller extends \\lithium\\action\\Controller {
	public function index() {
		return true;
	}
}
?>
EOD;
			mkdir($testApp . $path, 0777, true);
			$filepath = $testApp . $path . "/{$class}Controller.php";
			file_put_contents($filepath, $body);
			Libraries::cache(false);

			$expected = "test_app{$namespace}\\{$class}Controller";
			$instance = Libraries::instance($dotsyntax, "Posts", ['library' => 'test_app']);
			$result = get_class($instance);
			$this->assertEqual($expected, $result, "{$path} did not work");
		}
	}

	/**
	 * Tests that `Libraries::map()` and `Libraries::unmap()`
	 *
	 */
	public function testMapUnmap() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp, 0777, true);
		Libraries::add('test_app', ['path' => $testApp]);

		mkdir($testApp . '/lib', 0777);
		mkdir($testApp . '/_patch', 0777);

		$lib = <<<EOD
<?php
namespace test_app\\lib;
class LibTest {
	public function testMe() {
		return 'core class';
	}
}
?>
EOD;
		file_put_contents($testApp . '/lib/LibTest.php', $lib);

		$patch = <<<EOD
<?php
namespace test_app\\lib;
class LibTest {
	public function testMe() {
		return 'patched class';
	}
}
?>
EOD;
		file_put_contents($testApp . '/_patch/PatchedLibTest.php', $patch);

		$expected = $result = Libraries::realPath($testApp . '/lib/LibTest.php');
		$result = Libraries::path('test_app\lib\LibTest');

		$this->assertEqual($expected, $result);

		Libraries::map([
			'test_app\lib\LibTest' => $testApp . '/_patch/PatchedLibTest.php'
		]);

		$expected = $result = Libraries::realPath($testApp . '/_patch/PatchedLibTest.php');
		$result = Libraries::path('test_app\lib\LibTest');

		Libraries::unmap(['test_app\lib\LibTest']);

		$expected = $result = Libraries::realPath($testApp . '/lib/LibTest.php');
		$result = Libraries::path('test_app\lib\LibTest');

		$this->assertEqual($expected, $result);

		Libraries::map([
			'test_app\lib\LibTest' => $testApp . '/_patch/PatchedLibTest.php'
		]);
		Libraries::unmap('test_app\lib\LibTest');

		$expected = $result = Libraries::realPath($testApp . '/lib/LibTest.php');
		$result = Libraries::path('test_app\lib\LibTest');

		Libraries::map([
			'test_app\lib\LibTest' => $testApp . '/_patch/PatchedLibTest.php'
		]);

		$object = new \test_app\lib\LibTest();

		$result = $object->testMe();
		$this->assertEqual('patched class', $result);
	}
}

?>