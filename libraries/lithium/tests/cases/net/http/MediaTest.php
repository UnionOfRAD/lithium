<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\net\http;

use lithium\net\http\Media;
use lithium\action\Request;
use lithium\action\Response;
use lithium\core\Libraries;
use lithium\data\entity\Record;
use lithium\data\collection\RecordSet;

class MediaTest extends \lithium\test\Unit {

	/**
	 * Reset the `Media` class to its default state.
	 *
	 * @return void
	 */
	public function setUp() {
		Media::reset();
	}

	/**
	 * Tests setting, getting and removing custom media types.
	 *
	 * @return void
	 */
	public function testMediaTypes() {
		$result = Media::types();

		$this->assertTrue(is_array($result));
		$this->assertTrue(in_array('json', $result));
		$this->assertFalse(in_array('my', $result));

		$this->assertEqual($result, Media::formats());

		$result = Media::type('json');
		$expected = 'application/json';
		$this->assertEqual($expected, $result['content']);

		$expected = array(
			'view' => false, 'layout' => false, 'cast' => true,
			'encode' => 'json_encode', 'decode' => $result['options']['decode']
		);
		$this->assertEqual($expected, $result['options']);

		Media::type('my', 'text/x-my', array('view' => 'my\custom\View', 'layout' => false));

		$result = Media::types();
		$this->assertTrue(in_array('my', $result));

		$result = Media::type('my');
		$expected = 'text/x-my';
		$this->assertEqual($expected, $result['content']);

		$expected = array(
			'view' => 'my\custom\View', 'template' => null, 'layout' => null,
			'encode' => null, 'decode' => null, 'cast' => true
		);
		$this->assertEqual($expected, $result['options']);

		Media::type('my', false);
		$result = Media::types();
		$this->assertFalse(in_array('my', $result));
	}

	/**
	 * Tests that `Media` will return the correct type name of recognized, registered content types.
	 *
	 * @return void
	 */
	public function testContentTypeDetection() {
		$this->assertNull(Media::type('application/foo'));

		$result = Media::type('application/javascript');
		$this->assertEqual('js', $result['content']);

		$result = Media::type('*/*');
		$this->assertEqual('html', $result['content']);

		$result = Media::type('application/json');
		$this->assertEqual('json', $result['content']);

		$result = Media::type('application/json; charset=UTF-8');
		$this->assertEqual('json', $result['content']);

		$result = Media::type('json');
		$expected = array('content' => 'application/json', 'options' => array(
			'view' => false, 'layout' => false, 'cast' => true,
			'encode' => 'json_encode', 'decode' => $result['options']['decode']
		));
		$this->assertEqual($expected, $result);
	}

	public function testAssetTypeHandling() {
		$result = Media::assets();
		$expected = array('js', 'css', 'image', 'generic');
		$this->assertEqual($expected, array_keys($result));

		$result = Media::assets('css');
		$expected = '.css';
		$this->assertEqual($expected, $result['suffix']);
		$this->assertTrue(isset($result['path']['{:base}/{:library}/css/{:path}']));

		$result = Media::assets('my');
		$this->assertNull($result);

		$result = Media::assets('my', array('suffix' => '.my', 'path' => array(
			'{:base}/my/{:path}' => array('base', 'path')
		)));
		$this->assertNull($result);

		$result = Media::assets('my');
		$expected = '.my';
		$this->assertEqual($expected, $result['suffix']);
		$this->assertTrue(isset($result['path']['{:base}/my/{:path}']));

		$this->assertNull($result['filter']);
		Media::assets('my', array('filter' => array('/my/' => '/your/')));

		$result = Media::assets('my');
		$expected = array('/my/' => '/your/');
		$this->assertEqual($expected, $result['filter']);

		$expected = '.my';
		$this->assertEqual($expected, $result['suffix']);

		Media::assets('my', false);
		$result = Media::assets('my');
		$this->assertNull($result);

		$this->assertEqual('/foo.exe', Media::asset('foo.exe', 'bar'));
	}

