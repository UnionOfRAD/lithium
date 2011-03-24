<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\core\Libraries;
use lithium\data\Connections;

$this->title('Home');

$self = $this;

$notify = function($status, $message, $solution = null) {
	$html  = "<div class=\"test-result test-result-{$status}\">{$message}</div>";
	$html .= "<div class=\"test-result solution\">{$solution}</div>";
	return $html;
};

$checks = array(
	'home' => function() use ($notify) {
		$file = realpath(LITHIUM_APP_PATH . '/views/pages/home.html.php');

		return $notify(
			'notice',
			"You're using the application's default home page.",
			"To change this template, edit the file
			<code>{$file}</code>."
		);
	},
	'layout' => function() use ($notify, $self) {
		$file = realpath(LITHIUM_APP_PATH . '/views/layouts/default.html.php');
		$link = $self->html->link('layout', 'http://lithify.me/docs/lithium/template');

		return $notify(
			'notice',
			'Change the default layout.',
			"To change the {$link},
			this is the file wrapping your content as well as containing header and footer,
			edit the file <code>{$file}</code>."
		);
	},
	'routing' => function() use ($notify, $self) {
		$file = realpath(LITHIUM_APP_PATH . '/config/routes.php');
		$link = $self->html->link('routing', 'http://lithify.me/docs/lithium/net/http/Router');

		return $notify(
			'notice',
			'Use custom routing.',
			"To change the {$link} edit the file <code>{$file}</code>."
		);
	},
	'resourcesWritable' => function() use ($notify) {
		if (is_writable($path = realpath(Libraries::get(true, 'resources')))) {
			return $notify('success', 'Resources directory is writable.');
		}
		return $notify(
			'fail',
			'Your resource path is not writeable.',
			"To fix this on *nix and Mac OSX, run the following from the command line:
			<code>$ chmod -R 0777 {$path}</code>"
		);
	},
	'database' => function() use ($notify) {
		$config = Connections::config();
		$boot = realpath(LITHIUM_APP_PATH . '/config/bootstrap.php');
		$connections = realpath(LITHIUM_APP_PATH . '/config/bootstrap/connections.php');

		if (!empty($config)) {
			return $notify('success', 'Database connection/s configured.');
		}
		return $notify(
			'notice',
			'No database connection defined.',
			"To create a database connection:
			<ol>
				<li>Edit the file <code>{$boot}</code>.</li>
				<li>
					Uncomment the line having
					<code>require __DIR__ . '/bootstrap/connections.php';</code>.
				</li>
				<li>Edit the file <code>{$connections}</code>.</li>
			</ol>"
		);
	},
	'magicQuotes' => function() use ($notify) {
		if (get_magic_quotes_gpc() === 0) {
			return;
		}
		return $notify(
			'fail',
			'Magic quotes are enabled in your PHP configuration.',
			'Please set <code>magic_quotes_gpc = Off</code> in your <code>php.ini</code> settings.'
		);
	},
	'registerGlobals' => function() use ($notify) {
		if (!ini_get('register_globals')) {
			return;
		}
		return $notify(
			'fail',
			'Register globals is enabled in your PHP configuration.',
			'Please set <code>register_globals = Off</code> in your <code>php.ini</code> settings.'
		);
	},
);

?>

<h3>Getting Started</h3>
<?php foreach ($checks as $check): ?>
	<?php echo $check(); ?>
<?php endforeach; ?>

<h3>Additional Resources</h3>
<ul>
	<li><?php echo $this->html->link('Lithium API', 'http://lithify.me/docs/lithium'); ?></li>
	<li><?php echo $this->html->link('Lithium Development Wiki', 'http://dev.lithify.me/lithium/wiki'); ?></li>
	<li><?php echo $this->html->link('#li3 IRC channel', 'irc://irc.freenode.net/#li3'); ?></li>
	<li><?php echo $this->html->link('Lithium Community', 'http://sphere.lithify.me'); ?></li>
	<li><?php echo $this->html->link('Lithium Source', 'http://dev.lithify.me/lithium/source'); ?></li>
</ul>