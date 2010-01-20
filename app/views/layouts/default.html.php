<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
?>
<!doctype html>
<html>
<head>
	<?=$this->html->charset();?>
	<title>Application > <?=$this->title?></title>
	<?=$this->html->style('base');?>
	<?=$this->scripts();?>
	<?=$this->html->link('Icon', null, array('type' => 'icon'));?>
</head>
<body class="app">
	<div id="container">
		<div id="header">
			<h1>Application</h1>
			<h2>Powered by <?=$this->html->link('Lithium', 'http://li3.rad-dev.org');?>.</h2>
		</div>
		<div id="content">
			<?=$this->content;?>
		</div>
	</div>
</body>
</html>