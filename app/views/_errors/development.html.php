<?php

use lithium\analysis\Inspector;

$exception = $info['exception'];

/*
 * Set Lithium-esque colors for syntax highlighing.
 * Do these belong here? Maybe they should move to the layout or error bootstrap?
 */
ini_set('highlight.string', '#4DDB4A');
ini_set('highlight.comment', '#D42AAE');
ini_set('highlight.keyword', '#D42AAE');
ini_set('highlight.default', '#3C96FF');
ini_set('highlight.htm', '#FFFFFF');

/**
 * Helper function for generating line numbered code output with syntax highlighting.
 *
 * Should this function belong in a helper or something?
 *
 * @param string $file Normally a full path to a specific file
 * @param integer $line The central line number to retrieve
 * @see lithium\analysis\Inspector::lines()
 */
function renderCodeExcerpt($file = null, $line = null) {

	$sourceCode = Inspector::lines($file, range($line - 3, $line + 3));

	?>

	<div class="lithium-exception-location">
		<?php echo "{$file}: {$line}"; ?>
	</div>

	<div class="lithium-code-dump">

		<?php
		/**
		 * We can get rid of the 'code' HTML elements if we prefer a lighter background
		 * color. Also, it bothers me that the syntax highlighting isn't an exact match
		 * with li3_docs. The highlight color can make the code a little hard to read.
		 * If we stick with the dark background color, we might want to adjust the
		 * highlight color.
		 */
		?>

		<pre><code><?php

			foreach ($sourceCode as $num => $content) :

				// Pad the line number.
				// We shouldn't have any files longer than 999 lines, right? ;)
				$numPad = str_pad($num, 3, ' ');

				// Nuke any existing PHP start or end tags
				$content = str_ireplace(array('<?php', '?>'), '', $content);

				// Add the line number and wrap in PHP start and end tags
				$content = "<?php {$numPad}{$content} ?>";

				// Apply syntax highlighting
				$content = highlight_string($content, true);

				// Clean out PHP start and end tags, 'code' elements, and newlines
				$content = str_replace(array('&lt;?php', '?&gt;', '<code>', '</code>', "\n"), '', $content);

				if ($line === $num):
					?><span class="code-highlight"><?php
				endif;?><?echo "{$content}\n"; ?><?php

				if ($line === $num):
					?></span><?php
				endif;

			endforeach;

		?></code></pre>

	</div><?php

}

?>

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
<?php renderCodeExcerpt($exception->getFile(), $exception->getLine()); ?>

<?php
/**
 * Eventually these code snippets will be hidden by default. Clicking on the title
 * will swap the source code fragment in the main source code area. Maybe we'll add
 * a 'Show All' link or button.
 *
 * Also we plan to improve the way that closures are displayed in the stack trace.
 */
?>
<h3>Stack Trace</h3>
<div>
	<ol>
		<?php foreach ($exception->getTrace() as $id => $frame) :
			$title = null;
			// Borrowed this snippet from `\lithium\core\ErrorHandler::trace()`.
			if (isset($frame['function'])) {
				if (isset($frame['class'])) {
					$title = trim($frame['class'], '\\') . '::' . $frame['function'];
				} else {
					$title = $frame['function'];
				}
			}
			?>
			<li>
				<tt><?=$title;?>()</tt>
				<?php renderCodeExcerpt($frame['file'], $frame['line']); ?>
			</li>
		<?php endforeach; ?>
	</ol>
</div>