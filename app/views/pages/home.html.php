<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\data\Connections;

$checkName = null;
$checkStatus = array();

$notify = function($status, $message) use (&$checkName, &$checkStatus) {
	$checkStatus[$checkName] = $status;
	$status = $status ? 'success' : 'fail';
	$message = is_array($message) ? join("\n<br />", $message) : $message;
	return "<div class=\"test-result test-result-{$status}\">{$message}</div>";
};

$sanityChecks = array(
	'resourcesWritable' => function() use ($notify) {
		if (is_writable($path = LITHIUM_APP_PATH . '/resources')) {
			return $notify(true, 'Your application\'s resources directory is writable.');
		}
		return $notify(false, array(
			"Your resource path (<code>$path</code>) is not writeable. " .
			"To fix this on *nix and Mac OSX, run the following from the command line:",
			"<code>chmod -R 0777 {$path}</code>"
		));
	},
	'database' => function() use ($notify) {
		if (!$config = Connections::get('default')) {
			return $notify(false, array(
				'No default database connection defined. To create a database connection, ' .
				'edit the file <code>' . LITHIUM_APP_PATH . '/config/bootstrap.php</code>, and ' .
				'uncomment the following line:',
				'<code>require __DIR__ . \'/connections.php\';</code>',
				'Then, edit the file <code>' . LITHIUM_APP_PATH . '/config/connections.php</code>.'
			));
		}
		return $notify(true, 'Default database connection configured.');
	},
	'databaseConnected' => function() use ($notify, &$checkStatus) {
		if (!$checkStatus['database']) {
			return;
		}
		if (@Connections::get('default')->connect()) {
			return $notify(true, 'Connection to default database verified.');
		}
		return $notify(false, array(
			'Could not connect to default database. Please check the ' .
			'settings in <code>' . LITHIUM_APP_PATH . '/config/connections.php</code>.'
		));
	},
	'magicQuotes' => function() use ($notify) {
		if (get_magic_quotes_gpc() === 0) {
			return;
		}
		return $notify(false, array(
			"Magic quotes are enabled in your PHP configuration. Please set <code>" .
			"magic_quotes_gpc = Off</code> in your <code>php.ini</code> settings."
		));
	},
	'registerGlobals' => function() use ($notify) {
		if (!ini_get('register_globals')) {
			return;
		}
		return $notify(false, array(
			'Register globals is enabled in your PHP configuration. Please set <code>' .
			'register_globals = Off</code> in your <code>php.ini</code> settings.'
		));
	}
);

?>
<h3><?php echo $this->title('home'); ?></h3>

<p>
	This is your application's default home page. To change this template, edit the file
	<code><?php echo LITHIUM_APP_PATH . '/views/pages/home.html.php'; ?></code>.
</p>

<p>
	To change the application's <em>layout</em> (the file containing the
	header, footer and default styles), edit the file
	<code><?php echo LITHIUM_APP_PATH . '/views/layouts/default.html.php'; ?></code>.
</p>

<p>
	To change the <em><a href="http://lithify.me/docs/lithium/net/http/Router">routing</a></em> of
	the application's default page, edit the file
	<code><?php echo LITHIUM_APP_PATH . '/config/routes.php'; ?></code>.
</p>

<h3>system check</h3>

<?php

foreach ($sanityChecks as $checkName => $check) {
	echo $check();
}

?>

<h4>additional resources</h4>
<ul>
	<li><a href="http://lithify.me/docs/lithium">Lithium API</a></li>
	<li><a href="http://rad-dev.org/lithium/wiki">Lithium Development Wiki</a></li>
	<li><a href="http://rad-dev.org/lithium">Lithium Source</a></li>
	<li><a href="irc://irc.freenode.net/#li3">#li3 irc channel</a></li>
</ul>