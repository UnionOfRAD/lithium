<?php

if ($success && $count['skips']) {
	echo "OK, but skipped tests.\n";
} elseif ($success) {
	echo "OK\n";
} else {
	echo "FAIL\n";
}
echo "\n";

printf(
	"%d / %d %s\n",
	$count['passes'],
	$count['asserts'],
	$count['passes'] == 1 ? 'pass' : 'passes'
);
printf(
	'%d %s',
	$count['fails'],
	$count['fails'] == 1 ? 'fail' : 'fails'
);
printf(
	" and %d %s\n",
	$count['exceptions'],
	$count['exceptions'] == 1 ? 'exception' : 'exceptions'
);

?>