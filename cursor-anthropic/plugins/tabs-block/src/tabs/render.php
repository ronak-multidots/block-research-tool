<?php
/**
 * Server-side rendering for the Global Store Tabs block.
 *
 * Every panel is rendered into the page, with the inactive ones hidden, so the content
 * stays readable, crawlable and printable without JavaScript. `view.js` only takes over
 * the switching once the page has loaded.
 *
 * @package GlobalStore\TabsBlock
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

use GlobalStore\TabsBlock\Attributes;
use GlobalStore\TabsBlock\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( Renderer::class ) || ! class_exists( Attributes::class ) ) {
	return;
}

$gstb_tabs = Renderer::collect_tabs( $block instanceof WP_Block ? $block : null );

if ( empty( $gstb_tabs ) ) {
	return;
}

$gstb_attributes = Attributes::sanitize( is_array( $attributes ) ? $attributes : array() );

$gstb_wrapper_args = array(
	'class' => implode( ' ', Renderer::wrapper_classes( $gstb_attributes ) ),
);

$gstb_styles = Renderer::wrapper_styles( $gstb_attributes );

if ( '' !== $gstb_styles ) {
	$gstb_wrapper_args['style'] = $gstb_styles;
}

$gstb_markup = Renderer::render(
	$gstb_attributes,
	$gstb_tabs,
	get_block_wrapper_attributes( $gstb_wrapper_args )
);

// The renderer escapes every attribute and text node it emits; panel content has already
// been rendered by WordPress.
echo $gstb_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
