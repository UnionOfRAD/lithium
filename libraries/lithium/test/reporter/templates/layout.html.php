<!doctype html>
<html>
	<head>
		<title>Lithium Unit Test Dashboard</title>
		<link rel="stylesheet" href="css/debug.css" />
	</head>
	<body class="test-dashboard">
		<h1>Lithium Unit Test Dashboard</h1>

		<div style="float: left; padding: 10px 0 20px 20px; width: 20%;">
			<h2><a href="?group=\">Tests</a></h2>
			<?php echo $menu ?>
		</div>

		<div style="float:left; padding: 10px; width: 75%">
			<h2>Stats for <?php echo $title; ?></h2>

			<h3>Test results</h3>

			<span class="filters">
				<?php
					$base = $_SERVER['REQUEST_URI'];

					foreach ($filters as $i => $class) {
						$url = $base . "&amp;filters[]={$class}";
						$name = join('', array_slice(explode("\\", $class), -1));
						$key = Inflector::underscore($name);

						echo "<a class=\"{$key}\" href=\"{$url}\">{$name}</a>";

						if ($i < count($filters) - 1) {
							echo ' | ';
						}
					}
				?>
			</span>
		</div>
		<div style="clear:both"></div>
	</body>
</html>