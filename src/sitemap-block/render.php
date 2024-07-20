<?php

/**
 * Load the Post Types we're doing to show
 * We'll loop through them, drawing a column for each set of links.
 */
$postTypes = $attributes['postTypes'];
if (!$postTypes || !is_array($postTypes) || empty($postTypes)) {
	$postTypes = array('post', 'page');
}

$block_wrapper_attributes = get_block_wrapper_attributes();

$showHeadings = (isset($attributes['showHeadings']) && $attributes['showHeadings']) ? true : false;




?>

<div <?php echo $block_wrapper_attributes; ?>>
	<?php
	if (count($postTypes) > 1) {
	?>
	<div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex">
		<?php
		foreach ($postTypes as $type) {
			?>
			<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">
				<?php
				SimpleSitemap::outputPostType($type, $showHeadings);
				?>
			</div>
			<?php
		}
		?>
	<?php
	} else {
		foreach ($postTypes as $type) {
			SimpleSitemap::outputPostType($type, $showHeadings);
		}
	}
	?>
	</div>
</div>
