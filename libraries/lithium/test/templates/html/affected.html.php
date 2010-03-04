<h3>Additional Affected Tests</h3>
<ul class="metrics">

<?php foreach ($analysis as $class => $test): ?>
	<?php if ($test): ?>
		<li><?php echo $test ?></li>
	<?php endif ?>
<?php endforeach ?>

</ul>