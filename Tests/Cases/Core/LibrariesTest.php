<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright	 Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license	   http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Cases\Core;

use stdClass;
use SplFileInfo;
use Lithium\Util\Inflector;
use Lithium\Core\Libraries;

class LibrariesTest extends \Lithium\Test\Unit {

	protected $_cache = array();

	public function setUp() {
		$this->_cache = Libraries::cache();
		Libraries::cache(false);
		$this->hasApp = preg_match('/app$/', LITHIUM_APP_PATH);
	}

	public function tearDown() {
		Libraries::cache(false);
		Libraries::cache($this->_cache);
		unset($this->hasApp);
	}

	public function testNamespaceToFileTranslation() {
		$result = Libraries::path('\Lithium\Core\Libraries');
		$this->assertTrue(strpos($result, '/Lithium/Core/Libraries.php'));
		$this->assertTrue(file_exists($result));
		$this->assertFalse(strpos($result, '\\'));

		$result = Libraries::path('Lithium\Core\Libraries');
		$this->assertTrue(strpos($result, '/Lithium/Core/Libraries.php'));
		$this->assertTrue(file_exists($result));
		$this->assertFalse(strpos($result, '\\'));
	}

	public function testPathTemplate() {
		$expected = array('{:app}/Libraries/{:name}', '{:root}/{:name}');
		$result = Libraries::paths('Libraries');
		$this->assertEqual($expected, $result);

		$this->assertNull(Libraries::locate('AuthAdapter', 'Form'));

		$paths = Libraries::paths();
		$test = array('AuthAdapter' => array('Lithium\Security\Auth\Adapter\{:name}'));
		Libraries::paths($test);
		$this->assertEqual($paths + $test, Libraries::paths());

		$class = Libraries::locate('AuthAdapter', 'Form');
		$expected = 'Lithium\Security\Auth\Adapter\Form';
		$this->assertEqual($expected, $class);

		Libraries::paths($paths + array('AuthAdapter' => false));
		$this->assertEqual($paths, Libraries::paths());
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
		$tests = Libraries::find('Lithium', array('recursive' => true, 'path' => '/Tests/Cases'));
		$result = preg_grep('/^Lithium\\\\Tests\\\\Cases\\\\/', $tests);
		$this->assertIdentical($tests, $result);

		$all = Libraries::find('Lithium', array('recursive' => true));
		$result = array_values(preg_grep('/^Lithium\\\\Tests\\\\Cases\\\\/', $all));
		$this->assertIdentical($tests, $result);
		
		if ($this->hasApp) {
			$tests = Libraries::find('app', array('recursive' => true, 'path' => '/Tests/Cases'));
			$result = preg_grep('/^app\\\\tests\\\\cases\\\\/', $tests);
			$this->assertIdentical($tests, $result);
		}
	}

	/**
	 * Tests accessing library configurations.
	 *
	 * @return void
	 */
	public function testLibraryConfigAccess() {
		$result = Libraries::get('Lithium');
		$expected = array(
			'path' => str_replace('\\', '/', realpath(realpath(LITHIUM_LIBRARY_PATH) . '/Lithium')),
			'prefix' => 'Lithium\\',
			'suffix' => '.php',
			'loader' => 'Lithium\\Core\\Libraries::load',
			'includePath' => false,
			'transform' => null,
			'bootstrap' => false,
			'defer' => true,
			'default' => false
		);

		if (!$this->hasApp) {
			$expected['resources'] = str_replace('\\', '/', realpath(realpath(LITHIUM_LIBRARY_PATH) . '/Lithium/resources'));
			$expected['default'] = true;
		}

		$this->assertEqual($expected, $result);
		$this->assertNull(Libraries::get('foo'));

		$result = Libraries::get();
		$this->assertTrue(isset($result['Lithium']));
		$this->assertEqual($expected, $result['Lithium']);
		
		if ($this->hasApp) {
			$this->assertTrue(isset($result['app']));
		}
	}

