<?php
/**
 * Server-side rendering for the Global Store Flash Sale Header block.
 *
 * All markup is produced here so the block is fully readable without JavaScript;
 * `view.js` only keeps the countdown digits ticking once the page has loaded.
 *
 * @package GlobalStore\FlashSaleHeader
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

use GlobalStore\FlashSaleHeader\Attributes;
use GlobalStore\FlashSaleHeader\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( Renderer::class ) || ! class_exists( Attributes::class ) ) {
	return;
}

$gsfsh_attributes = Attributes::sanitize( is_array( $attributes ) ? $attributes : array() );

$gsfsh_wrapper_args = array(
	'class' => implode( ' ', Renderer::wrapper_classes( $gsfsh_attributes ) ),
);

$gsfsh_styles = Renderer::wrapper_styles( $gsfsh_attributes );

if ( '' !== $gsfsh_styles ) {
	$gsfsh_wrapper_args['style'] = $gsfsh_styles;
}

$gsfsh_markup = Renderer::render(
	$gsfsh_attributes,
	get_block_wrapper_attributes( $gsfsh_wrapper_args )
);

// The renderer escapes every attribute and text node it emits.
echo $gsfsh_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
