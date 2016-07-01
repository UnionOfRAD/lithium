<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

/**
 * Validation data for `en_US`.
 */
return [
	'phone' => '/^(?:\+?1)?[-. ]?\\(?[2-9][0-8][0-9]\\)?[-. ]?[2-9][0-9]{2}[-. ]?[0-9]{4}$/',
	'postalCode' => '/\\A\\b[0-9]{5}(?:-[0-9]{4})?\\b\\z/i',
	'ssn' => '/\\A\\b[0-9]{3}-[0-9]{2}-[0-9]{4}\\b\\z/i'
];

?>