<?php

foreach ((array) $stats['skips'] as $skip) {
	$trace = $skip['trace'][1];

	echo "Skip `{$skip['message']}`.\n";
	echo " Class   : {$trace['class']}\n";
	echo " Method  : {$trace['function']}()\n";
	echo "\n";
}

?>