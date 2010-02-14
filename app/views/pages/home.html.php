<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
?>
<h2><?php echo $this->title('Home');?></h2>
<p>
	Temporary home page that will eventually be filled with configuration checks.
</p>
<?php
	$path = LITHIUM_APP_PATH . '/resources';
	if (!is_writable($path)) {
		echo "<h4>here is a check for you</h4>";
		echo "<pre style='color:red'>"
			. "{$path} is not writable.\n"
			. "chmod -R 0777 {$path}\n"
		. "</pre>";
	}
?>
<h4>more info</h4>
<ul>
	<li><a href="http://rad-dev.org/lithium/wiki">Lithium Wiki</a></li>
	<li><a href="http://rad-dev.org/lithium">Lithium Source</a></li>
	<li><a href="irc://irc.freenode.net/#li3">#li3 irc channel</a></li>
</ul>