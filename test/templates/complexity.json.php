<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

$worstOffender = null;
$averages = [];

foreach (array_slice($data['max'], 0, 10) as $method => $count) {
	if ($count <= 7) {
		continue;
	}
	$worstOffender = compact('method', 'count');
}
foreach (array_slice($data['class'], 0, 10) as $class => $count) {
	$averages[$class] = $count;
}

echo json_encode(compact('worstOffender', 'averages'));

?>