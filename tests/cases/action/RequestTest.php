<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\action;

use lithium\core\Libraries;
use lithium\action\Request;

class RequestTest extends \lithium\test\Unit {

	protected $_superglobals = ['_GET', '_POST', '_SERVER', '_ENV'];

	protected $_env = [];

	protected $_iisConfig = [
		'env' => [
			'PLATFORM' => 'IIS',
			'SCRIPT_NAME' => '\index.php',
			'SCRIPT_FILENAME' => false,
			'DOCUMENT_ROOT' => false,
			'PATH_TRANSLATED' => '\lithium\app\webroot\index.php',
			'HTTP_PC_REMOTE_ADDR' => '123.456.789.000'
		],
		'globals' => false
	];

	protected $_cgiConfig = [
		'env' => [
			'PLATFORM' => 'CGI',
			'SCRIPT_FILENAME' => false,
			'DOCUMENT_ROOT' => false,
			'SCRIPT_URL' => '/lithium/app/webroot/index.php'
		],
		'globals' => false
	];

	protected $_nginxConfig = [
		'env' => [
			'FCGI_ROLE' => 'RESPONDER',
			'PATH_INFO' => '',
			'PATH_TRANSLATED' => '/lithium/app/webroot/index.php',
			'QUERY_STRING' => '',
			'REQUEST_METHOD' => 'GET',
			'CONTENT_TYPE' => '',
			'CONTENT_LENGTH' => '',
			'SCRIPT_NAME' => '/index.php',
			'SCRIPT_FILENAME' => '/lithium/app/webroot/index.php',
			'REQUEST_URI' => '/',
			'DOCUMENT_URI' => '/index.php',
			'DOCUMENT_ROOT' => '/lithium/app/webroot',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'REMOTE_ADDR' => '127.0.0.1',
			'REMOTE_PORT' => '52987',
			'SERVER_ADDR' => '127.0.0.1',
			'SERVER_PORT' => '80',
			'SERVER_NAME' => 'sandbox.local',
			'HTTP_HOST' => 'sandbox.local',
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7) AppleWebKit/...etc.',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us',
			'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
			'HTTP_CONNECTION' => 'keep-alive',
			'PHP_SELF' => '/index.php'
		],
		'globals' => false
	];

	public function setUp() {
		foreach ($this->_superglobals as $varname) {
			$this->_env[$varname] = $GLOBALS[$varname];
			unset($GLOBALS[$varname]);
		}
	}

	public function tearDown() {
		foreach ($this->_superglobals as $varname) {
			$GLOBALS[$varname] = $this->_env[$varname];
		}
	}

	public function testInitData() {
		$request = new Request([
			'data' => [
				'Article' => [
					'title' => 'cool'
				]
			]
		]);

		$expected = ['Article' => ['title' => 'cool']];
		$result = $request->data;
		$this->assertEqual($expected, $result);
	}

	public function testInitMethodOverride() {
		$request = new Request([
			'env' => ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET'],
			'data' => [
				'Article' => [
					'title' => 'cool'
				]
			]
		]);

		$this->assertEqual('GET', $request->env('REQUEST_METHOD'));
		$this->assertEqual(['Article' => ['title' => 'cool']], $request->data);
	}

	public function testInitMethodOverrideWithEmptyServer() {
		$request = new Request([
			'env' => ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'POST'],
			'data' => [
				'Article' => [
					'title' => 'cool'
				]
			]
		]);
		$this->assertEqual('POST', $request->env('REQUEST_METHOD'));
		$this->assertEqual(['Article' => ['title' => 'cool']], $request->data);
	}

	public function testScriptFilename() {
		$request = new Request(['env' => [
			'SCRIPT_FILENAME' => '/lithium/app/webroot/index.php'
		]]);
		$result = $request->env('SCRIPT_FILENAME');
		$this->assertEqual('/lithium/app/webroot/index.php', $result);
	}

	public function testPlatform() {
		$request = new Request($this->_iisConfig);
		$result = $request->env('PLATFORM');
		$this->assertEqual('IIS', $result);
	}

	public function testScriptFilenameTranslatedForIIS() {
		$request = new Request($this->_iisConfig);
		$this->assertEqual('\lithium\app\webroot\index.php', $request->env('SCRIPT_FILENAME'));

		$request = new Request([
			'env' => [
				'DOCUMENT_ROOT' => 'C:\htdocs',
				'PHP_SELF' => '\lithium\index.php',
				'SCRIPT_FILENAME' => null
			]
		]);
		$path = $request->env('DOCUMENT_ROOT') . $request->env('PHP_SELF');
		$this->assertEqual($path, $request->env('SCRIPT_FILENAME'));
	}

	public function testDocumentRoot() {
		$request = new Request([
			'env' => ['DOCUMENT_ROOT' => '/home/lithium/app/webroot']
		]);
		$this->assertEqual('/home/lithium/app/webroot', $request->env('DOCUMENT_ROOT'));
	}

	public function testDocumentRootTranslatedForIIS() {
		$request = new Request($this->_iisConfig);
		$result = $request->env('DOCUMENT_ROOT');
		$this->assertEqual('\lithium\app\webroot', $result);
	}

	public function testScriptName() {
		$request = new Request([
			'env' => ['HTTPS' => true, 'SCRIPT_NAME' => 'index.php']
		]);
		$this->assertEqual('index.php', $request->env('SCRIPT_NAME'));
	}

	public function testHttps() {
		$request = new Request(['env' => ['HTTPS' => true]]);
		$this->assertTrue($request->env('HTTPS'));
	}

	public function testHttpsFromScriptUri() {
		$request = new Request(['env' => [
			'SCRIPT_URI' => 'https://lithium.com',
			'HTTPS' => null
		]]);
		$this->assertTrue($request->env('HTTPS'));
	}

	public function testRemoteAddr() {
		$request = new Request(['env' => ['REMOTE_ADDR' => '123.456.789.000']]);
		$this->assertEqual('123.456.789.000', $request->env('REMOTE_ADDR'));

		$request = new Request(['env' => [
			'REMOTE_ADDR' => '123.456.789.000',
			'HTTP_X_FORWARDED_FOR' => '111.222.333.444'
		]]);
		$this->assertEqual('111.222.333.444', $request->env('REMOTE_ADDR'));

		$request = new Request(['env' => [
			'REMOTE_ADDR' => '123.456.789.000',
			'HTTP_X_FORWARDED_FOR' => '333.222.444.111, 444.333.222.111, 255.255.255.255'
		]]);
		$this->assertEqual('333.222.444.111', $request->env('REMOTE_ADDR'));

		$request = new Request(['env' => [
			'REMOTE_ADDR' => '123.456.789.000',
			'HTTP_PC_REMOTE_ADDR' => '222.333.444.555'
		]]);
		$this->assertEqual('222.333.444.555', $request->env('REMOTE_ADDR'));

		$request = new Request(['env' => [
			'REMOTE_ADDR' => '123.456.789.000',
			'HTTP_X_REAL_IP' => '111.222.333.444'
		]]);
		$this->assertEqual('111.222.333.444', $request->env('REMOTE_ADDR'));

		$request = new Request(['env' => [
			'REMOTE_ADDR' => '123.456.789.000',
			'HTTP_X_FORWARDED_FOR' => '111.222.333.444',
			'HTTP_PC_REMOTE_ADDR' => '222.333.444.555'
		]]);
		$this->assertEqual('111.222.333.444', $request->env('REMOTE_ADDR'));
	}

	public function testRemoteAddrFromHttpPcRemoteAddr() {
		$request = new Request($this->_iisConfig);
		$this->assertEqual('123.456.789.000', $request->env('REMOTE_ADDR'));
	}

	public function testPhpAuthBasic() {
		$request = new Request(['env' => [
			'PHP_AUTH_USER' => 'test-user',
			'PHP_AUTH_PW' => 'test-password'
		]]);
		$this->assertEqual('test-user', $request->env('PHP_AUTH_USER'));
		$this->assertEqual('test-password', $request->env('PHP_AUTH_PW'));

		$request = new Request(['env' => [
			'PHP_AUTH_USER' => 'test-user',
			'PHP_AUTH_PW' => ''
		]]);
		$this->assertEqual('test-user', $request->env('PHP_AUTH_USER'));
		$this->assertNull($request->env('PHP_AUTH_PW'));

		$request = new Request(['env' => [
			'PHP_AUTH_USER' => '',
			'PHP_AUTH_PW' => 'test-password'
		]]);
		$this->assertNull($request->env('PHP_AUTH_USER'));
		$this->assertEqual('test-password', $request->env('PHP_AUTH_PW'));
	}

	public function testCgiPhpAuthBasic() {
		$request = new Request(['env' => [
			'HTTP_AUTHORIZATION' => 'Basic dGVzdC11c2VyOnRlc3QtcGFzc3dvcmQ=',
		]]);
		$this->assertEqual('test-user', $request->env('PHP_AUTH_USER'));
		$this->assertEqual('test-password', $request->env('PHP_AUTH_PW'));

		$request = new Request(['env' => [
			'REDIRECT_HTTP_AUTHORIZATION' => 'Basic dGVzdC11c2VyOnRlc3QtcGFzc3dvcmQ=',
		]]);
		$this->assertEqual('test-user', $request->env('PHP_AUTH_USER'));
		$this->assertEqual('test-password', $request->env('PHP_AUTH_PW'));
	}

	public function testCgiPhpAuthBasicFailMissingPassword() {
		$request = new Request(['env' => [
			'HTTP_AUTHORIZATION' => 'Basic dGVzdC11c2VyOg==',
		]]);
		$this->assertEqual('test-user', $request->env('PHP_AUTH_USER'));
		$this->assertIdentical('', $request->env('PHP_AUTH_PW'));

		$request = new Request(['env' => [
			'HTTP_AUTHORIZATION' => 'Basic nRlc3QtcGFzc3dvcmQ=',
		]]);
		$this->assertNull($request->env('PHP_AUTH_USER'));
		$this->assertNull($request->env('PHP_AUTH_PW'));
	}

	public function testCgiPhpAuthBasicFailMissingPasswordAndColon() {
		$request = new Request(['env' => [
			'HTTP_AUTHORIZATION' => 'Basic dGVzdC11c2Vy',
		]]);
		$this->assertNull($request->env('PHP_AUTH_USER'));
		$this->assertNull($request->env('PHP_AUTH_PW'));
	}

	public function testCgiPhpAuthBasicFailMissingUser() {
		$request = new Request(['env' => [
			'HTTP_AUTHORIZATION' => 'Basic nRlc3QtcGFzc3dvcmQ=',
		]]);
		$this->assertNull($request->env('PHP_AUTH_USER'));
		$this->assertNull($request->env('PHP_AUTH_PW'));
	}

	public function testPhpAuthDigest() {
		$request = new Request(['env' => [
			'PHP_AUTH_DIGEST' => 'test-digest'
		]]);
		$this->assertEqual('test-digest', $request->env('PHP_AUTH_DIGEST'));
	}

	public function testCgiPhpAuthDigest() {
		$request = new Request(['env' => [
			'HTTP_AUTHORIZATION' => 'Digest test-digest'
		]]);
		$this->assertEqual('test-digest', $request->env('PHP_AUTH_DIGEST'));

		$request = new Request(['env' => [
			'REDIRECT_HTTP_AUTHORIZATION' => 'Digest test-digest'
		]]);
		$this->assertEqual('test-digest', $request->env('PHP_AUTH_DIGEST'));
	}

	public function testBase() {
		$request = new Request(['env' => ['PHP_SELF' => '/index.php']]);
		$this->assertEmpty($request->env('base'));
	}

	public function testBaseWithDirectory() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'PHP_SELF' => '/lithium/app/webroot/index.php',
			'REQUEST_URI' => '/lithium/hello/world'
		]]);
		$this->assertEqual('/lithium', $request->env('base'));
	}

	public function testRequestWithColon() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'PHP_SELF' => '/lithium/app/webroot/index.php',
			'REQUEST_URI' => '/lithium/pages/lithium/test:a'
		]]);
		$this->assertEqual('/lithium', $request->env('base'));
		$this->assertEqual('/pages/lithium/test:a', $request->url);

		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'PHP_SELF' => '/lithium/app/webroot/index.php',
			'REQUEST_URI' => '/lithium/pages/lithium/test:1'
		]]);
		$this->assertEqual('/lithium', $request->env('base'));
		$this->assertEqual('/pages/lithium/test:1', $request->url);
	}

	public function testRequestWithoutUrlQueryParamAndNoApp() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'PHP_SELF' => '/lithium/webroot/index.php',
			'REQUEST_URI' => '/lithium/'
		]]);
		$this->assertEqual('/lithium', $request->env('base'));
		$this->assertEqual('/', $request->url);
	}

	public function testRequestWithoutUrlQueryParamAndNoAppOrWebroot() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'PHP_SELF' => '/lithium/index.php',
			'REQUEST_URI' => '/lithium/'
		]]);
		$this->assertEqual('/lithium', $request->env('base'));
		$this->assertEqual('/', $request->url);
	}

	public function testBaseWithAppAndOtherDirectory() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'PHP_SELF' => '/lithium/app/other/webroot/index.php'
		]]);
		$this->assertEqual('/lithium/app/other', $request->env('base'));
	}

	public function testServerHttpBase() {
		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'sub.lithium.local'
			]
		]);

		$expected = '.lithium.local';
		$result = $request->env('HTTP_BASE');
		$this->assertEqual($expected, $result);
	}

	public function testCgiPlatform() {
		$request = new Request($this->_cgiConfig);
		$this->assertTrue($request->env('CGI_MODE'));
	}

	public function testCgiScriptUrl() {
		$request = new Request($this->_cgiConfig);
		$expected = '/lithium/app/webroot/index.php';
		$result = $request->env('SCRIPT_NAME');
		$this->assertEqual($expected, $result);
	}

	public function testGetMethod() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'PHP_SELF' => '/lithium/app/webroot/index.php',
			'HTTP_ACCEPT' => 'text/html,application/xml,image/png,*/*',
			'HTTP_ACCEPT_LANGUAGE' => 'da, en-gb;q=0.8, en;q=0.7'
		]]);
		$request->data = ['Article' => ['title' => 'cool']];

		$expected = ['title' => 'cool'];
		$result = $request->get('data:Article');
		$this->assertEqual($expected, $result);

		$result = $request->get('not:Post');
		$this->assertNull($result);

		$expected = '/lithium';
		$result = $request->get('env:base');
		$this->assertEqual($expected, $result);

		$accept = $request->get('http:accept');
		$this->assertEqual('text/html,application/xml,image/png,*/*', $accept);
		$this->assertEqual($request->get('http:method'), $request->env('REQUEST_METHOD'));
	}

	public function testDetect() {
		$request = new Request(['env' => ['SOME_COOL_DETECTION' => true]]);
		$request->detect('cool', 'SOME_COOL_DETECTION');

		$this->assertTrue($request->is('cool'));
		$this->assertFalse($request->is('foo'));

		$request = new Request(['env' => [
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; U; XXXXX like Mac OS X; en) AppleWebKit/420+'
		]]);

		$request->detect('iPhone', ['HTTP_USER_AGENT', '/iPhone/']);
		$isiPhone = $request->is('iPhone'); // returns true if 'iPhone' appears anywhere in the UA
		$this->assertTrue($isiPhone);
	}

	public function testDetectWithClosure() {
		$request = new Request();
		$request->detect('cool', function ($self) { return true; });
		$request->detect('notCool', function ($self) { return false; });

		$this->assertTrue($request->is('cool'));
		$this->assertFalse($request->is('notCool'));
	}

	public function testDetectWithArray() {
		$request = new Request();
		$request->detect(['cool' => function ($self) {
			return true;
		}]);

		$result = $request->is('cool');
		$this->assertTrue($result);
	}

	public function testDetectWithArrayRegex() {
		$request = new Request(['env' => ['SOME_COOL_DETECTION' => 'this is cool']]);
		$request->detect('cool', ['SOME_COOL_DETECTION', '/cool/']);

		$result = $request->is('cool');
		$this->assertTrue($result);
	}

	public function testDetectSsl() {
		$request = new Request(['env' => ['SCRIPT_URI' => null, 'HTTPS' => 'off']]);
		$this->assertFalse($request->env('HTTPS'));

		$request = new Request(['env' => ['SCRIPT_URI' => null, 'HTTPS' => 'on']]);
		$this->assertTrue($request->env('HTTPS'));

		$request = new Request(['env' => ['SCRIPT_URI' => null, 'HTTPS' => null]]);
		$this->assertFalse($request->env('HTTPS'));
	}

	public function testContentTypeDetection() {
		$request = new Request(['env' => [
			'CONTENT_TYPE' => 'application/json; charset=UTF-8',
			'REQUEST_METHOD' => 'POST'
		]]);
		$this->assertTrue($request->is('json'));
		$this->assertFalse($request->is('html'));
		$this->assertFalse($request->is('foo'));
	}

	public function testIsMobile() {
		$iPhone = 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like ';
		$iPhone .= 'Gecko) Version/3.0 Mobile/1A535b Safari/419.3';

		$request = new Request(['env' => ['HTTP_USER_AGENT' => $iPhone]]);
		$this->assertTrue($request->is('mobile'));

		$android = 'Mozilla/5.0 (Linux; U; Android 0.5; en-us) AppleWebKit/522+ (KHTML, like ';
		$android .= 'Gecko) Safari/419.3';

		$request = new Request(['env' => ['HTTP_USER_AGENT' => $android]]);
		$this->assertTrue($request->is('mobile'));
	}

	public function testIsDNT() {
		$request = new Request(['env' => ['HTTP_DNT' => '1']]);
		$this->assertTrue($request->is('dnt'));

		$request = new Request(['env' => ['HTTP_DNT' => '0']]);
		$this->assertFalse($request->is('dnt'));

		$request = new Request(['env' => ['HTTP_DNT' => 'invalid']]);
		$this->assertFalse($request->is('dnt'));

		$request = new Request(['env' => ['HTTP_DNT' => 'true']]);
		$this->assertFalse($request->is('dnt'));

		$request = new Request(['env' => []]);
		$this->assertFalse($request->is('dnt'));
	}

	public function testType() {
		$request = new Request();
		$this->assertEqual('html', $request->type());

		$request = new Request(['env' => [
			'CONTENT_TYPE' => 'application/json; charset=UTF-8',
			'REQUEST_METHOD' => 'POST'
		]]);
		$this->assertEqual('application/json; charset=UTF-8', $request->env('CONTENT_TYPE'));
		$this->assertEqual('json', $request->type());
	}

	public function testTypeforNginx() {
		$request = new Request($this->_nginxConfig);
		$this->assertEqual('html', $request->type());
	}

	public function testHeaders() {
		$request = new Request(['env' => [
			'CONTENT_TYPE' => 'application/json; charset=UTF-8',
			'HTTP_COOKIE' => 'name=value; name2=value2',
			'HTTP_CUSTOM_HEADER' => 'foobar'
		]]);
		$this->assertEqual('application/json; charset=UTF-8', $request->headers('Content-Type'));
		$this->assertEqual('name=value; name2=value2', $request->headers('Cookie'));
		$this->assertEqual('foobar', $request->headers('Custom-Header'));
	}

	public function testRefererDefault() {
		$request = new Request([
			'env' => ['HTTP_REFERER' => null]
		]);
		$this->assertEqual('/', $request->referer('/'));
	}

	public function testRefererNotLocal() {
		$request = new Request(['env' => [
			'HTTP_REFERER' => 'http://lithium.com/posts/index',
			'HTTP_HOST' => 'foo.com'
		]]);

		$result = $request->referer('/');
		$this->assertEqual('http://lithium.com/posts/index', $result);
	}

	public function testRefererLocal() {
		$request = new Request([
			'env' => ['HTTP_REFERER' => '/posts/index']
		]);

		$expected = '/posts/index';
		$result = $request->referer('/', true);
		$this->assertEqual($expected, $result);
	}

	public function testRefererLocalWithHost() {
		$request = new Request(['env' => [
			'HTTP_REFERER' => 'http://lithium.com/posts/index',
			'HTTP_HOST' => 'lithium.com'
		]]);

		$result = $request->referer('/', true);
		$this->assertEqual('/posts/index', $result);
	}

	public function testRefererLocalFromNotLocal() {
		$request = new Request([
			'env' => [
				'HTTP_REFERER' => 'http://lithium.com/posts/index',
				'HTTP_HOST' => 'foo.com'
			]
		]);

		$expected = '/';
		$result = $request->referer('/', true);
		$this->assertEqual($expected, $result);
	}

	public function testMagicParamsAccess() {
		$request = new Request(['globals' => false]);
		$this->assertNull($request->action);
		$this->assertArrayNotHasKey('action', $request->params);
		$this->assertFalse(isset($request->action));

		$expected = $request->params['action'] = 'index';
		$this->assertEqual($expected, $request->action);
		$this->assertTrue(isset($request->action));
	}

	public function testDataStreamWithDrain() {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, '{ "foo": "bar" }');
		rewind($stream);

		$request = new Request(compact('stream') + [
			'env' => [
				'CONTENT_TYPE' => 'application/json; charset=UTF-8',
				'REQUEST_METHOD' => 'POST'
			]
		]);
		$expected = ['foo' => 'bar'];
		$this->assertEqual($expected, $request->data);
	}

	public function testDataStreamNoDrain() {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, '{ "foo": "bar" }');
		rewind($stream);

		$request = new Request(compact('stream') + [
			'drain' => false,
			'env' => [
				'CONTENT_TYPE' => 'application/json; charset=UTF-8',
				'REQUEST_METHOD' => 'POST'
			]
		]);
		$this->assertEmpty($request->body);
		$this->assertEmpty($request->data);

		fclose($stream);
	}

	public function testSingleFileNormalization() {
		$_FILES = [
			'file' => [
				'name' => 'file.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpows38J',
				'error' => 0,
				'size' => 418
			]
		];
		$request = new Request();

		$expected = ['file' => [
			'name' => 'file.jpg',
			'type' => 'image/jpeg',
			'tmp_name' => '/private/var/tmp/phpows38J',
			'error' => 0,
			'size' => 418
		]];
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testDeepFileNormalization() {
		$_FILES = [
			'files' => [
				'name' => [
					0 => 'file 2.jpg',
					1 => 'file 3.jpg',
					2 => 'file 4.jpg'
				],
				'type' => [
					0 => 'image/jpeg',
					1 => 'image/jpeg',
					2 => 'image/jpeg'
				],
				'tmp_name' => [
					0 => '/private/var/tmp/phpF5vsky',
					1 => '/private/var/tmp/phphRJ2zW',
					2 => '/private/var/tmp/phprI92L1'
				],
				'error' => [
					0 => 0,
					1 => 0,
					2 => 0
				],
				'size' => [
					0 => 418,
					1 => 418,
					2 => 418
				]
			]
		];
		$request = new Request();

		$expected = ['files' => [
			0 => [
				'name' => 'file 2.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpF5vsky',
				'error' => 0,
				'size' => 418
			],
			1 => [
				'name' => 'file 3.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phphRJ2zW',
				'error' => 0,
				'size' => 418
			],
			2 => [
				'name' => 'file 4.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phprI92L1',
				'error' => 0,
				'size' => 418
			]
		]];
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testNestedFilesNormalization() {
		$_FILES = ['Image' => [
			'name' => [
				'file' => 'file 5.jpg'
			],
			'type' => [
				'file' => 'image/jpeg'
			],
			'tmp_name' => [
				'file' => '/private/var/tmp/phpAmSDL4'
			],
			'error' => [
				'file' => 0
			],
			'size' => [
				'file' => 418
			]
		]];
		$request = new Request();

		$expected = ['Image' => [
			'file' => [
				'name' => 'file 5.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpAmSDL4',
				'error' => 0,
				'size' => 418
			]
		]];

		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testNestedDeepFilesNormalization() {
		$_FILES = ['Photo' => [
			'name' => [
				'files' => [
					0 => 'file 6.jpg',
					1 => 'file 7.jpg',
					2 => 'file 8.jpg'
				]
			],
			'type' => [
				'files' => [
					0 => 'image/jpeg',
					1 => 'image/jpeg',
					2 => 'image/jpeg'
				]
			],
			'tmp_name' => [
				'files' => [
					0 => '/private/var/tmp/php2eViak',
					1 => '/private/var/tmp/phpMsC5Pp',
					2 => '/private/var/tmp/phpm2nm98'
				]
			],
			'error' => [
				'files' => [
					0 => 0,
					1 => 0,
					2 => 0
				]
			],
			'size' => [
				'files' => [
					0 => 418,
					1 => 418,
					2 => 418
				]
			]
		]];
		$request = new Request();

		$expected = ['Photo' => [
			'files' => [
				0 => [
					'name' => 'file 6.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/php2eViak',
					'error' => 0,
					'size' => 418
				],
				1 => [
					'name' => 'file 7.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpMsC5Pp',
					'error' => 0,
					'size' => 418
				],
				2 => [
					'name' => 'file 8.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpm2nm98',
					'error' => 0,
					'size' => 418
				]
			]
		]];
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testMixedFilesNormalization() {
		$_FILES = [
			'file' => [
				'name' => 'file.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpows38J',
				'error' => 0,
				'size' => 418
			],
			'files' => [
				'name' => [
					0 => 'file 2.jpg',
					1 => 'file 3.jpg',
					2 => 'file 4.jpg'
				],
				'type' => [
					0 => 'image/jpeg',
					1 => 'image/jpeg',
					2 => 'image/jpeg'
				],
				'tmp_name' => [
					0 => '/private/var/tmp/phpF5vsky',
					1 => '/private/var/tmp/phphRJ2zW',
					2 => '/private/var/tmp/phprI92L1'
				],
				'error' => [
					0 => 0,
					1 => 0,
					2 => 0
				],
				'size' => [
					0 => 418,
					1 => 418,
					2 => 418
				]
			],
			'Image' => [
				'name' => [
					'file' => 'file 5.jpg'
				],
				'type' => [
					'file' => 'image/jpeg'
				],
				'tmp_name' => [
					'file' => '/private/var/tmp/phpAmSDL4'
				],
				'error' => [
					'file' => 0
				],
				'size' => [
					'file' => 418
				]
			],
			'Photo' => [
				'name' => [
					'files' => [
						0 => 'file 6.jpg',
						1 => 'file 7.jpg',
						2 => 'file 8.jpg'
					]
				],
				'type' => [
					'files' => [
						0 => 'image/jpeg',
						1 => 'image/jpeg',
						2 => 'image/jpeg'
					]
				],
				'tmp_name' => [
					'files' => [
						0 => '/private/var/tmp/php2eViak',
						1 => '/private/var/tmp/phpMsC5Pp',
						2 => '/private/var/tmp/phpm2nm98'
					]
				],
				'error' => [
					'files' => [
						0 => 0,
						1 => 0,
						2 => 0
					]
				],
				'size' => [
					'files' => [
						0 => 418,
						1 => 418,
						2 => 418
					]
				]
			]
		];
		$expected = [
			'file' => [
				'name' => 'file.jpg',
				'type' => 'image/jpeg',
				'tmp_name' => '/private/var/tmp/phpows38J',
				'error' => 0,
				'size' => 418
			],
			'files' => [
				0 => [
					'name' => 'file 2.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpF5vsky',
					'error' => 0,
					'size' => 418
				],
				1 => [
					'name' => 'file 3.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phphRJ2zW',
					'error' => 0,
					'size' => 418
				],
				2 => [
					'name' => 'file 4.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phprI92L1',
					'error' => 0,
					'size' => 418
				]
			],
			'Image' => [
				'file' => [
					'name' => 'file 5.jpg',
					'type' => 'image/jpeg',
					'tmp_name' => '/private/var/tmp/phpAmSDL4',
					'error' => 0,
					'size' => 418
				]
			],
			'Photo' => [
				'files' => [
					0 => [
						'name' => 'file 6.jpg',
						'type' => 'image/jpeg',
						'tmp_name' => '/private/var/tmp/php2eViak',
						'error' => 0,
						'size' => 418
					],
					1 => [
						'name' => 'file 7.jpg',
						'type' => 'image/jpeg',
						'tmp_name' => '/private/var/tmp/phpMsC5Pp',
						'error' => 0,
						'size' => 418
					],
					2 => [
						'name' => 'file 8.jpg',
						'type' => 'image/jpeg',
						'tmp_name' => '/private/var/tmp/phpm2nm98',
						'error' => 0,
						'size' => 418
					]
				]
			]
		];

		$request = new Request();
		$result = $request->data;
		$this->assertEqual($expected, $result);

		unset($_FILES, $request);
	}

	public function testRequestTypeAccessors() {
		$request = new Request(['env' => ['REQUEST_METHOD' => 'GET']]);
		$this->assertTrue($request->is('get'));
		$this->assertFalse($request->is('post'));

		$request = new Request(['env' => ['REQUEST_METHOD' => 'POST']]);
		$this->assertTrue($request->is('post'));
		$this->assertFalse($request->is('get'));
		$this->assertFalse($request->is('put'));

		$request = new Request(['env' => ['REQUEST_METHOD' => 'PUT']]);
		$this->assertTrue($request->is('put'));
		$this->assertFalse($request->is('get'));
		$this->assertFalse($request->is('post'));
	}

	public function testRequestTypeIsMobile() {
		$request = new Request(['env' => [
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en)'
		]]);
		$isMobile = $request->is('mobile');
		$this->assertTrue($isMobile);
	}

	public function testUrlFromConstructor() {
		$request = new Request(['url' => 'posts/1']);

		$expected = '/posts/1';
		$result = $request->url;
		$this->assertEqual($expected, $result);
	}

	public function testDataFromConstructor() {
		$request = new Request(['data' => ['name' => 'bob']]);

		$expected = ['name' => 'bob'];
		$result = $request->data;
		$this->assertEqual($expected, $result);

		$_POST = ['organization' => 'Union of Rad'];
		$request = new Request(['data' => ['name' => 'bob']]);

		$expected = ['name' => 'bob', 'organization' => 'Union of Rad'];
		$result = $request->data;
		$this->assertEqual($expected, $result);
	}

	public function testQueryFromConstructor() {
		$request = new Request(['query' => ['page' => 1]]);

		$expected = ['page' => 1];
		$result = $request->query;
		$this->assertEqual($expected, $result);

		$_GET = ['limit' => 10];
		$request = new Request(['query' => ['page' => 1]]);

		$expected = ['page' => 1, 'limit' => 10];
		$result = $request->query;
		$this->assertEqual($expected, $result);
	}

	public function testMethodOverrideFromData() {
		$_POST['_method'] = 'put';
		$request = new Request();

		$result = $request->is('put');
		$this->assertTrue($result);

		unset($_POST);

		$request = new Request(['data' => ['_method' => 'put']]);

		$result = $request->is('put');
		$this->assertTrue($result);
	}

	public function testMergeMobileDetectors() {
		$request = new Request([
			'env' => ['HTTP_USER_AGENT' => 'testMobile'],
			'detectors' => ['mobile' => ['HTTP_USER_AGENT', ['testMobile']]]
		]);

		$result = $request->is('mobile');
		$this->assertTrue($result);

		$request = new Request([
			'env' => ['HTTP_USER_AGENT' => 'iPhone'],
			'detectors' => ['mobile' => ['HTTP_USER_AGENT', ['testMobile']]]
		]);

		$result = $request->is('mobile');
		$this->assertTrue($result);
	}

	public function testRequestTypeFromConstruct() {
		$request = new Request(['type' => 'json']);

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

	public function testStreamAutomaticContentDecoding() {
		foreach (['POST', 'PUT', 'PATCH'] as $method) {
			$stream = fopen('php://temp', 'r+');
			fwrite($stream, '{ "foo": "bar" }');
			rewind($stream);

			$request = new Request(compact('stream') + [
				'env' => [
					'CONTENT_TYPE' => 'application/json; charset=UTF-8',
					'REQUEST_METHOD' => $method
				]
			]);
			$this->assertEqual(['foo' => 'bar'], $request->data);
		}

		foreach (['GET', 'HEAD', 'OPTIONS', 'DELETE'] as $method) {
			$stream = fopen('php://temp', 'r+');
			fwrite($stream, '{ "foo": "bar" }');
			rewind($stream);

			$request = new Request(compact('stream') + [
				'env' => [
					'CONTENT_TYPE' => 'application/json; charset=UTF-8',
					'REQUEST_METHOD' => $method
				]
			]);
			$this->assertEmpty($request->data);

			fclose($stream);
		}
	}

	public function testStreamNoAutomaticContentDecodingNoDrain() {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, '{ "foo": "bar" }');

		foreach (['POST', 'PUT', 'PATCH'] as $method) {
			rewind($stream);

			$request = new Request(compact('stream') + [
				'drain' => false,
				'env' => [
					'CONTENT_TYPE' => 'application/json; charset=UTF-8',
					'REQUEST_METHOD' => $method
				]
			]);
			$this->assertEmpty($request->data);
		}
		fclose($stream);
	}

	public function testRequestTypeFromHeader() {
		$request = new Request(['env' => ['CONTENT_TYPE' => 'json']]);
		$this->assertEqual('json', $request->type());
	}

	public function testResponseTypeDetection() {
		$request = new Request(['env' => ['HTTP_ACCEPT' => 'text/xml,*/*']]);
		$this->assertEqual('xml', $request->accepts());

		$request->params['type'] = 'json';
		$this->assertEqual('json', $request->accepts());

		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'application/xml,image/png,*/*'
		]]);
		$this->assertEqual('xml', $request->accepts());

		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'application/xml,application/xhtml+xml'
		]]);
		$this->assertEqual('html', $request->accepts());

		$request = new Request(['env' => ['HTTP_ACCEPT' => null]]);
		$this->assertEqual('html', $request->accepts());
	}

	/**
	 * Tests that accepted content-types without a `q` value are sorted in the order they appear in
	 * the `HTTP_ACCEPT` header.
	 */
	public function testAcceptTypeOrder() {
		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'application/xhtml+xml,text/html'
		]]);
		$expected = ['application/xhtml+xml', 'text/html'];
		$this->assertEqual($expected, $request->accepts(true));

		$request = new Request(['env' => [
			'HTTP_USER_AGENT' => 'Safari',
			'HTTP_ACCEPT' => 'application/xhtml+xml,text/html,text/plain;q=0.9'
		]]);
		$expected = ['application/xhtml+xml', 'text/html', 'text/plain'];
		$this->assertEqual($expected, $request->accepts(true));
	}

	public function testAcceptWithTypeParam() {
		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'application/json'
		]]);
		$this->assertFalse($request->accepts('text'));
		$this->assertTrue($request->accepts('json'));
		$this->assertFalse($request->accepts('html'));
	}

	public function testAcceptWithTypeParamFallbackHtml() {
		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'nothing/matches'
		]]);
		$this->assertFalse($request->accepts('json'));
		$this->assertTrue($request->accepts('html'));

		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'application/json'
		]]);
		$this->assertFalse($request->accepts('text'));
		$this->assertTrue($request->accepts('json'));
		$this->assertFalse($request->accepts('html'));
	}

	public function testAcceptForBothXmlWithAliasedHtml() {
		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'application/xml'
		]]);
		$this->assertTrue($request->accepts('xml'));
		$this->assertFalse($request->accepts('html'));
	}

	public function testAcceptDoesNotAccepFullNamespacedType() {
		$request = new Request(['env' => [
			'HTTP_ACCEPT' => 'application/json'
		]]);
		$this->assertFalse($request->accepts('application/json'));
	}

	public function testParsingAcceptHeader() {
		$chrome = [
			'application/xml',
			'application/xhtml+xml',
			'text/html;q=0.9',
			'text/plain;q=0.8',
			'image/png',
			'*/*;q=0.5'
		];
		$firefox = [
			'text/html',
			'application/xhtml+xml',
			'application/xml;q=0.9',
			'*/*;q=0.8'
		];
		$safari = [
			'application/xml',
			'application/xhtml+xml',
			'text/html;q=0.9',
			'text/plain;q=0.8',
			'image/png',
			'*/*;q=0.5'
		];
		$opera = [
			'text/html',
			'application/xml;q=0.9',
			'application/xhtml+xml',
			'image/png',
			'image/jpeg',
			'image/gif',
			'image/x-xbitmap',
			'*/*;q=0.1'
		];
		$android = [
			'application/xml',
			'application/xhtml+xml',
			'text/html;q=0.9',
			'text/plain;q=0.8',
			'image/png',
			'*/*;q=0.5',
			'application/youtube-client'
		];
		$request = new Request(['env' => ['HTTP_ACCEPT' => join(',', $chrome)]]);
		$this->assertEqual('html', $request->accepts());
		$this->assertNotEmpty(array_search('text/plain', $request->accepts(true)), 4);

		$request = new Request(['env' => ['HTTP_ACCEPT' => join(',', $safari)]]);
		$this->assertEqual('html', $request->accepts());

		$request = new Request(['env' => ['HTTP_ACCEPT' => join(',', $firefox)]]);
		$this->assertEqual('html', $request->accepts());

		$request = new Request(['env' => ['HTTP_ACCEPT' => join(',', $opera)]]);
		$this->assertEqual('html', $request->accepts());

		$request = new Request(['env' => ['HTTP_ACCEPT' => join(',', $chrome)]]);
		$request->params['type'] = 'txt';

		$result = $request->accepts(true);
		$this->assertEqual('text/plain', $result[0]);

		$request = new Request(['env' => ['HTTP_ACCEPT' => join(',', $android)]]);
		$this->assertEqual('html', $request->accepts());
	}

	/**
	 * Tests that `Accept` headers with only one listed content type are parsed property, and tests
	 * that `'* /*'` is still parsed as `'text/html'`.
	 */
	public function testAcceptSingleContentType() {
		$request = new Request(['env' => ['HTTP_ACCEPT' => 'application/json,text/xml']]);
		$this->assertEqual(['application/json', 'text/xml'], $request->accepts(true));
		$this->assertEqual('json', $request->accepts());

		$request = new Request(['env' => ['HTTP_ACCEPT' => 'application/json']]);
		$this->assertEqual(['application/json'], $request->accepts(true));
		$this->assertEqual('json', $request->accepts());

		$request = new Request(['env' => ['HTTP_ACCEPT' => '*/*']]);
		$this->assertEqual(['text/html'], $request->accepts(true));
		$this->assertEqual('html', $request->accepts());
	}

	public function testLocaleDetection() {
		$request = new Request();
		$this->assertNull($request->locale());

		$request->params['locale'] = 'fr';
		$this->assertEqual('fr', $request->locale());

		$request->locale('de');
		$this->assertEqual('de', $request->locale());
	}

	/**
	 * Tests that `action\Request` correctly inherits the functionality of the `to()` method
	 * inherited from `lithium\net\http\Request`.
	 */
	public function testConvertToUrl() {
		$request = new Request([
			'env' => ['HTTP_HOST' => 'foo.com', 'HTTPS' => 'on'],
			'base' => '/the/base/path',
			'url' => '/the/url',
			'query' => ['some' => 'query', 'parameter' => 'values']
		]);
		$expected = 'https://foo.com/the/base/path/the/url?some=query&parameter=values';
		$this->assertEqual($expected, $request->to('url'));

		$request = new Request([
			'env' => ['HTTP_HOST' => 'foo.com'],
			'base' => '/',
			'url' => '/',
			'query' => []
		]);
		$expected = 'http://foo.com/';
		$this->assertEqual($expected, $request->to('url'));

		$request = new Request([
			'url' => 'foo/bar',
			'base' => null,
			'env' => ['HTTP_HOST' => 'example.com', 'PHP_SELF' => '/index.php']
		]);

		$expected = 'http://example.com/foo/bar';
		$this->assertEqual($expected, $request->to('url'));
	}

	public function testConvertToUrl2() {
		$request = new Request([
			'env' => ['HTTP_HOST' => 'foo.com', 'HTTPS' => 'on'],
			'base' => '/the/base/path',
			'url' => '/posts',
			'params' => ['controller' => 'posts', 'action' => 'index'],
			'query' => ['some' => 'query', 'parameter' => 'values']
		]);
		$expected = 'https://foo.com/the/base/path/posts?some=query&parameter=values';
		$this->assertEqual($expected, $request->to('url'));
	}

	public function testConvertToString() {
		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'foo.com',
				'HTTPS' => 'on',
				'CONTENT_TYPE' => 'text/html',
				'HTTP_CUSTOM_HEADER' => 'foobar'
			],
			'base' => '/the/base/path',
			'url' => '/posts',
			'query' => ['some' => 'query', 'parameter' => 'values']
		]);

		$expected = join("\r\n", [
			'GET /the/base/path/posts?some=query&parameter=values HTTP/1.1',
			'Host: foo.com',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: text/html',
			'Custom-Header: foobar',
			'',''
		]);
		$this->assertEqual($expected, $request->to('string'));
	}

	public function testConvertToStringWithPost() {
		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'li3.me',
				'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
				'HTTP_USER_AGENT' => 'Mozilla/5.0'
			],
			'url' => '/posts',
			'data' => ['some' => 'body', 'parameter' => 'values']
		]);

		$expected = join("\r\n", [
			'GET /posts HTTP/1.1',
			'Host: li3.me',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: 26',
			'', 'some=body&parameter=values'
		]);
		$this->assertEqual($expected, $request->to('string'));
	}

	public function testConvertToStringWithJson() {
		$expected = join("\r\n", [
			'GET /posts HTTP/1.1',
			'Host: li3.me',
			'Connection: Close',
			'User-Agent: Mozilla/5.0',
			'Content-Type: application/json',
			'Content-Length: 36',
			'', '{"some":"body","parameter":"values"}'
		]);

		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'li3.me',
				'CONTENT_TYPE' => 'application/json',
				'HTTP_USER_AGENT' => 'Mozilla/5.0'
			],
			'url' => '/posts',
			'body' => '{"some":"body","parameter":"values"}'
		]);

		$this->assertEqual($expected, $request->to('string'));

		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'li3.me',
				'CONTENT_TYPE' => 'application/json',
				'HTTP_USER_AGENT' => 'Mozilla/5.0'
			],
			'url' => '/posts',
			'data' => ['some' => 'body', 'parameter' => 'values']
		]);

		$this->assertEqual($expected, $request->to('string'));
	}

	/**
	 * Tests that the HTTP request method set by `Request` from the server information is not
	 * overwritten in a parent class.
	 */
	public function testRequestMethodConfiguration() {
		$request = new Request(['env' => ['REQUEST_METHOD' => 'POST']]);
		$this->assertEqual('POST', $request->method);
		$this->assertTrue($request->is('post'));

		$request = new Request(['env' => ['REQUEST_METHOD' => 'PATCH']]);
		$this->assertEqual('PATCH', $request->method);
		$this->assertTrue($request->is('patch'));
	}

	public function testRequestUriWithHtAccessRedirection() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'REQUEST_URI' => '/lithium/hello/world?page=1',
			'PHP_SELF' => '/lithium/app/webroot/index.php'
		]]);

		$this->assertIdentical('/lithium', $request->env('base'));
		$this->assertIdentical('/hello/world', $request->url);
	}

	public function testRequestUriWithNoHtAccessRedirection() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'REQUEST_URI' => '/lithium/app/webroot/hello/world?page=1',
			'PHP_SELF' => '/lithium/app/webroot/index.php'
		]]);

		$this->assertIdentical('/lithium', $request->env('base'));
		$this->assertIdentical('/app/webroot/hello/world', $request->url);
	}

	public function testRequestUriWithVirtualHost() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www/lithium/app/webroot',
			'REQUEST_URI' => '/hello/world?page=1',
			'PHP_SELF' => '/index.php'
		]]);

		$this->assertIdentical('', $request->env('base'));
		$this->assertIdentical('/hello/world', $request->url);
	}

	public function testRequestUriWithAdminRoute() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www/lithium/app/webroot',
			'REQUEST_URI' => '/lithium/admin/hello/world?page=1',
			'PHP_SELF' => '/lithium/app/webroot/index.php'
		]]);

		$this->assertIdentical('/lithium', $request->env('base'));
		$this->assertIdentical('/admin/hello/world', $request->url);
	}

	public function testRequestWithNoGlobals() {
		$_SERVER = ['env' => [
			'DOCUMENT_ROOT' => '/www',
			'HTTP_HOST' => 'foo.com',
			'HTTPS' => 'on',
			'SERVER_PROTOCOL' => 'HTTP/1.0',
			'REQUEST_URI' => '/lithium/app/hello/world?page=1',
			'PHP_SELF' => '/lithium/app/index.php'
		]];
		$request = new Request(['globals' => false]);

		$this->assertIdentical('localhost', $request->host);
		$this->assertIdentical('http', $request->scheme);
		$this->assertIdentical('HTTP/1.1', $request->protocol);
		$this->assertIdentical('1.1', $request->version);
		$this->assertIdentical('/', $request->url);
		$this->assertIdentical('', $request->env('base'));
	}

	public function testRequestWithEnvVariables() {
		$request = new Request(['env' => [
			'DOCUMENT_ROOT' => '/www',
			'HTTP_HOST' => 'foo.com',
			'HTTPS' => 'on',
			'SERVER_PROTOCOL' => 'HTTP/1.0',
			'REQUEST_URI' => '/lithium/app/hello/world?page=1',
			'PHP_SELF' => '/lithium/app/index.php'
		]]);

		$this->assertIdentical('foo.com', $request->host);
		$this->assertIdentical('https', $request->scheme);
		$this->assertIdentical('HTTP/1.0', $request->protocol);
		$this->assertIdentical('1.0', $request->version);
		$this->assertIdentical('/hello/world', $request->url);
		$this->assertIdentical('/lithium/app', $request->env('base'));
	}

	public function testEnvVariablesArePopulated() {
		$request = new Request(['env' => [
			'HTTP_HOST' => 'foo.com',
			'HTTPS' => 'on',
			'SERVER_PROTOCOL' => 'HTTP/1.0'
		]]);

		$this->assertIdentical('foo.com', $request->host);
		$this->assertIdentical('https', $request->scheme);
		$this->assertIdentical('HTTP/1.0', $request->protocol);
		$this->assertIdentical('1.0', $request->version);
	}

	public function testOverridingOfEnvVariables() {
		$request = new Request([
			'env' => [
				'HTTP_HOST' => 'foo.com',
				'HTTPS' => 'on',
				'SERVER_PROTOCOL' => 'HTTP/1.0'
			],
			'host' => 'bar.com',
			'scheme' => 'http',
			'protocol' => 'HTTP/1.1'
		]);

		$this->assertIdentical('bar.com', $request->host);
		$this->assertIdentical('http', $request->scheme);
		$this->assertIdentical('HTTP/1.1', $request->protocol);
		$this->assertIdentical('1.1', $request->version);
	}
}

?>