<?php
/**
 * Server-side rendering for the Global Store Flash Sale Header block.
 *
 * All attribute values are untrusted user input (entered in the block
 * editor and stored in post content) and MUST be sanitized/escaped before
 * being echoed here.
 *
 * @package GlobalStore\FlashSaleHeader
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Default block content (unused; this is a dynamic block).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$allowed_sizes = array( 'wide', 'medium', 'tall' );
$size          = isset( $attributes['size'] ) && in_array( $attributes['size'], $allowed_sizes, true )
	? $attributes['size']
	: 'wide';

$block_title = isset( $attributes['title'] ) ? wp_kses_post( $attributes['title'] ) : '';
$subtitle    = isset( $attributes['subtitle'] ) ? wp_kses_post( $attributes['subtitle'] ) : '';
$legal_text  = isset( $attributes['legalText'] ) ? wp_kses_post( $attributes['legalText'] ) : '';

$cta_text = isset( $attributes['ctaText'] ) ? sanitize_text_field( $attributes['ctaText'] ) : '';
$cta_url  = isset( $attributes['ctaUrl'] ) ? esc_url_raw( $attributes['ctaUrl'] ) : '';
$show_cta = 'wide' !== $size && '' !== $cta_text;

$image_url = isset( $attributes['imageUrl'] ) ? esc_url_raw( $attributes['imageUrl'] ) : '';
$image_alt = isset( $attributes['imageAlt'] ) ? sanitize_text_field( $attributes['imageAlt'] ) : '';

$expiry_raw       = isset( $attributes['expiryDateTime'] ) ? sanitize_text_field( $attributes['expiryDateTime'] ) : '';
$expiry_timestamp = $expiry_raw ? strtotime( $expiry_raw ) : false;
$has_countdown    = false !== $expiry_timestamp;

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'flash-sale-header is-size-' . $size,
	)
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() output is pre-escaped. ?>>
	<div class="flash-sale-header__media">
		<?php if ( $image_url ) : ?>
			<img
				class="flash-sale-header__image"
				src="<?php echo esc_url( $image_url ); ?>"
				alt="<?php echo esc_attr( $image_alt ); ?>"
				loading="lazy"
			/>
		<?php endif; ?>
	</div>

	<div class="flash-sale-header__content">
		<?php if ( $block_title ) : ?>
			<h2 class="flash-sale-header__title"><?php echo $block_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized above with wp_kses_post(). ?></h2>
		<?php endif; ?>

		<?php if ( $subtitle ) : ?>
			<p class="flash-sale-header__subtitle"><?php echo $subtitle; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized above with wp_kses_post(). ?></p>
		<?php endif; ?>

		<div
			class="flash-sale-header__countdown"
			<?php if ( $has_countdown ) : ?>
				data-flash-sale-countdown
				data-expiry="<?php echo esc_attr( gmdate( 'c', $expiry_timestamp ) ); ?>"
			<?php endif; ?>
		>
			<?php if ( $has_countdown ) : ?>
				<div class="flash-sale-header__countdown-unit" data-unit="days">
					<span class="flash-sale-header__countdown-value">00</span>
					<span class="flash-sale-header__countdown-label"><?php esc_html_e( 'Days', 'global-store' ); ?></span>
				</div>
				<div class="flash-sale-header__countdown-unit" data-unit="hours">
					<span class="flash-sale-header__countdown-value">00</span>
					<span class="flash-sale-header__countdown-label"><?php esc_html_e( 'Hrs', 'global-store' ); ?></span>
				</div>
				<div class="flash-sale-header__countdown-unit" data-unit="minutes">
					<span class="flash-sale-header__countdown-value">00</span>
					<span class="flash-sale-header__countdown-label"><?php esc_html_e( 'Mins', 'global-store' ); ?></span>
				</div>
				<div class="flash-sale-header__countdown-unit" data-unit="seconds">
					<span class="flash-sale-header__countdown-value">00</span>
					<span class="flash-sale-header__countdown-label"><?php esc_html_e( 'Secs', 'global-store' ); ?></span>
				</div>
			<?php else : ?>
				<p class="flash-sale-header__countdown-placeholder">
					<?php esc_html_e( 'Set an expiry date to show a live countdown.', 'global-store' ); ?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( $show_cta ) : ?>
			<a
				class="flash-sale-header__cta wp-block-button__link"
				href="<?php echo esc_url( $cta_url ? $cta_url : '#' ); ?>"
			>
				<?php echo esc_html( $cta_text ); ?>
			</a>
		<?php endif; ?>

		<?php if ( $legal_text ) : ?>
			<p class="flash-sale-header__legal"><?php echo $legal_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized above with wp_kses_post(). ?></p>
		<?php endif; ?>
	</div>
</div>
