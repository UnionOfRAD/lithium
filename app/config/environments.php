<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\core\Libraries;
use \lithium\core\Environment;

/*
 * Set up the "development" environment
 */
Environment::set("development", array(
	"Output.varDump"	    	=> true,		    	// Writes output from pr() and debug()
	"Output.sqlDump"	    	=> true,		    	// Writes SQL log at the bottom of the page
	"Output.timestamp.enabled"	=> true,		    	// Output the page generation time
	"Output.timestamp.format"	=> "<!-- %01.4fs -->",	// Page generation time output format string
	"Cache.enabled"		    	=> true,
	"Cache.expires"		    	=> "+10 seconds",
	"Asset.compress"	    	=> false,
	"Asset.timestamp"	    	=> true
));

/*
 * Set the current environment to "development"
 */

Environment::set("development");

/*
 * Inflector configuration example
 */
// Inflector::add("plural", array(
//     '/(s)tatus$/i' => '\1\2tatuses', '/^(ox)$/i' => '\1\2en', '/([m|l])ouse$/i' => '\1ice'
// ));
// Inflector::add("uninflectedPlural", array(
//     '.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox'
// ));
// Inflector::add("irregularPlural", array(
//     'atlas' => 'atlases', 'beef' => 'beefs', 'brother' => 'brothers'
// ));
// Inflector::add("singular", array(
//     '/(s)tatuses$/i' => '\1\2tatus', '/(matr)ices$/i' =>'\1ix','/(vert|ind)ices$/i'
// ));

/*
 * Paths configuration example
 */
// Libraries::addPluginPath("/path/to/more/plugins");

?>