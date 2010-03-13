{:heading2}Code Coverage{:end}
<?php
	foreach ($data as $class => $coverage) {
		echo ($coverage['percentage'] >= 85 ? "{:success}" : "{:error}");
		echo "{$class}{:end}: ";
		echo count($coverage['covered']) . " of " . count($coverage['executable']);
		echo " lines covered (";
		echo ($coverage['percentage'] >= 85 ? "{:success}" : "{:error}");
		echo "{$coverage['percentage']}%{:end})\n";
	}
?>