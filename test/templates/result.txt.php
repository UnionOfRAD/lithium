<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

if ($success && $count['skips']) {
	echo "{:green}OK, but skipped tests.{:end}\n";
} elseif ($success) {
	echo "{:green}OK{:end}\n";
} else {
	echo "{:red}FAIL{:end}\n";
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