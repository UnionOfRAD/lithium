<?php
/**
 * Locate and load Lithium core library files.  Throws a fatal error if the core can't be found.
 * If your Lithium core directory is named something other than `lithium`, change the string below.
 */
if (!include LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php') {
	$message  = "Lithium core could not be found.  Check the value of LITHIUM_LIBRARY_PATH in ";
	$message .= __FILE__ . ".  It should point to the directory containing your ";
	$message .= "/libraries directory.";
	throw new ErrorException($message);
}

use lithium\core\Libraries;

/**
 * Add the Lithium core library.  This sets default paths and initializes the autoloader.  You
 * generally should not need to override any settings.
 */
Libraries::add('lithium');
Libraries::add('li3_fixtures');
Libraries::add('ci', array(
	'default' => true,
	'resources' => call_user_func(function() {
		if (is_dir($resources = str_replace("//", "/", sys_get_temp_dir() . '/resources'))) {
			return $resources;
		}
		foreach (array($resources, "{$resources}/logs", "{$resources}/tmp/cache/templates") as $d) {
			mkdir($d, 0777, true);
		}
		return $resources;
	})
));
?>