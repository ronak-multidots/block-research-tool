<?php
/**
 * Server-side rendering for a single Tab panel.
 *
 * The tab's nav button (icon + title) is rendered by the parent Tabs
 * block, which reads this block's saved attributes directly from the
 * parsed block tree. This file only renders the panel that wraps the
 * tab's arbitrary inner blocks.
 *
 * All attribute values are untrusted user input (entered in the block
 * editor and stored in post content) and MUST be sanitized/escaped before
 * being echoed here.
 *
 * @package GlobalStore\Tabs
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the tab's arbitrary content).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$tab_id = isset( $attributes['tabId'] ) ? sanitize_html_class( $attributes['tabId'] ) : '';

if ( '' === $tab_id ) {
	$tab_id = 'tab-' . wp_unique_id();
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'           => 'tabs-block__panel',
		'id'              => 'tabs-block-panel-' . $tab_id,
		'role'            => 'tabpanel',
		'aria-labelledby' => 'tabs-block-tab-' . $tab_id,
		'tabindex'        => '0',
	)
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() output is pre-escaped. ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $content is WordPress-rendered inner block markup, already escaped by each inner block's own render. ?>
</div>
