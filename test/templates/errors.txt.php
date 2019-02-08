<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

$i = 0;
foreach ((array) $stats['errors'] as $error) {
	$i++;

	if ($error['result'] == 'fail') {
		echo "{:red}Failed{:end} assertion {$error['assertion']}.\n";
		echo " File    : {$error['file']}\n";
		echo " Class   : {$error['class']}\n";
		echo " Method  : {$error['method']}()\n";
		echo " Line    : {$error['line']}\n";
		echo " ________\n";
		echo "{$error['message']}\n";
		echo " ________\n";
		echo "\n";
	} elseif ($error['result'] == 'exception') {
		if ($error['code'] !== 0) {
			echo "{:purple}{$error['name']} ({$error['code']}){:end} thrown.\n";
		} else {
			echo "{:purple}{$error['name']}{:end} thrown.\n";
		}
		echo " File    : {$error['file']}\n";
		echo " Class   : {$error['class']}\n";
		echo " Method  : {$error['method']}()\n";
		echo " Line    : {$error['line']}\n";
		echo " ________\n";
		echo "{$error['message']}\n";
		echo " ________\n";

		if (isset($error['trace']) && !empty($error['trace'])) {
			echo "{$error['trace']}\n";
			echo " ________\n";
		}
		echo "\n";
	}
}

?>