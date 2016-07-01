<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\net\http;

use lithium\net\http\Media;
use lithium\net\http\Response;

class MediaTest extends \lithium\test\Integration {

	/**
	 * This tests that setting custom paths and disabling layout
	 * via `\lithium\net\http\Media::type()` is handled properly
	 * by the default `\lithium\template\View` class and `File`
	 * rendered adapter.
	 */
	public function testMediaTypeViewRender() {
		Media::type('view-integration-test', 'lithium/viewtest', [
			'view' => 'lithium\template\View',
			'paths' => [
				'layout' => false,
				'template' => [
					'{:library}/tests/mocks/template/view/adapters/{:template}.{:type}.php',
					'{:library}/tests/mocks/template/view/adapters/{:template}.html.php'
				]
			]
		]);

		// testing no layout with a custom type template
		$response = new Response();
		$response->type('view-integration-test');
		Media::render($response, [], [
			'layout' => true,
			'library' => 'lithium',
			'template' => 'testTypeFile'
		]);
		$this->assertEqual('This is a type test.', $response->body());

		// testing the template falls back to the html template
		$response = new Response();
		$response->type('view-integration-test');
		Media::render($response, [], [
			'layout' => true,
			'library' => 'lithium',
			'template' => 'testFile'
		]);
		$this->assertEqual('This is a test.', $response->body());

		// testing with a layout
		Media::type('view-integration-test', 'lithium/viewtest', [
			'view' => 'lithium\template\View',
			'paths' => [
				'layout' => '{:library}/tests/mocks/template/view/adapters/testLayoutFile.html.php',
				'template' => [
					'{:library}/tests/mocks/template/view/adapters/{:template}.{:type}.php',
					'{:library}/tests/mocks/template/view/adapters/{:template}.html.php'
				]
			]
		]);
		$response = new Response();
		$response->type('view-integration-test');
		Media::render($response, [], [
			'layout' => true,
			'library' => 'lithium',
			'template' => 'testTypeFile'
		]);
		$this->assertEqual("Layout top.\nThis is a type test.Layout bottom.", $response->body());

		Media::type('view-integration-test', false);
	}
}

?>