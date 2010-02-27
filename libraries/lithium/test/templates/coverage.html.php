<?php foreach ($analysis as $class => $coverage): ?>
	<h4 class="coverage">
		<?php echo $class ?>:
		<?php echo count($coverage['covered']) ?> of <?php echo count($coverage['executable']) ?>
		lines covered (<em><?php echo $coverage['percentage'] ?>%</em>)
	</h4>
	<?php foreach ($coverage['output'] as $file => $data): ?>
		<?php if (!empty($data)): ?>
			<div class="code-coverage-results">
				<?php foreach ($data as $line => $row): ?>
					<div class="code-line <?php echo $row['class'] ?>">
						<span class="line-num"><?php echo $line ?></span>
						<span class="content"><?php
							echo htmlspecialchars(str_replace("\t", "	", $row['data']))
						?></span>
					</div><!-- code-line -->
				<?php endforeach ?>
			</div>
			<h4 class="code-coverage-name"><?php echo $file ?></h4>
			<!-- code-coverage-results -->
		<?php endif ?>
	<?php endforeach ?>
<?php endforeach ?>