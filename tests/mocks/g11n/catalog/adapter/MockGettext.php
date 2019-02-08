<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\g11n\catalog\adapter;

class MockGettext extends \lithium\g11n\catalog\adapter\Gettext {

	public $mo = true;

	public $po = true;

	protected function _files($category, $locale, $scope) {
		$files = parent::_files($category, $locale, $scope);

		foreach ($files as $key => $file) {
			$extension = pathinfo($file, PATHINFO_EXTENSION);

			if ((!$this->mo && $extension === 'mo') || (!$this->po && $extension === 'po')) {
				unset($files[$key]);
			}
		}
		return $files;
	}
}

?>