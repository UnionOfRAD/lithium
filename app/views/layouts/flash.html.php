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

use \lithium\core\Environment;

?>
<!doctype html>
<html>
<head>
	<?php echo $this->html->charset(); ?>
	<title><?php echo $page_title; ?></title>
	<?php if (Environment::is('production')) { ?>
		<meta http-equiv="Refresh" content="<?=$pause?>;url=<?=$url?>"/>
	<?php } ?>
	<style>
		p { text-align:center; font:bold 1.1em sans-serif }
		a { color:#444; text-decoration: none }
		a:hover { text-decoration: underline; color: #44E }
	</style>
</head>
<body>
	<p><a href="<?=$url; ?>"><?=$message; ?></a></p>
</body>
</html>