	/**
	 * Tests the addition and removal of default libraries.
	 *
	 * @return void
	 */
	public function testLibraryAddRemove() {
		$lithium = Libraries::get('Lithium');
		$this->assertFalse(empty($lithium));

		$app = Libraries::get(true);
		$this->assertFalse(empty($app));

		Libraries::remove(array('Lithium', 'app'));

		$result = Libraries::get('Lithium');
		$this->assertTrue(empty($result));

		$result = Libraries::get('app');
		$this->assertTrue(empty($result));

		$result = Libraries::add('Lithium', array('bootstrap' => false) + $lithium);
		$this->assertEqual($lithium, $result);

		$result = Libraries::add('app', array('bootstrap' => false) + $app);
		$this->assertEqual(array('bootstrap' => false) + $app, $result);
	}

	/**
	* Tests that an exception is thrown when a library is added which could not be found.
	*
	* @return void
	*/
	public function testAddInvalidLibrary() {
		$this->expectException("Library `invalid_foo` not found.");
		Libraries::add('invalid_foo');
	}

	/**
	 * Tests that non-prefixed (poorly named or structured) libraries can still be added.
	 *
	 * @return void
	 */
	public function testAddNonPrefixedLibrary() {
		$tmpDir = realpath(Libraries::get(true, 'resources') . '/tmp');
		$this->skipIf(!is_writable($tmpDir), "Can't write to resources directory.");

		$fakeDir = $tmpDir . '/fake';
		$fake = "<?php class Fake {} ?>";
		$fakeFilename = $fakeDir . '/fake.php';
		mkdir($fakeDir);
		file_put_contents($fakeFilename, $fake);

		Libraries::add('bad', array(
			'prefix' => false,
			'path' => $fakeDir,
			'transform' => function($class, $config) { return ''; }
		));

		Libraries::add('fake', array(
			'path' => $fakeDir,
			'includePath' => true,
			'prefix' => false,
			'transform' => function($class, $config) {
				return $config['path'] . '/' . Inflector::underscore($class) . '.php';
			}
		));

		$this->assertFalse(class_exists('Fake', false));
		$this->assertTrue(class_exists('Fake'));
		unlink($fakeFilename);
		rmdir($fakeDir);
		Libraries::remove('fake');
	}

