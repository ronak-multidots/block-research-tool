<?php
/**
 * Renders the Tabs block into a standalone HTML page for design review.
 *
 * Run `php tools/preview.php` then open artifacts/preview.html.
 *
 * @package GlobalStore\TabsBlock
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock\Tools;

use GlobalStore\TabsBlock\Renderer;

require_once dirname( __DIR__ ) . '/tests/php/unit/bootstrap.php';

$gstb_style = dirname( __DIR__ ) . '/build/tabs/style-index.css';
$gstb_view  = dirname( __DIR__ ) . '/build/tabs/view.js';

if ( ! file_exists( $gstb_style ) || ! file_exists( $gstb_view ) ) {
	echo "Run 'npm run build' first.\n";
	exit( 1 );
}

/**
 * Peach rocket used by the default "Our Mission" panel.
 *
 * @return string
 */
function gstb_rocket_svg(): string {
	return <<<'SVG'
<svg class="wp-block-global-store-tabs__illustration" viewBox="0 0 240 220" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Rocket">
	<defs>
		<linearGradient id="gstb-rocket" x1="18%" y1="92%" x2="86%" y2="8%">
			<stop offset="0" stop-color="#f4a574"/>
			<stop offset="1" stop-color="#ee6c2d"/>
		</linearGradient>
	</defs>
	<circle cx="44" cy="168" r="22" fill="url(#gstb-rocket)" opacity=".9"/>
	<circle cx="198" cy="58" r="16" fill="url(#gstb-rocket)" opacity=".75"/>
	<circle cx="188" cy="186" r="11" fill="url(#gstb-rocket)" opacity=".55"/>
	<path fill="url(#gstb-rocket)" d="M86 168c10-28 32-62 62-88 18-16 38-26 54-30-3 17-12 38-28 56-26 30-62 52-88 62z"/>
	<path fill="url(#gstb-rocket)" d="M86 168c8 4 16 6 22 6-2-8-6-18-12-28-8 6-10 14-10 22z"/>
	<circle cx="152" cy="92" r="11" fill="#fff" opacity=".35"/>
</svg>
SVG;
}

/**
 * Two-column panel markup matching the reference layout.
 *
 * @param string $heading Heading text.
 * @param string $body    Body copy.
 * @param string $media   Optional right-column HTML.
 * @return string
 */
function gstb_panel( string $heading, string $body, string $media = '' ): string {
	$media_html = '' !== $media
		? '<div class="wp-block-column" style="flex-basis:42%">' . $media . '</div>'
		: '';

	return '<div class="wp-block-columns wp-block-global-store-tabs__media-row">'
		. '<div class="wp-block-column" style="flex-basis:58%">'
		. '<h3 class="wp-block-heading">' . esc_html( $heading ) . '</h3>'
		. '<p>' . esc_html( $body ) . '</p>'
		. '</div>'
		. $media_html
		. '</div>';
}

/**
 * Build the preview page.
 *
 * @param string $stylesheet Compiled CSS.
 * @param string $view       Compiled front-end script.
 * @return string
 */
function gstb_build_preview( string $stylesheet, string $view ): string {
	$tabs = array(
		array(
			'label'   => 'Our Mission',
			'icon'    => 'target',
			'slug'    => 'mission',
			'content' => gstb_panel(
				'Serving People. Solving Problems.',
				'Our Mission: To serve people by solving problems that improve productivity, increase prosperity, and create peace of mind.',
				gstb_rocket_svg()
			),
		),
		array(
			'label'   => 'Our Superpowers',
			'icon'    => 'bolt',
			'slug'    => 'superpowers',
			'content' => gstb_panel(
				'What we are uncommonly good at.',
				'Speed, craft, and the discipline to put both to work on problems that matter.'
			),
		),
		array(
			'label'   => 'What We Stand For',
			'icon'    => 'sparkle',
			'slug'    => 'stand-for',
			'content' => gstb_panel(
				'The principles we hold to.',
				'The easy answer and the right answer are not always the same. We choose the latter.'
			),
		),
	);

	$frames = array(
		array( 'Default card', array() ),
		array( 'Pills', array( 'tabStyle' => 'pills' ) ),
		array( 'Vertical', array( 'orientation' => 'vertical' ) ),
	);

	$body = '';

	foreach ( $frames as list( $label, $attributes ) ) {
		$classes = implode( ' ', array_merge( array( Renderer::BASE_CLASS ), Renderer::wrapper_classes( $attributes ) ) );

		$body .= sprintf(
			'<figure><figcaption>%s</figcaption>%s</figure>',
			esc_html( $label ),
			Renderer::render(
				$attributes,
				$tabs,
				sprintf( 'class="%s"', esc_attr( $classes ) )
			)
		);
	}

	$page_styles = 'body{margin:0;padding:48px 32px;background:#e8eaef;'
		. 'font-family:Inter,system-ui,-apple-system,sans-serif}'
		. 'figure{margin:0 auto 48px;max-width:920px}'
		. 'figcaption{color:#5c6370;font-size:13px;margin-bottom:12px}'
		. '.wp-block-columns{display:flex;gap:2rem;align-items:center}'
		. '.wp-block-column{min-width:0}'
		. '@media(max-width:640px){.wp-block-columns{flex-direction:column}}';

	return sprintf(
		'<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
			. '<meta name="viewport" content="width=device-width,initial-scale=1">'
			. '<title>Global Store Tabs preview</title><style>%s</style><style>%s</style>'
			. '</head><body>%s<script>%s</script></body></html>',
		file_get_contents( $stylesheet ), // phpcs:ignore WordPress.WP.AlternativeFunctions
		$page_styles,
		$body,
		file_get_contents( $view ) // phpcs:ignore WordPress.WP.AlternativeFunctions
	);
}

$gstb_output_dir = dirname( __DIR__ ) . '/artifacts';

if ( ! is_dir( $gstb_output_dir ) ) {
	mkdir( $gstb_output_dir, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

// phpcs:ignore WordPress.WP.AlternativeFunctions
file_put_contents(
	$gstb_output_dir . '/preview.html',
	gstb_build_preview( $gstb_style, $gstb_view )
);

echo "Wrote artifacts/preview.html\n";
echo "Serve it with: php -S 127.0.0.1:8766 -t artifacts\n";
