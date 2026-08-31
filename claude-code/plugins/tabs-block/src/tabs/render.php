<?php
/**
 * Server-side rendering for the Tabs block.
 *
 * `$content` already holds the fully rendered Tab panels (each Tab block
 * renders its own wrapper via src/tab/render.php). The nav row (icon +
 * title per tab) is built here by reading each child Tab block's saved
 * attributes straight from the parsed block tree, since that data lives
 * on the child blocks, not on this one.
 *
 * All attribute values are untrusted user input (entered in the block
 * editor and stored in post content) and MUST be sanitized/escaped before
 * being echoed here.
 *
 * @package GlobalStore\Tabs
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (the tab panels).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$parsed_inner_blocks = isset( $block->parsed_block['innerBlocks'] ) && is_array( $block->parsed_block['innerBlocks'] )
	? $block->parsed_block['innerBlocks']
	: array();

$gst_tabs = array();

foreach ( $parsed_inner_blocks as $inner_block ) {
	if ( ! isset( $inner_block['blockName'] ) || 'global-store/tab' !== $inner_block['blockName'] ) {
		continue;
	}

	$attrs = isset( $inner_block['attrs'] ) && is_array( $inner_block['attrs'] ) ? $inner_block['attrs'] : array();

	$gst_tabs[] = array(
		'tab_id'    => isset( $attrs['tabId'] ) ? sanitize_html_class( $attrs['tabId'] ) : '',
		'title'     => isset( $attrs['title'] ) ? wp_strip_all_tags( $attrs['title'] ) : '',
		'icon_type' => isset( $attrs['iconType'] ) ? sanitize_key( $attrs['iconType'] ) : 'none',
		'dashicon'  => isset( $attrs['dashicon'] ) ? sanitize_html_class( $attrs['dashicon'] ) : '',
		'image_url' => isset( $attrs['imageUrl'] ) ? esc_url_raw( $attrs['imageUrl'] ) : '',
		'image_alt' => isset( $attrs['imageAlt'] ) ? sanitize_text_field( $attrs['imageAlt'] ) : '',
	);
}

if ( ! empty( $gst_tabs ) ) {
	// Dashicons is only enqueued in wp-admin by default; the nav icons need
	// it on the frontend too.
	wp_enqueue_style( 'dashicons' );
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'tabs-block' ) );
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() output is pre-escaped. ?>>
	<?php if ( ! empty( $gst_tabs ) ) : ?>
		<div class="tabs-block__nav" role="tablist">
			<?php foreach ( $gst_tabs as $index => $gst_tab ) : ?>
				<?php
				$gst_tab_id  = '' !== $gst_tab['tab_id'] ? $gst_tab['tab_id'] : 'tab-' . $index;
				$is_selected = 0 === $index;
				?>
				<button
					type="button"
					role="tab"
					class="tabs-block__tab-btn<?php echo $is_selected ? ' is-active' : ''; ?>"
					id="tabs-block-tab-<?php echo esc_attr( $gst_tab_id ); ?>"
					aria-selected="<?php echo $is_selected ? 'true' : 'false'; ?>"
					aria-controls="tabs-block-panel-<?php echo esc_attr( $gst_tab_id ); ?>"
					tabindex="<?php echo $is_selected ? '0' : '-1'; ?>"
				>
					<?php if ( 'dashicon' === $gst_tab['icon_type'] && $gst_tab['dashicon'] ) : ?>
						<span class="tabs-block__tab-icon dashicons <?php echo esc_attr( $gst_tab['dashicon'] ); ?>" aria-hidden="true"></span>
					<?php elseif ( 'image' === $gst_tab['icon_type'] && $gst_tab['image_url'] ) : ?>
						<img
							class="tabs-block__tab-icon tabs-block__tab-icon--image"
							src="<?php echo esc_url( $gst_tab['image_url'] ); ?>"
							alt="<?php echo esc_attr( $gst_tab['image_alt'] ); ?>"
							loading="lazy"
						/>
					<?php endif; ?>
					<span class="tabs-block__tab-label"><?php echo esc_html( $gst_tab['title'] ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="tabs-block__panels">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $content is WordPress-rendered inner block markup (each Tab panel), already escaped by src/tab/render.php. ?>
	</div>
</div>
