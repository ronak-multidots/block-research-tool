<?php
/**
 * Server-side rendering for a single tab panel.
 *
 * Inside a tabs block this file is never reached: the container renders its panels
 * itself so it can pair each one with its button in the tab list. This is the fallback
 * for a tab that has been separated from its container, for example by an editing
 * mistake or a partially copied pattern, and it keeps the content visible.
 *
 * @package GlobalStore\TabsBlock
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks.
 * @var WP_Block $block      Block instance.
 */

declare( strict_types = 1 );

use GlobalStore\TabsBlock\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '' === trim( (string) $content ) ) {
	return;
}

printf(
	'<div %s>%s</div>',
	get_block_wrapper_attributes( array( 'class' => Renderer::BASE_CLASS . '__panel' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
