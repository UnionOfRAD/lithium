<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

foreach ((array) $stats['skips'] as $skip) {
	$trace = $skip['trace'][1];

	echo "{:cyan}Skip{:end} `{$skip['message']}`.\n";
	echo " Class   : {$trace['class']}\n";
	echo " Method  : {$trace['function']}()\n";
	echo "\n";
}

?>