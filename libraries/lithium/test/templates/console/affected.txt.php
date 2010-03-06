<?php
	echo "===Additional Affected Tests===\n";

	foreach ($analysis as $class => $test) {
		if ($test) {
			echo "{$test}\n";
		}
	}
?>