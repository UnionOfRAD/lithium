<h3>Benchmarks</h3>
<table class="metrics"><tbody>

<?php foreach ($analysis['totals'] as $title => $data): ?>
	<tr>
		<td class="metric-name"><?= $title ?></td>
		<td class="metric"><?= $data['formatter']($data['value']) ?></td>
	</tr>
<?php endforeach ?>
</tbody></table>