<?php
	use \lithium\util\Inflector;
	$base = $request->env('base');
?>
<!doctype html>
<html>
	<head>
		<title></title>
		<link rel="stylesheet" href="<?php echo $base;?>/css/debug.css" />
		<link href="<?php echo $base;?>/favicon.ico" title="Icon" type="image/x-icon" rel="icon" />
		<link href="<?php echo $base;?>/favicon.ico" title="Icon" type="image/x-icon" rel="shortcut icon" /></head>
	</head>
	<body class="test-dashboard">
		<h1>Lithium Unit Test Dashboard</h1>

		<div style="float: left; padding: 10px 0 20px 20px; width: 20%;">
			<h2><a href="/test/">Tests</a></h2>
			<?php echo $menu ?>
		</div>

		<div style="float:left; padding: 10px; width: 75%">
			<h2>Stats for <?php echo $report->title; ?></h2>

			<h3>Test results</h3>

			<span class="filters">
				<?php echo join(' | ', array_map(
					function($class) use ($request) {
						$url = "?filters[]={$class}";
						$name = join('', array_slice(explode("\\", $class), -1));
						$key = Inflector::underscore($name);
						return "<a class=\"{$key}\" href=\"{$url}\">{$name}</a>";
					},
					$filters
				)); ?>
			</span>
			<?php
				echo $report->stats();
				echo $report->filters();
			?>
		</div>
		<div style="clear:both"></div>
	</body>
</html>