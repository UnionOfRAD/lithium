<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test;

use lithium\test\Mocker;
use lithium\aop\Filters;
use lithium\data\entity\document\Mock as Document;
use lithium\tests\mocks\data\mockPost\Mock as MockPost;

/**
 * WARNING:
 * No unit test should mock the same test as another to avoid conflicting filters.
 *
 * @deprecated
 */
class MockerTest extends \lithium\test\Unit {

	protected $_backup;

	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);
		Mocker::register();
	}

	public function tearDown() {
		Filters::clear('lithium\test\Mocker');
		Filters::clear('lithium\test\Mocker');

		error_reporting($this->_backup);
	}

	public function testAutoloadRegister() {
		Mocker::register();
		$registered = spl_autoload_functions();
		$this->assertTrue(in_array([
			'lithium\test\Mocker',
			'create'
		], $registered));
	}

	public function testBasicCreation() {
		$mockee = 'lithium\console\command\Mock';
		Mocker::create($mockee);
		$this->assertTrue(class_exists($mockee));
	}

	public function testBasicCreationExtendsCorrectParent() {
		$mockeeObj = new \lithium\console\request\Mock();
		$this->assertInstanceOf('lithium\console\Request', $mockeeObj);
	}

	public function testCanMockNonLithiumClasses() {
		$mockee = 'stdClass\Mock';
		Mocker::create($mockee);
		$this->assertTrue(class_exists($mockee));
	}

	public function testNonLithiumInstanceClass() {
		$std = new \lithium\tests\mocks\test\mockNonLi3StdClass\Mock;

		$this->assertInternalType('bool', $std->method1());

		$std->applyFilter('method1', function($params, $next) {
			return [];
		});

		$this->assertInternalType('array', $std->method1());
		Filters::clear('\lithium\tests\mocks\test\mockNonLi3StdClass\Mock');
	}

	public function testNonLithiumStaticClass() {
		$class = 'lithium\analysis\debugger\Mock';
		$var = ['foo', 'bar', 'baz'];

		$this->assertInternalType('string', $class::export($var));

		$class::applyFilter('export', function($params, $next) {
			return [];
		});

		$this->assertInternalType('array', $class::export($var));
		Filters::clear($class);
	}

	public function testCannotCreateNonStandardMockClass() {
		$mockee = 'lithium\console\request\Mocker';
		Mocker::create($mockee);
		$this->assertTrue(!class_exists($mockee));
	}

	public function testFilteringNonStaticClass() {
		$dispatcher = new \lithium\console\dispatcher\Mock();

		$originalResult = $dispatcher->config([]);

		$dispatcher->applyFilter('config', function($params, $next) {
			return [];
		});

		$filteredResult = $dispatcher->config([]);

		$this->assertCount(0, $filteredResult);
		$this->assertNotEqual($filteredResult, $originalResult);

		Filters::clear('\lithium\console\dispatcher\Mock');
	}

	public function testFilteringNonStaticClassCanReturnOriginal() {
		$response = new \lithium\console\response\Mock();

		$originalResult = $response->styles();

		$response->applyFilter('styles', function($params, $next) {
			return $next($params);
		});

		$filteredResult = $response->styles();

		$this->assertEqual($filteredResult, $originalResult);

		Filters::clear('\lithium\console\response\Mock');
	}

	public function testFilteringStaticClass() {
		$mockee = 'lithium\analysis\parser\Mock';

		$code = 'echo "foobar";';

		$originalResult = $mockee::tokenize($code, ['wrap' => true]);

		$mockee::applyFilter('tokenize', function($params, $next) {
			return [];
		});

		$filteredResult = $mockee::tokenize($code, ['wrap' => true]);

		$this->assertCount(0, $filteredResult);
		$this->assertNotEqual($filteredResult, $originalResult);

		Filters::clear($mockee);
	}

	public function testFilteringStaticClassCanReturnOriginal() {
		$mockee = 'lithium\analysis\debugger\Mock';

		$originalResult = $mockee::export(['foo', 'bar', 'baz']);

		$mockee::applyFilter('export', function($params, $next) {
			return $next($params);
		});

		$filteredResult = $mockee::export(['foo', 'bar', 'baz']);

		$this->assertEqual($filteredResult, $originalResult);

		Filters::clear($mockee);
	}

	public function testOriginalMethodNotCalled() {
		$http = new \lithium\tests\mocks\security\auth\adapter\mockHttp\Mock;

		$this->assertCount(0, $http->headers);

		$http->_writeHeader('Content-type: text/html');

		$this->assertCount(1, $http->headers);

		$http->applyFilter('_writeHeader', function($params, $next) {
			return false;
		});

		$http->_writeHeader('Content-type: application/pdf');

		$this->assertCount(1, $http->headers);

		Filters::clear('lithium\tests\mocks\security\auth\adapter\mockHttp\Mock');
	}

	public function testFilteringAFilteredMethod() {
		$adapt = 'lithium\core\adaptable\Mock';
		$adapt::applyFilter('_initAdapter', function($params, $next) {
			return false;
		});
		$this->assertFalse($adapt::_initAdapter('foo', []));

		Filters::clear($adapt);
	}

	public function testStaticResults() {
		$docblock = 'lithium\analysis\docblock\Mock';
		$docblock::applyFilter(['comment', 'tags'], function($params, $next) {
			return false;
		});
		$docblock::comment('foobar');
		$docblock::comment('bar');
		$docblock::tags('baz', 'foo');

		$this->assertIdentical(2, count($docblock::$staticResults['comment']));
		$this->assertIdentical(['foobar'], $docblock::$staticResults['comment'][0]['args']);
		$this->assertFalse($docblock::$staticResults['comment'][0]['result']);
		$this->assertIdentical(['bar'], $docblock::$staticResults['comment'][1]['args']);
		$this->assertFalse($docblock::$staticResults['comment'][1]['result']);

		$this->assertIdentical(1, count($docblock::$staticResults['tags']));
		$this->assertIdentical(['baz', 'foo'], $docblock::$staticResults['tags'][0]['args']);
		$this->assertFalse($docblock::$staticResults['tags'][0]['result']);

		Filters::clear($docblock);
	}

	public function testInstanceResults() {
		$debugger = new \lithium\data\schema\Mock;
		$debugger->applyFilter(['names', 'meta'], function($params, $next) {
			return false;
		});
		$debugger->names('foo', 'foobar');
		$debugger->names('bar');
		$debugger->meta('baz');

		$this->assertIdentical(2, count($debugger->results['names']));
		$this->assertIdentical(['foo', 'foobar'], $debugger->results['names'][0]['args']);
		$this->assertFalse($debugger->results['names'][0]['result']);
		$this->assertIdentical(['bar'], $debugger->results['names'][1]['args']);
		$this->assertFalse($debugger->results['names'][1]['result']);

		$this->assertIdentical(1, count($debugger->results['meta']));
		$this->assertIdentical(['baz'], $debugger->results['meta'][0]['args']);
		$this->assertFalse($debugger->results['meta'][0]['result']);

		Filters::clear('lithium\data\schema\Mock');
	}

	public function testSkipByReference() {
		$stdObj = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$stdObj->foo = 'foo';
		$originalData = $stdObj->data();
		$stdObj->applyFilter('data', function($params, $next) {
			return [];
		});
		$nonfilteredData = $stdObj->data();
		$this->assertIdentical($originalData, $nonfilteredData);

		Filters::clear('lithium\tests\mocks\test\mockStdClass\Mock');
	}

	public function testGetByReference() {
		$stdObj = new \lithium\tests\mocks\test\mockStdClass\Mock();
		$stdObj->foo = 'foo';
		$foo =& $stdObj->foo;
		$foo = 'bar';
		$this->assertIdentical('bar', $stdObj->foo);
	}

	public function testChainReturnsMockerChain() {
		$this->assertInstanceOf('lithium\test\MockerChain', Mocker::chain(new \stdClass));
	}

	public function testMergeWithEmptyArray() {
		$results = [];
		$staticResults = [
			'method1' => [
				[
					'args' => [],
					'results' => true,
					'time' => 100,
				],
			],
		];

		$this->assertEqual($staticResults, Mocker::mergeResults($results, $staticResults));
		$this->assertEqual($staticResults, Mocker::mergeResults($staticResults, $results));
	}

	public function testMultipleResultsSimple() {
		$results = [
			'method1' => [
				[
					'args' => [],
					'results' => true,
					'time' => 100,
				],
			],
		];
		$staticResults = [
			'method1' => [
				[
					'args' => [],
					'results' => true,
					'time' => 0,
				],
			],
		];
		$expected = [
			'method1' => [
				[
					'args' => [],
					'results' => true,
					'time' => 0,
				],
				[
					'args' => [],
					'results' => true,
					'time' => 100,
				],
			],
		];
		$this->assertEqual($expected, Mocker::mergeResults($results, $staticResults));
	}

	public function testMultipleResultsComplex() {
		$results = [
			'method1' => [
				[
					'args' => [],
					'results' => true,
					'time' => 100,
				],
			],
			'method2' => [
				[
					'args' => [],
					'results' => true,
					'time' => 100,
				],
			],
		];
		$staticResults = [
			'method1' => [
				[
					'args' => [],
					'results' => true,
					'time' => 0,
				],
				[
					'args' => [],
					'results' => true,
					'time' => 200,
				],
			],
		];
		$expected = [
			'method1' => [
				[
					'args' => [],
					'results' => true,
					'time' => 0,
				],
				[
					'args' => [],
					'results' => true,
					'time' => 100,
				],
				[
					'args' => [],
					'results' => true,
					'time' => 200,
				],
			],
			'method2' => [
				[
					'args' => [],
					'results' => true,
					'time' => 100,
				],
			],
		];

		$this->assertEqual($expected, Mocker::mergeResults($results, $staticResults));
	}

	public function testCreateFunction() {
		$obj = new \lithium\tests\mocks\test\MockStdClass;
		Mocker::overwriteFunction('lithium\tests\mocks\test\get_class', function($obj) {
			return 'foo';
		});
		$this->assertIdentical('foo', $obj->getClass());
	}

	public function testCreateFunctionWithByReferenceParam() {
		Mocker::overwriteFunction('lithium\tests\mocks\test\getmxrr', function($host, &$mxhosts) {
			$mxhosts = 'foo_bar';
			return;
		});
		$foo = 'baz';
		\lithium\tests\mocks\test\getmxrr('foo', $foo);
		$this->assertIdentical('foo_bar', $foo);
	}

	public function testCallFunctionUsesGlobalFallback() {
		$result = Mocker::callFunction('foo\bar\baz\get_called_class');
		$this->assertIdentical('lithium\test\Mocker', $result);
	}

	public function testMultipleCreateFunction() {
		$obj = new \lithium\tests\mocks\test\MockStdClass;
		Mocker::overwriteFunction('lithium\tests\mocks\test\get_class', function($obj) {
			return 'foo';
		});
		Mocker::overwriteFunction('lithium\tests\mocks\test\get_class', function($obj) {
			return 'bar';
		});
		$this->assertIdentical('bar', $obj->getClass());
	}

	public function testResetSpecificFunctions() {
		$obj = new \lithium\tests\mocks\test\MockStdClass;
		Mocker::overwriteFunction('lithium\tests\mocks\test\get_class', function($obj) {
			return 'baz';
		});
		Mocker::overwriteFunction('lithium\tests\mocks\test\is_executable', function($foo) {
			return 'qux';
		});
		Mocker::overwriteFunction('lithium\tests\mocks\test\get_class', false);

		$this->assertIdentical('lithium\tests\mocks\test\MockStdClass', $obj->getClass());
		$this->assertIdentical('qux', $obj->isExecutable());
	}

	public function testResetAllFunctions() {
		$obj = new \lithium\tests\mocks\test\MockStdClass;
		Mocker::overwriteFunction('lithium\tests\mocks\test\get_class', function($obj) {
			return 'baz';
		});
		Mocker::overwriteFunction('lithium\tests\mocks\test\is_executable', function($foo) {
			return 'qux';
		});
		Mocker::overwriteFunction(false);

		$this->assertIdentical('lithium\tests\mocks\test\MockStdClass', $obj->getClass());
		$this->assertInternalType('bool', $obj->isExecutable());
	}

	public function testMagicCallGetStoredResultsWhenCalled() {
		$obj = new \lithium\tests\mocks\test\mockStdClass\Mock;

		$obj->__call('foo', []);
		$results = Mocker::mergeResults($obj->results, $obj::$staticResults);

		$this->assertArrayHasKey('__call', $results);
		$this->assertArrayNotHasKey('__callStatic', $results);
	}

	public function testMagicCallStaticGetStoredResultsWhenCalled() {
		$obj = new \lithium\tests\mocks\test\mockStdClass\Mock;

		$obj->__callStatic('foo', []);
		$results = Mocker::mergeResults($obj->results, $obj::$staticResults);

		$this->assertArrayHasKey('__callStatic', $results);
		$this->assertArrayNotHasKey('__call', $results);
	}

	public function testMagicCallGetStoredResultsWhenCalledIndirectly() {
		$obj = new \lithium\tests\mocks\test\mockStdClass\Mock;

		$obj->methodBar();
		$results = Mocker::mergeResults($obj->results, $obj::$staticResults);

		$this->assertArrayHasKey('__call', $results);
		$this->assertCount(2, $results['__call']);
	}

	public function testDoesNotThrowExceptionWhenMockingIterator() {
		$this->assertNotException('Exception', function() {
			new \lithium\util\collection\Mock;
		});
	}

	public function testMockDocument() {
		$document = new Document();
	}

	// This tests fails with max recursion.
	// public function testMockModel() {
	// 	$entity = MockPost::create();
	// }

	public function testConstructParams() {
		$expected = 'lithium\tests\mocks\data\MockPost';
		$document = new Document(['model' => $expected]);
		$this->assertIdentical($expected, $document->model());
	}
}

?>