	public function testAssetPathGeneration() {
		$result = Media::asset('scheme://host/subpath/file', 'js');
		$expected = 'scheme://host/subpath/file';
		$this->assertEqual($expected, $result);

		$result = Media::asset('subpath/file', 'js');
		$expected = '/js/subpath/file.js';
		$this->assertEqual($expected, $result);

		$result = Media::asset('this.file.should.not.exist', 'css', array('check' => true));
		$this->assertFalse($result);

		$result = Media::asset('debug', 'css', array('check' => 'true', 'library' => 'app'));
		$expected = '/css/debug.css';
		$this->assertEqual($expected, $result);

		$result = Media::asset('debug', 'css', array('timestamp' => true));
		$this->assertPattern('%^/css/debug\.css\?\d+$%', $result);

		$result = Media::asset('debug.css?type=test', 'css', array(
			'check' => 'true', 'base' => 'foo'
		));
		$expected = 'foo/css/debug.css?type=test';
		$this->assertEqual($expected, $result);

		$result = Media::asset('debug.css?type=test', 'css', array(
			'check' => 'true', 'base' => 'foo', 'timestamp' => true
		));
		$this->assertPattern('%^foo/css/debug\.css\?type=test&\d+$%', $result);

		$file = Media::path('css/debug.css', 'bar');
		$this->assertTrue(file_exists($file));
	}

	public function testCustomAssetPathGeneration() {
		Media::assets('my', array('suffix' => '.my', 'path' => array(
			'{:base}/my/{:path}' => array('base', 'path')
		)));

		$result = Media::asset('subpath/file', 'my');
		$expected = '/my/subpath/file.my';
		$this->assertEqual($expected, $result);

		Media::assets('my', array('filter' => array('/my/' => '/your/')));

		$result = Media::asset('subpath/file', 'my');
		$expected = '/your/subpath/file.my';
		$this->assertEqual($expected, $result);

		$result = Media::asset('subpath/file', 'my', array('base' => '/app/path'));
		$expected = '/app/path/your/subpath/file.my';
		$this->assertEqual($expected, $result);

		$result = Media::asset('subpath/file', 'my', array('base' => '/app/path/'));
		$expected = '/app/path//your/subpath/file.my';
		$this->assertEqual($expected, $result);
	}

	public function testMultiLibraryAssetPaths() {
		$result = Media::asset('path/file', 'js', array('library' => 'app', 'base' => '/app/base'));
		$expected = '/app/base/js/path/file.js';
		$this->assertEqual($expected, $result);

		Libraries::add('li3_foo_blog', array(
			'path' => LITHIUM_APP_PATH . '/libraries/plugins/blog',
			'bootstrap' => false,
			'route' => false
		));

		$result = Media::asset('path/file', 'js', array(
			'library' => 'li3_foo_blog', 'base' => '/app/base'
		));
		$expected = '/app/base/blog/js/path/file.js';
		$this->assertEqual($expected, $result);

		Libraries::remove('li3_foo_blog');
	}

	public function testManualAssetPaths() {
		$result = Media::asset('/path/file', 'js', array('base' => '/base'));
		$expected = '/base/path/file.js';
		$this->assertEqual($expected, $result);

		$result = Media::asset('/foo/bar', 'js', array('base' => '/base', 'check' => true));
		$this->assertFalse($result);

		$result = Media::asset('/css/debug', 'css', array('base' => '/base', 'check' => true));
		$expected = '/base/css/debug.css';
		$this->assertEqual($expected, $result);

		$result = Media::asset('/css/debug.css', 'css', array('base' => '/base', 'check' => true));
		$expected = '/base/css/debug.css';
		$this->assertEqual($expected, $result);

		$result = Media::asset('/css/debug.css?foo', 'css', array(
			'base' => '/base', 'check' => true
		));
		$expected = '/base/css/debug.css?foo';
		$this->assertEqual($expected, $result);
	}

