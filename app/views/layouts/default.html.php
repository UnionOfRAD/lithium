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
	<?php echo $this->html->charset(); ?>
	<title><?=$title; ?></title>
	<?php echo $this->html->style('base'); ?>
	<?php echo $this->scripts(); ?>
	<?php echo $this->html->link('Icon', null, array('type' => 'icon')); ?>
</head>
<body>
	<div id="container">
		<div id="header"></div>
		<div id="content">
			<?php echo $this->content; ?>
		</div>
		<div id="footer"></div>
	</div>
</body>
</html>
