<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\http;

use \lithium\http\Media;
use \lithium\action\Request;

class MediaTest extends \lithium\test\Unit {

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

		$result = Media::type('json');
		$expected = 'application/json';
		$this->assertEqual($expected, $result['content']);

		$expected = array(
			'view' => false, 'layout' => false, 'encode' => 'json_encode', 'decode' => 'json_decode'
		);
		$this->assertEqual($expected, $result['options']);

		Media::type('my', 'text/x-my', array('view' => '\my\custom\View', 'layout' => false));

		$result = Media::types();
		$this->assertTrue(in_array('my', $result));

		$result = Media::type('my');
		$expected = 'text/x-my';
		$this->assertEqual($expected, $result['content']);

		$expected = array(
			'view' => '\my\custom\View',
			'template' => null,
			'layout' => null,
			'encode' => null,
			'decode' => null
		);
		$this->assertEqual($expected, $result['options']);

		Media::type('my', false);
		$result = Media::types();
		$this->assertFalse(in_array('my', $result));
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
	}

	public function testAssetPathGeneration() {
		$result = Media::asset('scheme://host/subpath/file', 'js');
		$expected = 'scheme://host/subpath/file';
		$this->assertEqual($expected, $result);

		$result = Media::asset('subpath/file', 'js');
		$expected = '/js/subpath/file.js';
		$this->assertEqual($expected, $result);

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

	public function testCustomEncodeHandler() {
		Media::asset('csv', 'application/csv', array('encode' => function($data) {
		 	ob_start();
		 	$out = fopen('php://output', 'w');
		 	foreach ($data as $record) {
		 		fputcsv($out, $record->to('array'));
		 	}
		 	fclose($out);
		 	$content = ob_get_clean();
		 	return $content;
		}));
	}
}

?>