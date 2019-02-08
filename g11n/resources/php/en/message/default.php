<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

/**
 * Message data for `en`.
 *
 * Plural rule and forms derived from the GNU gettext documentation.
 *
 * @link http://www.gnu.org/software/gettext/manual/gettext.html#Plural-forms
 */
return [
	'pluralForms' => 2,
	'pluralRule' => function ($n) { return $n != 1 ? 1 : 0; }
];

?>