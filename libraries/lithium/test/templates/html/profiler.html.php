<h3>Benchmarks</h3>
<table class="metrics"><tbody>

<?php foreach ($analysis['totals'] as $title => $data): ?>
	<tr>
		<td class="metric-name"><?php echo $title ?></td>
		<td class="metric"><?php echo $data['formatter']($data['value']) ?></td>
	</tr>
<?php endforeach ?>
</tbody></table>