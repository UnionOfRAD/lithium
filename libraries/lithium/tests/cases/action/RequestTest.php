<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\action;

use \lithium\action\Request;
use \lithium\tests\mocks\action\MockIisRequest;
use \lithium\tests\mocks\action\MockCgiRequest;

class RequestTest extends \lithium\test\Unit {

	public $request = null;

	protected $_server = array();

	protected $_env = array();

	public function setUp() {
		$this->_server = $_SERVER;
		$this->_env = $_ENV;
		$this->request = new Request(array('init' => false));
	}

	public function tearDown() {
		$_SERVER = $this->_server;
		$_ENV = $this->_env;
		unset($this->Request);
	}

	public function testInitData() {
		$_POST['Article']['title'] = 'cool';
		$request = new Request();

		$expected = array('Article' => array('title' => 'cool'));
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_POST, $request);
	}

	public function testInitMethodOverride() {
		$_POST['Article']['title'] = 'cool';
		$_ENV['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'GET';
		$request = new Request();

		$expected = 'GET';
		$result = $request->env('REQUEST_METHOD');
		$this->assertEqual($expected, $result);

		$expected = array('Article' => array('title' => 'cool'));
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_POST, $request);
	}

	public function testInitMethodOverrideWithEmptyServer() {
		$request = new Request(array('env' => array('HTTP_X_HTTP_METHOD_OVERRIDE' => 'POST')));
		$request->data = array('Article' => array('title' => 'cool'));

		$expected = 'POST';
		$result = $request->env('REQUEST_METHOD');
		$this->assertEqual($expected, $result);

		$expected = array('Article' => array('title' => 'cool'));
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_POST, $request);
	}

	public function testScriptFilename() {
		$request = new Request(array('env' => array(
			'SCRIPT_FILENAME' => '/lithium/app/webroot/index.php'
		)));

		$expected = '/lithium/app/webroot/index.php';
		$result = $request->env('SCRIPT_FILENAME');
		$this->assertEqual($expected, $result);
	}

	public function testPlatform() {
		$request = new MockIisRequest();

		$expected = 'IIS';
		$result = $request->env('PLATFORM');
		$this->assertEqual($expected, $result);
	}

	public function testScriptFilenameTranslatedForIIS() {
		$request = new MockIisRequest();

		$expected = '\\lithium\\app\\webroot\\index.php';
		$result = $request->env('SCRIPT_FILENAME');
		$this->assertEqual($expected, $result);
	}

	public function testDocumentRoot() {
		$_SERVER['DOCUMENT_ROOT'] = '/home/lithium/app/webroot';
		$request = new Request();

		$expected = '/home/lithium/app/webroot';
		$result = $request->env('DOCUMENT_ROOT');
		$this->assertEqual($expected, $result);
	}

	public function testDocumentRootTranslatedForIIS() {
		$request = new MockIisRequest();

		$expected = '\\lithium\\app\\webroot';
		$result = $request->env('DOCUMENT_ROOT');
		$this->assertEqual($expected, $result);
	}

	public function testScriptName() {
		$_SERVER['SCRIPT_NAME'] = 'index.php';
		$request = new Request();

		$expected = 'index.php';
		$result = $request->env('SCRIPT_NAME');
		$this->assertEqual($expected, $result);
	}

	public function testHttps() {
		$_SERVER['HTTPS'] = true;
		$request = new Request();

		$expected = true;
		$result = $request->env('HTTPS');
		$this->assertEqual($expected, $result);
	}

	public function testHttpsFromScriptUri() {
		$_SERVER['SCRIPT_URI'] = 'https://lithium.com';
		unset($_SERVER['HTTPS']);
		$request = new Request();

		$expected = true;
		$result = $request->env('HTTPS');
		$this->assertEqual($expected, $result);
	}

	public function testRemoteAddr() {
		$_SERVER['REMOTE_ADDR'] = '123.456.789.000';
		$request = new Request();

		$expected = '123.456.789.000';
		$result = $request->env('REMOTE_ADDR');
		$this->assertEqual($expected, $result);
	}

	public function testRemoteAddrFromHttpPcRemoteAddr() {
		$request = new MockIisRequest();

		$expected = '123.456.789.000';
		$result = $request->env('REMOTE_ADDR');
		$this->assertEqual($expected, $result);
	}

	public function testBase() {
		$_SERVER['PHP_SELF'] = '/index.php';
		$request = new Request();

		$expected = null;
		$result = $request->env('base');
		$this->assertEqual($expected, $result);
	}

	public function testBaseWithDirectory() {
		$_SERVER['PHP_SELF'] = '/lithium.com/app/webroot/index.php';
		$request = new Request();

		$expected = '/lithium.com';
		$result = $request->env('base');
		$this->assertEqual($expected, $result);
	}

	public function testBaseWithAppAndOtherDirectory() {
		$_SERVER['PHP_SELF'] = '/lithium.com/app/other/webroot/index.php';
		$request = new Request();

		$expected = '/lithium.com/app/other';
		$result = $request->env('base');
		$this->assertEqual($expected, $result);
	}

	public function testPhpSelfTranslatedForIIS() {
		$request = new MockIisRequest();

		$expected = '/index.php';
		$result = $request->env('PHP_SELF');
		$this->assertEqual($expected, $result);
	}

	public function testServerHttpBase() {
		$_SERVER['HTTP_HOST'] = 'sub.lithium.local';
		$request = new Request();

		$expected = '.lithium.local';
		$result = $request->env('HTTP_BASE');
		$this->assertEqual($expected, $result);
	}

	public function testCgiPlatform() {
		$request = new MockCgiRequest();

		$expected = true;
		$result = $request->env('CGI_MODE');
		$this->assertEqual($expected, $result);
	}

	public function testCgiScriptUrl() {
		$request = new MockCgiRequest();

		$expected = '/lithium/app/webroot/index.php';
		$result = $request->env('SCRIPT_NAME');
		$this->assertEqual($expected, $result);
	}

	public function testGetMethod() {
		$_SERVER['PHP_SELF'] = '/lithium.com/app/webroot/index.php';
		$_POST['Article']['title'] = 'cool';
		$request = new Request();

		$expected = array('title' => 'cool');
		$result = $request->get('data:Article');
		$this->assertEqual($expected, $result);

		$expected = null;
		$result = $request->get('not:Post');
		$this->assertEqual($expected, $result);

		$expected = '/lithium.com';
		$result = $request->get('env:base');
		$this->assertEqual($expected, $result);

		unset($_POST, $request);
	}

	public function testDetect() {
		$_SERVER['SOME_COOL_DETECTION'] = true;
		$request = new Request();
		$request->detect('cool', 'SOME_COOL_DETECTION');

		$expected = true;
		$result = $request->is('cool');
		$this->assertEqual($expected, $result);
	}

	public function testDetectWithClosure() {
		$request = new Request();
		$request->detect('cool', function ($self) {
			return true;
		});

		$expected = true;
		$result = $request->is('cool');
		$this->assertEqual($expected, $result);
	}

	public function testDetectWithArray() {
		$request = new Request();
		$request->detect(array('cool' => function ($self) {
			return true;
		}));

		$expected = true;
		$result = $request->is('cool');
		$this->assertEqual($expected, $result);
	}

	public function testDetectWithArrayRegex() {
		$_SERVER['SOME_COOL_DETECTION'] = 'this is cool';
		$request = new Request();
		$request->detect('cool', array('SOME_COOL_DETECTION', '/cool/'));

		$expected = true;
		$result = $request->is('cool');
		$this->assertEqual($expected, $result);
	}

	public function testIsMobile() {
		$_SERVER['HTTP_USER_AGENT'] = 'iPhone';
		$request = new Request();

		$expected = true;
		$result = $request->is('mobile');
		$this->assertEqual($expected, $result);
	}

	public function testType() {
		$request = new Request();

		$expected = 'html';
		$result = $request->type();
		$this->assertEqual($expected, $result);
	}

	public function testRefererDefault() {
		$_SERVER['HTTP_REFERER'] = null;
		$request = new Request();

		$expected = '/';
		$result = $request->referer('/');
		$this->assertEqual($expected, $result);
	}

	public function testRefererNotLocal() {
		$_SERVER['HTTP_REFERER'] = 'http://lithium.com/posts/index';
		$request = new Request();

		$expected = 'http://lithium.com/posts/index';
		$result = $request->referer('/');
		$this->assertEqual($expected, $result);
	}

	public function testRefererLocal() {
		$_SERVER['HTTP_REFERER'] = '/posts/index';
		$request = new Request();

		$expected = '/posts/index';
		$result = $request->referer('/', true);
		$this->assertEqual($expected, $result);
	}

	public function testRefererLocalFromNotLocal() {
		$_SERVER['HTTP_REFERER'] = 'http://lithium.com/posts/index';
		$request = new Request();

		$expected = '/';
		$result = $request->referer('/', true);
		$this->assertEqual($expected, $result);
	}

	public function testMagicParamsAccess() {
		$this->assertNull($this->request->action);
		$this->assertFalse(isset($this->request->params['action']));

		$expected = $this->request->params['action'] = 'index';
		$result = $this->request->action;
		$this->assertEqual($expected, $result);
	}

	public function testSingleFileNormalization() {
		$_FILES = array(
			'file' => array(
				'name' => 'file.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpows38J',
				'error' => 0,
				'size' => 418,
		  	)
		);
		$request = new Request();

		$expected = array('file' => array(
			'name' => 'file.jpg',
			'type' => 'image/jpeg',
			'tmp_name' => '/private/var/tmp/phpows38J',
			'error' => 0,
			'size' => 418,
		));
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testDeepFileNormalization() {
		$_FILES = array(
			'files' =>  array(
				'name' => array(
			  		0 => 'file 2.jpg',
			  		1 => 'file 3.jpg',
			  		2 => 'file 4.jpg',
				),
				'type' => array(
			  		0 => 'image/jpeg',
			  		1 => 'image/jpeg',
			  		2 => 'image/jpeg',
				),
				'tmp_name' => array(
			  		0 => '/private/var/tmp/phpF5vsky',
			  		1 => '/private/var/tmp/phphRJ2zW',
			  		2 => '/private/var/tmp/phprI92L1',
				),
				'error' => array(
			  		0 => 0,
			  		1 => 0,
			  		2 => 0,
				),
				'size' => array(
			  		0 => 418,
			  		1 => 418,
			  		2 => 418,
				)
			)
		);
		$request = new Request();

		$expected = array('files' => array(
			0 => array(
				'name' => 'file 2.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpF5vsky',
				'error' => 0,
				'size' => 418,
			),
			1 => array(
				'name' => 'file 3.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phphRJ2zW',
				'error' => 0,
				'size' => 418,
			),
			2 => array(
				'name' => 'file 4.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phprI92L1',
				'error' => 0,
				'size' => 418,
			),
		));
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testNestedFilesNormalization() {
		$_FILES = array('Image' => array(
			'name' => array(
		  		'file' => 'file 5.jpg',
			),
			'type' => array(
		  		'file' => 'image/jpeg',
			),
			'tmp_name' => array(
		  		'file' => '/private/var/tmp/phpAmSDL4',
			),
			'error' => array(
		  		'file' => 0,
			),
			'size' => array(
		  		'file' => 418,
			),
	  	));
		$request = new Request();

		$expected = array('Image' => array(
			'file' => array(
				'name' => 'file 5.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpAmSDL4',
				'error' => 0,
				'size' => 418,
		  	)
		));

		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testNestedDeepFilesNormalization() {
		$_FILES = array('Photo' => array(
			'name' => array(
		  		'files' => array(
					0 => 'file 6.jpg',
					1 => 'file 7.jpg',
					2 => 'file 8.jpg',
		  		),
			),
			'type' => array(
		  		'files' => array(
					0 => 'image/jpeg',
					1 => 'image/jpeg',
					2 => 'image/jpeg',
		  		),
			),
			'tmp_name' => array(
		  		'files' => array(
					0 => '/private/var/tmp/php2eViak',
					1 => '/private/var/tmp/phpMsC5Pp',
					2 => '/private/var/tmp/phpm2nm98',
		  		),
			),
			'error' => array(
				'files' => array(
					0 => 0,
					1 => 0,
					2 => 0,
		  		),
			),
			'size' => array(
		  		'files' => array(
					0 => 418,
					1 => 418,
					2 => 418,
		  		)
			)
		));
		$request = new Request();

		$expected = array('Photo' => array(
			'files' => array(
				0 => array(
					'name' => 'file 6.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/php2eViak',
					'error' => 0,
					'size' => 418,
				),
				1 => array(
					'name' => 'file 7.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpMsC5Pp',
					'error' => 0,
					'size' => 418,
				),
				2 => array(
					'name' => 'file 8.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpm2nm98',
					'error' => 0,
					'size' => 418,
				),
		  	)
		));
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testMixedFilesNormalization() {
		$_FILES	 = array(
			'file' => array(
				'name' => 'file.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpows38J',
				'error' => 0,
				'size' => 418,
		  	),
		  	'files' =>  array(
				'name' => array(
			  		0 => 'file 2.jpg',
			  		1 => 'file 3.jpg',
			  		2 => 'file 4.jpg',
				),
				'type' => array(
			  		0 => 'image/jpeg',
			  		1 => 'image/jpeg',
			  		2 => 'image/jpeg',
				),
				'tmp_name' => array(
			  		0 => '/private/var/tmp/phpF5vsky',
			  		1 => '/private/var/tmp/phphRJ2zW',
			  		2 => '/private/var/tmp/phprI92L1',
				),
				'error' => array(
			  		0 => 0,
			  		1 => 0,
			  		2 => 0,
				),
				'size' => array(
			  		0 => 418,
			  		1 => 418,
			  		2 => 418,
				),
		  	),
		  	'Image' => array(
				'name' => array(
			  		'file' => 'file 5.jpg',
				),
				'type' => array(
			  		'file' => 'image/jpeg',
				),
				'tmp_name' => array(
			  		'file' => '/private/var/tmp/phpAmSDL4',
				),
				'error' => array(
			  		'file' => 0,
				),
				'size' => array(
			  		'file' => 418,
				),
		  	),
		  	'Photo' => array(
				'name' => array(
			  		'files' => array(
						0 => 'file 6.jpg',
						1 => 'file 7.jpg',
						2 => 'file 8.jpg',
			  		),
				),
				'type' => array(
			  		'files' => array(
						0 => 'image/jpeg',
						1 => 'image/jpeg',
						2 => 'image/jpeg',
			  		),
				),
				'tmp_name' => array(
			  		'files' => array(
						0 => '/private/var/tmp/php2eViak',
						1 => '/private/var/tmp/phpMsC5Pp',
						2 => '/private/var/tmp/phpm2nm98',
			  		),
				),
				'error' => array(
					'files' => array(
						0 => 0,
						1 => 0,
						2 => 0,
			  		),
				),
				'size' => array(
			  		'files' => array(
						0 => 418,
						1 => 418,
						2 => 418,
			  		),
				),
		  	),
		);
		$expected = array(
			'file' => array(
				'name' => 'file.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpows38J',
				'error' => 0,
				'size' => 418,
		  	),
			'files' => array(
				0 => array(
					'name' => 'file 2.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpF5vsky',
					'error' => 0,
					'size' => 418,
				),
				1 => array(
					'name' => 'file 3.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phphRJ2zW',
					'error' => 0,
					'size' => 418,
				),
				2 => array(
					'name' => 'file 4.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phprI92L1',
					'error' => 0,
					'size' => 418,
				),
			),
			'Image' => array(
				'file' => array(
					'name' => 'file 5.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpAmSDL4',
					'error' => 0,
					'size' => 418,
			  	)
			),
			'Photo' => array(
				'files' => array(
					0 => array(
						'name' => 'file 6.jpg',
						'type' => 'image/jpeg',
						'tmp_name' => '/private/var/tmp/php2eViak',
						'error' => 0,
						'size' => 418,
					),
					1 => array(
						'name' => 'file 7.jpg',
						'type' => 'image/jpeg',
						'tmp_name' => '/private/var/tmp/phpMsC5Pp',
						'error' => 0,
						'size' => 418,
					),
					2 => array(
						'name' => 'file 8.jpg',
						'type' => 'image/jpeg',
						'tmp_name' => '/private/var/tmp/phpm2nm98',
						'error' => 0,
						'size' => 418,
					),
			  	)
			)
		);

		$request = new Request();
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testRequestTypeAccessors() {
		$request = new Request(array('env' => array('REQUEST_METHOD' => 'GET')));
		$this->assertTrue($request->is('get'));
		$this->assertFalse($request->is('post'));

		$request = new Request(array('env' => array('REQUEST_METHOD' => 'POST')));
		$this->assertTrue($request->is('post'));
		$this->assertFalse($request->is('get'));
		$this->assertFalse($request->is('put'));

		$request = new Request(array('env' => array('REQUEST_METHOD' => 'PUT')));
		$this->assertTrue($request->is('put'));
		$this->assertFalse($request->is('get'));
		$this->assertFalse($request->is('post'));
	}

	public function testRequestTypeIsMobile() {
		$request = new Request(array('env' => array(
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en)'
		)));
		$this->assertTrue($request->is('mobile'));
	}

	public function testUrlFromGet() {
		$_GET['url'] = 'posts/1';
		$request = new Request();

		$expected = 'posts/1';
		$result = $request->url;
		$this->assertEqual($expected, $result);

		unset($_GET);
	}

	public function testUrlFromConstructor() {
		$request = new Request(array('url' => 'posts/1'));

		$expected = 'posts/1';
		$result = $request->url;
		$this->assertEqual($expected, $result);
	}

	public function testDataFromConstructor() {
		$request = new Request(array('data' => array('name' => 'bob')));

		$expected = array('name' => 'bob');
		$result = $request->data;
		$this->assertEqual($expected, $result);
	}

	public function testQueryFromConstructor() {
		$request = new Request(array('query' => array('page' => 1)));

		$expected = array('page' => 1);
		$result = $request->query;
		$this->assertEqual($expected, $result);
	}

	public function testMethodOverrideFromData() {
		$_POST['_method'] = 'put';
		$request = new Request();

		$result = $request->is('put');
		$this->assertTrue($result);

		unset($_POST);

		$request = new Request(array('data' => array('_method' => 'put')));

		$result = $request->is('put');
		$this->assertTrue($result);
	}

	public function testMergeMobileDetectors() {
		$request = new Request(array(
			'env' => array('HTTP_USER_AGENT' => 'testMobile'),
			'detectors' => array('mobile' => array('HTTP_USER_AGENT', array('testMobile')))
		));

		$result = $request->is('mobile');
		$this->assertTrue($result);

		$request = new Request(array(
			'env' => array('HTTP_USER_AGENT' => 'iPhone'),
			'detectors' => array('mobile' => array('HTTP_USER_AGENT', array('testMobile')))
		));

		$result = $request->is('mobile');
		$this->assertTrue($result);
	}

	public function testRequestTypeFromConstruct() {
		$request = new Request(array('type' => 'json'));

		$expected = 'json';
		$result = $request->type();
		$this->assertEqual($expected, $result);
	}

	public function testRequestTypeFromParams() {
		$request = new Request();
		$request->params['type'] = 'json';

		$expected = 'json';
		$result = $request->type();
		$this->assertEqual($expected, $result);
	}

	public function testRequestTypeFromHeader() {
		$request = new Request(array('env' => array('Content-type' => 'json')));

		$expected = 'json';
		$result = $request->type();
		$this->assertEqual($expected, $result);
	}
}

?>