Code Coverage
<?php

	$summary = array(
		'classes' => 0, 'executable' => 0, 'covered' => 0, 'uncovered' => 0, 'percentage' => 0
	);

	foreach ($data as $class => $coverage) {
		$summary['classes']++;
		$summary['executable'] += count($coverage['executable']);
		$summary['covered'] += count($coverage['covered']);
		$summary['uncovered'] += count($coverage['uncovered']);
		$summary['percentage'] += $coverage['percentage'];

		echo "{$class}: ";
		echo count($coverage['covered']) . " of " . count($coverage['executable']);
		echo " lines covered (";
		echo "{$coverage['percentage']}%)\n";

		if ($coverage['percentage'] == 100) {
			continue;
		}
		echo "\nCoverage analysis\n";

		foreach ($coverage['output'] as $file => $lines) {
			echo "\n{$file}:\n";

			foreach ($lines as $num => $line) {
				$color = $colorMap[$line['class']];
				echo "{$num} {$line['data']}\n";
			}
		}
		echo "\n";
	}

$displayPercentage = function($raw) {
    $percentage = round($raw);
    echo $percentage;
};
?>

Summary

Classes Covered:   <?php echo $summary['classes'] ?>

Executable Lines:  <?php echo $summary['executable'] ?>

Lines Covered:     <?php echo $summary['covered'] ?>

Lines Uncovered:   <?php echo $summary['uncovered'] ?>

Total Coverage:    <?php
	$percTotal = ($summary['covered'] / $summary['executable']) * 100;
	$displayPercentage($percTotal);
	?>%
Average Per Class: <?php
	$percPerClass = $summary['percentage'] / $summary['classes'];
	$displayPercentage($percPerClass);
	?>%
