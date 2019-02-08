<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

$render = function($self, $path, $parent = null) use ($base) {
	$result = ["<ul class='menu'>"];

	foreach ($path as $current => $value) {
		$path = trim(str_replace("//", "/", "{$parent}/{$current}"), "/");
		$result[] = "<li>";

		if (is_string($value)) {
			$result[] =  "<a title='run {$path}' "
				. "href='{$base}/test/{$path}'>{$current}</a>";
			continue;
		}
		$result[] =  "<a class='menu-folder' title='run {$path}' "
			. "href='{$base}/test/{$path}'>{$current}</a>";
		$result[] = $self($self, $value, $path);
		$result[] = "</li>";
	}
	$result[] = "</ul>";
	return join("\n", $result);
};
echo $render($render, $menu);
?>