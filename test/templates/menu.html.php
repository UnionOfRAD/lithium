<?php
$render = function($self, $path, $parent = null) use ($base) {
	foreach ($path as $current => $value) {
		if (is_string($value)) {
			echo  "<li><a title='run {$parent}\{$current}'
			href='{$base}/{$parent}/{$current}'>{$current}</a></li>";
			continue;
		}
		echo "<li><a class='menu-folder' title='run {$parent}\{$current}'
			href='{$base}/{$parent}/{$current}'>{$current}</a><ul>";
		$self($self, $value, $parent . '/' . $current);
		echo "</ul></li>";
	}
};
echo "<ul>";
$render($render, $menu, 'test');
echo "</ul>";
?>