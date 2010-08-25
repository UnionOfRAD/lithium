{:heading}Benchmarks{:end}
<?php
	foreach ($data['totals'] as $title => $result) {
		echo "{$title}: {$result['formatter']($result['value'])}\n";
	}
?>