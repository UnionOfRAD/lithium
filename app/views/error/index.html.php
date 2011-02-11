<?php
/**
 * It would be cool if the name of the exception was a link to the API.
 * Also, should we show the exception code? That might be helpful for HTTP exceptions.
 */
?>
<h3>Exception</h3>
<div class="lithium-exception-class"><?=get_class($exception);?></div>
<div class="lithium-exception-message"><?=$exception->getMessage();?></div>

<h3>Source</h3>
<div class="lithium-exception-location"><?=$exception->getFile();?>:<?=$exception->getLine();?></div>
<div class="lithium-code-dump">
	<?php
	/**
	 * I'm not sure if it's preferable to echo HTML elements like this.
	 */
	echo '<pre>';
	foreach ($sourceCode as $lineNumber => $content) {
		/**
		 * There must be a better wrap with the span element, I'm just not sure how.
		 */
		if ($exception->getLine() === $lineNumber) {
			echo '<span class="code-highlight">';
		}
		echo $lineNumber < 10 ? ' ' . $lineNumber : $lineNumber;
		echo '. ';
		echo $content;
		echo "\n";
		if ($exception->getLine() === $lineNumber) {
			echo '</span>';
		}
	}
	echo '</pre>';
	?>
</div>

<?php
/**
 * I think it would be cool if each row of the stack trace could be clicked on
 * to expand a div with the source code for that item. But I wasn't sure if we
 * wanted to introduce a JavaScript dependency.
 */
?>
<h3>Stack Trace</h3>
<div class="lithium-stack-trace">
	<ol>
		<?php foreach ($stackTrace as $trace) : ?>
			<li><?=$trace;?></li>
		<?php endforeach; ?>
	</ol>
</div>