	public function testRender() {
		$response = new Response();
		$response->type('json');
		$data = array('something');
		Media::render($response, $data);

		$expected = array('Content-type: application/json');
		$result = $response->headers();
		$this->assertEqual($expected, $result);

		$expected = json_encode($data);
		$result = $response->body();
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that types with decode handlers can properly decode content.
	 *
	 * @return void
	 */
	public function testDecode() {
		$data = array('movies' => array(
			array('name' => 'Shaun of the Dead', 'year' => 2004),
			array('name' => 'V for Vendetta', 'year' => 2005)
		));
		$encoded = '{"movies":[{"name":"Shaun of the Dead","year":2004},';
		$encoded .= '{"name":"V for Vendetta","year":2005}]}';

		$result = Media::decode('json', $encoded);
		$this->assertEqual($data, $result);
	}

	public function testCustomEncodeHandler() {
		$response = new Response();
		$response->type('csv');

		Media::type('csv', 'application/csv', array('encode' => function($data) {
			ob_start();
			$out = fopen('php://output', 'w');
			foreach ($data as $record) {
				fputcsv($out, $record);
			}
			fclose($out);
			return ob_get_clean();
		}));

		$data = array(
			array('John', 'Doe', '123 Main St.', 'Anytown, CA', '91724'),
			array('Jane', 'Doe', '124 Main St.', 'Anytown, CA', '91724')
		);

		Media::render($response, $data);
		$result = $response->body;
		$expected = 'John,Doe,"123 Main St.","Anytown, CA",91724' . "\n";
		$expected .= 'Jane,Doe,"124 Main St.","Anytown, CA",91724' . "\n";
		$this->assertEqual(array($expected), $result);

		$result = $response->headers['Content-type'];
		$expected = 'application/csv';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that rendering plain text correctly returns the render data as-is.
	 *
	 * @return void
	 */
	public function testPlainTextOutput() {
		$response = new Response();
		$response->type('text');
		Media::render($response, "Hello, world!");

		$expected = array("Hello, world!");
		$result = $response->body;
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that an exception is thrown for cases where an attempt is made to render content for
	 * a type which is not registered.
	 *
	 * @return void
	 */
	public function testUndhandledContent() {
		$response = new Response();
		$response->type('bad');

		$this->expectException("Unhandled media type 'bad'");
		Media::render($response, array('foo' => 'bar'));

		$result = $response->body;
		$this->assertNull($result);
	}

	/**
	 * Tests that attempts to render a media type with no handler registered produces an
	 * 'unhandled media type' exception, even if the type itself is a registered content type.
	 *
	 * @return void
	 */
	public function testUnregisteredContentHandler() {
		$response = new Response();
		$response->type('xml');

		$this->expectException("Unhandled media type 'xml'");
		Media::render($response, array('foo' => 'bar'));

		$result = $response->body;
		$this->assertNull($result);
	}

	/**
	 * Tests handling content type manually using parameters to `Media::render()`, for content types
	 * that are registered but have no default handler.
	 *
	 * @return void
	 */
	public function testManualContentHandling() {
		Media::type('custom', 'text/x-custom');
		$response = new Response();
		$response->type = 'custom';

		Media::render($response, 'Hello, world!', array(
			'layout' => false,
			'template' => false,
			'encode' => function($data) { return "Message: {$data}"; }
		));

		$result = $response->body;
		$expected = array("Message: Hello, world!");
		$this->assertEqual($expected, $result);

		$this->expectException("/Template not found/");
		Media::render($response, 'Hello, world!');

		$result = $response->body;
		$this->assertNull($result);
	}

	/**
	 * Tests that parameters from the `Request` object passed into `render()` via
	 * `$options['request']` are properly merged into the `$options` array passed to render
	 * handlers.
	 *
	 * @return void
	 */
	public function testRequestOptionMerging() {
		Media::type('custom', 'text/x-custom');
		$request = new Request();
		$request->params['foo'] = 'bar';

		$response = new Response();
		$response->type = 'custom';

		Media::render($response, null, compact('request') + array(
			'layout' => false,
			'template' => false,
			'encode' => function($data, $handler) { return $handler['request']->foo; }
		));
		$this->assertEqual(array('bar'), $response->body);
	}

	public function testMediaEncoding() {
		$data = array('hello', 'goodbye', 'foo' => array('bar', 'baz' => 'dib'));
		$expected = json_encode($data);
		$result = Media::encode('json', $data);
		$this->assertEqual($expected, $result);

		$this->assertEqual($result, Media::to('json', $data));
		$this->assertNull(Media::encode('badness', $data));

		$result = Media::decode('json', $expected);
		$this->assertEqual($data, $result);
	}

	public function testRenderWithOptionsMerging() {
		$base = LITHIUM_APP_PATH . '/resources/tmp';
		$this->skipIf(!is_writable($base), "{$base} is not writable.");

		$request = new Request();
		$request->params['controller'] = 'pages';

		$response = new Response();
		$response->type = 'html';

		$this->expectException('/Template not found/');
		Media::render($response, null, compact('request'));
		$this->_cleanUp();
	}

	public function testCustomWebroot() {
		Libraries::add('defaultStyleApp', array('path' => LITHIUM_APP_PATH, 'bootstrap' => false));
		$this->assertEqual(LITHIUM_APP_PATH . '/webroot', Media::webroot('defaultStyleApp'));

		Libraries::add('customWebRootApp', array(
			'path' => LITHIUM_APP_PATH,
			'webroot' => LITHIUM_APP_PATH,
			'bootstrap' => false
		));
		$this->assertEqual(LITHIUM_APP_PATH, Media::webroot('customWebRootApp'));

		Libraries::remove('defaultStyleApp');
		Libraries::remove('customWebRootApp');
		$this->assertNull(Media::webroot('defaultStyleApp'));
	}

	/**
	 * Tests that the `Media` class' configuration can be reset to its default state.
	 *
	 * @return void
	 */
	public function testStateReset() {
		$this->assertFalse(in_array('foo', Media::types()));

		Media::type('foo', 'text/x-foo');
		$this->assertTrue(in_array('foo', Media::types()));

		Media::reset();
		$this->assertFalse(in_array('foo', Media::types()));
	}

	public function testEncodeRecordSet() {
		$data = new RecordSet(array('data' => array(
			1 => new Record(array('data' => array('id' => 1, 'foo' => 'bar'))),
			2 => new Record(array('data' => array('id' => 2, 'foo' => 'baz'))),
			3 => new Record(array('data' => array('id' => 3, 'baz' => 'dib')))
		)));
		$json = '{"1":{"id":1,"foo":"bar"},"2":{"id":2,"foo":"baz"},"3":{"id":3,"baz":"dib"}}';
		$this->assertEqual($json, Media::encode(array('encode' => 'json_encode'), $data));
	}

	/**
	 * Tests that calling `Media::type()` to retrieve the details of a type that is aliased to
	 * another type, automatically resolves to the settings of the type being pointed at.
	 *
	 * @return void
	 */
	public function testTypeAliasResolution() {
		$resolved = Media::type('text');
		$this->assertEqual('text/plain', $resolved['content']);
		unset($resolved['options']['encode']);

		$result = Media::type('txt');
		unset($result['options']['encode']);
		$this->assertEqual($resolved, $result);
	}

	public function testQueryUndefinedAssetTypes() {
		$base = Media::path('index.php', 'generic');
		$result = Media::path('index.php', 'foo');
		$this->assertEqual($result, $base);

		$base = Media::asset('/bar', 'generic');
		$result = Media::asset('/bar', 'foo');
		$this->assertEqual($result, $base);
	}

	public function testGetLibraryWebroot() {
		$this->assertTrue(is_dir(Media::webroot(true)));
		$this->assertNull(Media::webroot('foobar'));

		Libraries::add('foobar', array('path' => __DIR__, 'webroot' => __DIR__));
		$this->assertEqual(__DIR__, Media::webroot('foobar'));
		Libraries::remove('foobar');
	}

	/**
	 * Tests that the `Response` object can be directly modified from a templating class or encode
	 * function.
	 *
	 * @return void
	 */
	public function testResponseModification() {
		Media::type('my', 'text/x-my', array('view' => 'lithium\tests\mocks\net\http\Template'));
		$response = new Response();

		Media::render($response, null, array('type' => 'my'));
		$this->assertEqual('Value', $response->headers('Custom'));
	}
}

?>