	/**
	 * Tests that non-class files are always filtered out of `find()` results unless an alternate
	 * filter is specified.
	 *
	 * @return void
	 */
	public function testExcludeNonClassFiles() {
		$result = Libraries::find('Lithium');
		$this->assertFalse($result);

		$result = Libraries::find('Lithium', array('namespaces' => true));

		$this->assertTrue(in_array('Lithium\Action', $result));
		$this->assertTrue(in_array('Lithium\Core', $result));
		$this->assertTrue(in_array('Lithium\Util', $result));

		$this->assertFalse(in_array('Lithium\LICENSE.txt', $result));
		$this->assertFalse(in_array('Lithium\Readme.wiki', $result));

		$this->assertFalse(Libraries::find('Lithium'));
		$result = Libraries::find('Lithium', array('path' => '/test/filter/reporter/template'));
		$this->assertFalse($result);

		$result = Libraries::find('Lithium', array(
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
		$this->expectException('Failed to load class `SomeInvalidLibrary` from path ``.');
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

	public function testCacheControl() {
		$this->assertNull(Libraries::path('Foo'));
		$cache = Libraries::cache();
		Libraries::cache(array('Foo' => 'Bar'));
		$this->assertEqual('Bar', Libraries::path('Foo'));

		Libraries::cache(false);
		Libraries::cache($cache);
	}

	/**
	 * Tests recursive and non-recursive searching through libraries with paths.
	 *
	 * @return void
	 */
	public function testFindingClasses() {
		$result = Libraries::find('Lithium', array(
			'recursive' => true, 'path' => '/Tests/Cases', 'filter' => '/LibrariesTest/'
		));
		$this->assertIdentical(array(__CLASS__), $result);

		$result = Libraries::find('Lithium', array(
			'path' => '/Tests/Cases/', 'filter' => '/LibrariesTest/'
		));
		$this->assertIdentical(array(), $result);

		$result = Libraries::find('Lithium', array(
			'path' => '/Tests/Cases/Core', 'filter' => '/LibrariesTest/'
		));
		$this->assertIdentical(array(__CLASS__), $result);
		$count = Libraries::find('Lithium', array('recursive' => true));
		$count2 = Libraries::find(true, array('recursive' => true));
		$this->assertTrue($count < $count2);

		$result = Libraries::find('foo', array('recursive' => true));
		$this->assertNull($result);
	}

	public function testFindingClassesAndNamespaces() {
		$result = Libraries::find('Lithium', array('namespaces' => true));
		$this->assertTrue(in_array('Lithium\Net', $result));
		$this->assertTrue(in_array('Lithium\Test', $result));
		$this->assertTrue(in_array('Lithium\Util', $result));
		$this->assertFalse(in_array('Lithium\Readme', $result));
		$this->assertFalse(in_array('Lithium\Readme.wiki', $result));
	}

	public function testFindingClassesWithExclude() {
		$options = array(
			'recursive' => true,
			'filter' => false,
			'exclude' => '/\w+Test$|webroot|index$|^app\\\\config|^\w+\\\\views\/|\./'
		);
		$classes = Libraries::find('Lithium', $options);

		$this->assertTrue(in_array('Lithium\Util\Set', $classes));
		$this->assertTrue(in_array('Lithium\Util\Collection', $classes));
		$this->assertTrue(in_array('Lithium\Core\Libraries', $classes));
		$this->assertTrue(in_array('Lithium\Action\Dispatcher', $classes));

		$this->assertFalse(in_array('Lithium\Tests\Integration\Data\SourceTest', $classes));
		$this->assertFalse(preg_grep('/\w+Test$/', $classes));

		$expected = Libraries::find('Lithium', array(
			'filter' => '/\w+Test$/', 'recursive' => true
		));
		$result = preg_grep('/\w+Test/', $expected);
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateAll() {
		$result = Libraries::locate('Tests');
		$this->assertTrue(count($result) > 30);

		$expected = array(
			'Lithium\Template\View\Adapter\File',
			'Lithium\Template\View\Adapter\Simple'
		);
		$result = Libraries::locate('Adapter.Template.View');
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('Test.Filter');
		$this->assertTrue(count($result) >= 4);
		$this->assertTrue(in_array('Lithium\Test\Filter\Affected', $result));
		$this->assertTrue(in_array('Lithium\Test\Filter\Complexity', $result));
		$this->assertTrue(in_array('Lithium\Test\Filter\Coverage', $result));
		$this->assertTrue(in_array('Lithium\Test\Filter\Profiler', $result));
	}

	public function testServiceLocateInstantiation() {
		$result = Libraries::instance('Adapter.Template.View', 'Simple');
		$this->assertTrue(is_a($result, 'Lithium\Template\View\Adapter\Simple'));
		$this->expectException("Class `Foo` of type `Adapter.Template.View` not found.");
		$result = Libraries::instance('Adapter.Template.View', 'Foo');
	}

	public function testServiceLocateAllCommands() {
		$result = Libraries::locate('Command');
		$this->assertTrue(count($result) > 7);

		$expected = array('Lithium\Console\Command\G11n\Extract');
		$result = Libraries::locate('Command.G11n');
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests locating service objects.  These tests may fail if not run on a stock install, as other
	 * objects may preceed the core objects in load order.
	 *
	 * @return void
	 */
	public function testServiceLocation() {
		$this->assertNull(Libraries::locate('Adapter', 'File'));
		$this->assertNull(Libraries::locate('Adapter.View', 'File'));
		$this->assertNull(Libraries::locate('Invalid_package', 'InvalidClass'));

		$result = Libraries::locate('Adapter.Template.View', 'File');
		$this->assertEqual('Lithium\Template\View\Adapter\File', $result);

		$result = Libraries::locate('Adapter.Storage.Cache', 'File');
		$expected = 'Lithium\Storage\Cache\Adapter\File';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('Data.Source', 'Database');
		$expected = 'Lithium\Data\Source\Database';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate('Adapter.Data.Source.Database', 'MySql');
		$expected = 'Lithium\Data\Source\Database\Adapter\MySql';
		$this->assertEqual($expected, $result);

		$result = Libraries::locate(null, '\Lithium\Data\Source\Database');
		$expected = '\Lithium\Data\Source\Database';
		$this->assertEqual($expected, $result);

		$expected = new stdClass();
		$result = Libraries::locate(null, $expected);
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateApp() {
		$this->skipIf(!$this->hasApp, 'Running in standalone mode.');
		$result = Libraries::locate('Controllers', 'HelloWorld');
		$expected = 'app\Controllers\HelloWorldController';
		$this->assertEqual($expected, $result);

		// Tests caching of paths
		$result = Libraries::locate('Controllers', 'HelloWorld');
		$this->assertEqual($expected, $result);
	}

	public function testServiceLocateCommand() {
		$result = Libraries::locate('Command.G11n', 'Extract');
		$expected = 'Lithium\Console\Command\G11n\Extract';
		$this->assertEqual($expected, $result);
	}

	public function testCaseSensitivePathLookups() {
		Libraries::cache(false);
		$library = Libraries::get('Lithium');
		$base = $library['path'] . '/';

		$expected = $base . 'Template/View.php';

		$result = Libraries::path('\Lithium\Template\View');
		$this->assertEqual($expected, $result);

		$result = Libraries::path('Lithium\Template\View');
		$this->assertEqual($expected, $result);

		$expected = $base . 'Template/View';

		$result = Libraries::path('\Lithium\Template\View', array('dirs' => true));
		$this->assertEqual($expected, $result);

		$result = Libraries::path('Lithium\Template\View', array('dirs' => true));
		$this->assertEqual($expected, $result);
	}

	public function testPathDirectoryLookups() {
		$library = Libraries::get('Lithium');
		$base = $library['path'] . '/';

		$result = Libraries::path('Lithium\Template\View', array('dirs' => false));
		$expected = $base . 'Template/View.php';
		$this->assertEqual($expected, $result);

		$result = Libraries::path('Lithium\Template\Views', array('dirs' => true));
		$this->assertNull($result);
	}

	public function testFindingClassesWithCallableFilters() {
		$result = Libraries::find('Lithium', array(
			'recursive' => true, 'path' => '/Tests/Cases', 'format' => function($file, $config) {
				return new SplFileInfo($file);
			},
			'filter' => function($file) {
				if ($file->getFilename() === 'LibrariesTest.php') {
					return $file;
				}
			}
		));
		$this->assertEqual(1, count($result));
		$this->assertIdentical(__FILE__, $result[0]->getRealPath());
	}

	public function testFindingClassesWithCallableExcludes() {
		$result = Libraries::find('Lithium', array(
			'recursive' => true, 'path' => '/Tests/Cases',
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
		$result = Libraries::find('Lithium', array(
			'path' => '/Console/Command/Create/Template',
			'namespaces' => false,
			'suffix' => false,
			'filter' => false,
			'exclude' => false,
			'format' => function ($file, $config) {
				return basename($file);
			}
		));
		$this->assertTrue(count($result) > 3);
		$this->assertTrue(array_search('controller.txt.php', $result) !== false);
		$this->assertTrue(array_search('model.txt.php', $result) !== false);
		$this->assertTrue(array_search('plugin.phar.gz', $result) !== false);
	}

	public function testLocateWithDotSyntax() {
		$expected = 'Lithium\Template\Helper\Html';
		$result = Libraries::locate('Helper', 'Lithium.Html');
		$this->assertEqual($expected, $result);
	}

	public function testLocateCommandInLithium() {
		$expected = array(
			'Lithium\Console\Command\Create',
			'Lithium\Console\Command\G11n',
			'Lithium\Console\Command\Help',
			'Lithium\Console\Command\Library',
			'Lithium\Console\Command\Route',
			'Lithium\Console\Command\Test'
		);
		$result = Libraries::locate('Command', null, array(
			'library' => 'Lithium', 'recursive' => false
		));
		$this->assertEqual($expected, $result);
	}

	public function testLocateCommandInLithiumRecursiveTrue() {
		$expected = array(
			'Lithium\Console\Command\Create',
			'Lithium\Console\Command\G11n',
			'Lithium\Console\Command\Help',
			'Lithium\Console\Command\Library',
			'Lithium\Console\Command\Route',
			'Lithium\Console\Command\Test',
			'Lithium\Console\Command\G11n\Extract',
			'Lithium\Console\Command\Create\Controller',
			'Lithium\Console\Command\Create\Mock',
			'Lithium\Console\Command\Create\Model',
			'Lithium\Console\Command\Create\Test',
			'Lithium\Console\Command\Create\View'
		);
		$result = Libraries::locate('Command', null, array(
			'library' => 'Lithium', 'recursive' => true
		));
		$this->assertEqual($expected, $result);
	}

	public function testLocateWithLibrary() {
	    $expected = array();
	    $result = (array) Libraries::locate("Tests", null, array('library' => 'doesntExist'));
	    $this->assertIdentical($expected, $result);
	}

	public function testLocateWithLithiumLibrary() {
	    $expected = (array) Libraries::find('Lithium', array(
		    'path' => '/Tests',
			'preFilter' => '/[A-Z][A-Za-z0-9]+\Test\./',
	        'recursive' => true,
	        'filter' => '/\\\(Cases|Integration|Functional|Mocks)\\\/'
	    ));
	    $result = (array) Libraries::locate("Tests", null, array('library' => 'Lithium'));
	    $this->assertEqual($expected, $result);
	}

	public function testLocateWithTestAppLibrary() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp);
		Libraries::add('test_app', array('path' => $testApp));

		mkdir($testApp . '/Tests/Cases/Models', 0777, true);
		file_put_contents($testApp . '/Tests/Cases/Models/UserTest.php',
		"<?php namespace test_app\\tests\\cases\\models;\n
			class UserTest extends \\Lithium\\Test\\Unit { public function testMe() {
				\$this->assertTrue(true);
			}}"
		);
		Libraries::cache(false);

		$expected = array('test_app\\Tests\\Cases\\Models\\UserTest');
	    $result = (array) Libraries::locate("Tests", null, array('library' => 'test_app'));
	    $this->assertEqual($expected, $result);

		$this->_cleanUp();
	}

	/**
	 * Tests that `Libraries::realPath()` correctly resolves paths to files inside Phar archives.
	 *
	 * @return void
	 */
	public function testPathsInPharArchives() {
		$base = Libraries::get('Lithium', 'path');
		$path = "{$base}/Console/Command/Create/Template/app.phar.gz";

		$expected = "phar://{$path}/controllers/HelloWorldController.php";
		$result = Libraries::realPath($expected);
		$this->assertEqual($expected, $result);
	}

	public function testClassInstanceWithSubnamespace() {
		$testApp = Libraries::get(true, 'resources') . '/tmp/tests/test_app';
		mkdir($testApp);
		$paths = array("/Controllers", "/Controllers/Admin");

		foreach ($paths as $path) {
			$namespace = str_replace('/', '\\', $path);
			$dotsyntax = str_replace('/', '.', trim($path, '/'));
			$class = 'Posts';

			Libraries::add('test_app', array('path' => $testApp));

			mkdir($testApp . $path, 0777, true);
			file_put_contents($testApp . $path . "/{$class}Controller.php",
			"<?php namespace test_app{$namespace};\n
				class {$class}Controller extends \\Lithium\\Action\\Controller {
				public function index() {
					return true;
				}}"
			);
			Libraries::cache(false);

			$expected = "test_app{$namespace}\\{$class}Controller";
			$instance = Libraries::instance($dotsyntax, "Posts", array('library' => 'test_app'));
		    $result = get_class($instance);
		    $this->assertEqual($expected, $result, "{$path} did not work");
		}

		$this->_cleanUp();
	}
}

?>
