<?php
echo "===Cyclomatic Complexity===\n";

foreach (array_slice($analysis['max'], 0, 10) as $method => $count) {
	if ($count <= 7) {
		continue;
	}
	echo "Worst Offender\n\t{$method} - {$count}\n";
}
echo "Class Averages\n";
foreach (array_slice($analysis['class'], 0, 10) as $class => $count) {
	echo "\t{$class} - ";
	echo round($count, 2) . "\n";
}

echo "\n";

?>