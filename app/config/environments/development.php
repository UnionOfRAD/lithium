<?php

use \lithium\core\Libraries;
use \lithium\core\Environment;

/*
 * Set up the "development" environment
 */
Environment::set("development", array(
	"Output.varDump"            => true,                // Writes output from pr() and debug()
	"Output.sqlDump"            => true,                // Writes SQL log at the bottom of the page
	"Output.timestamp.enabled"  => true,                // Output the page generation time
	"Output.timestamp.format"   => "<!-- %01.4fs -->",  // Page generation time output format string
	"Cache.enabled"             => true,
	"Cache.expires"             => "+10 seconds",
	"Asset.compress"            => false,
	"Asset.timestamp"           => true,
	// "G11n.locale"				=> "en",
	// "G11n.timezone"				=> "Etc/UTC",
	// "G11n.currency"				=> "USD"
));

/*
 * Set the current environment to "development"
 */
// switch (true) {
// 	case
// }
Environment::set("development");

?>