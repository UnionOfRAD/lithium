<?php foreach ($analysis as $class => $coverage): ?>
	<h4 class="coverage">
		<?= $class ?>:
		<?= count($coverage['covered']) ?> of <?= count($coverage['executable']) ?>
		lines covered (<em><?= $coverage['percentage'] ?>%</em>)
	</h4>
	<?php foreach ($coverage['output'] as $file => $data): ?>
		<?php if (!empty($data)): ?>
			<div class="code-coverage-results">
				<h4 class="name"><?= $file ?></h4>
				<?php foreach ($data as $line => $row): ?>
					<div class="code-line <?= $row['class'] ?>">
						<span class="line-num"><?= $line ?></span>
						<span class="content"><?=
							htmlspecialchars(str_replace("\t", "	", $row['data']))
						?></span>
					</div><!-- code-line -->
				<?php endforeach ?>
			</div><!-- code-coverage-results -->
		<?php endif ?>
	<?php endforeach ?>
<?php endforeach ?>