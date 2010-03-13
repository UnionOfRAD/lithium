<h3>Affected Tests</h3>
<ul class="metrics">

<?php foreach ($data as $class => $test): ?>
	<?php if ($test): ?>
		<li><?php echo $test ?></li>
	<?php endif ?>
<?php endforeach ?>

</ul>