<?php
	$depth = 2;
	$prev = array();
	$current = null;
?>
<ul><li><a href="<?= $base ?>/test/lithium/tests">Run All Tests</a></li>
<?php foreach ($menu as $test): ?>
	<?php
		$case = array_pop($path = explode("\\", $test));
		$caseDepth = count($path);
	?>

	<?php if (!isset($current)): ?>
		<ul>
	<?php endif ?>

	<?php while ($depth >= $caseDepth): ?>
		</li></ul>
		<?php
			$depth--;
			$current = array_pop($prev);
		?>
	<?php endwhile ?>

	<?php while(isset($current) && $current != $path[$depth-1]): ?>
		</li></ul>
		<?php
			$current = array_pop($prev);
			$depth--;
		?>
	<?php endwhile ?>

	<?php while ($depth < $caseDepth-1): ?>
		<li>
			<a href="<?= $base ?>/test/<?= join(array_slice($path, 0, $depth+1), "\\") ?>">
				<?= $path[$depth] ?>
			</a>
			<ul>
		<?php
			array_push($prev, $current);
			$current = $path[$depth];
			$depth++;
		?>
	<?php endwhile ?>

	<li><a href="<?= $base ?>/test/<?= join($path, "\\") ?>"><?= $case ?></a></li>

<?php endforeach ?>

<?php while($depth > 0): ?>
	</li></ul>
	<?php $depth--; ?>
<?php endwhile ?>