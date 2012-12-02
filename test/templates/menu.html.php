<?php
$render = function($self, $path, $parent = null) use ($base) {
	foreach ($path as $current => $value) {
		if (is_string($value)) {
			echo  "<li><a title='run {$parent}\{$current}'
			href='{$base}/{$parent}/{$current}'>{$current}</a></li>";
			continue;
		}
		echo "<li><a class='menu-folder' title='run {$parent}\{$current}'
			href='{$base}/{$parent}/{$current}'>{$current}</a></li>";
		echo "<ul>";
		$self($value, $current, $render);
		echo "<ul>";
	}
};
echo "<ul>";
$render($render, $menu);
echo "<ul>";
?>