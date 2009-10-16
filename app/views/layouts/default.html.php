<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
?>
<!doctype html>
<html>
<head>
	<?=@$this->html->charset(); ?>
	<title><?=$title; ?></title>
	<?=@$this->html->style('base'); ?>
	<?=@$this->scripts(); ?>
	<?=@$this->html->link('Icon', null, array('type' => 'icon')); ?>
</head>
<body>
	<div id="container">
		<div id="header"></div>
		<div id="content">
			<?=@$this->content; ?>
		</div>
		<div id="footer"></div>
	</div>
</body>
</html>
