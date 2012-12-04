<?php

$render = function($self, $path, $parent = null) use ($base) {
	foreach ($path as $current => $value) {
		$path = trim(str_replace("//", "/", "{$parent}/{$current}"), "/");

		if (is_string($value)) {
			echo  "<li><a title='run {$path}'
			href='{$base}/test/{$path}'>{$current}</a></li>";
			continue;
		}
		echo "<li><a class='menu-folder' title='run {$path}'
			href='{$base}/test/{$path}'>{$current}</a></li>";
		echo "<ul>";
		$self($self, $value, $path);
		echo "<ul>";
	}
};
echo "<ul>";
$render($render, $menu);
echo "<ul>";
?>