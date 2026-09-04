<?php
/**
 * Renders the block into a standalone HTML page for design review.
 *
 * The page uses the compiled front-end stylesheet and the real server-side renderer,
 * so it is a faithful preview of the three layouts without booting WordPress:
 *
 *   php tools/preview.php && open artifacts/preview.html
 *
 * @package GlobalStore\FlashSaleHeader
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader\Tools;

use GlobalStore\FlashSaleHeader\Renderer;

require_once dirname( __DIR__ ) . '/tests/php/unit/bootstrap.php';

$gsfsh_style = dirname( __DIR__ ) . '/build/flash-sale-header/style-index.css';

if ( ! file_exists( $gsfsh_style ) ) {
	echo "Run 'npm run build' first.\n";
	exit( 1 );
}

/**
 * Write a stand-in for the cutout photography next to the preview page.
 *
 * The renderer only accepts http(s) and root-relative URLs, so the preview is served
 * over PHP's built-in web server rather than opened straight from disk.
 *
 * @param string $dir Output directory.
 * @return string Root-relative URL of the placeholder.
 */
function gsfsh_placeholder_cutout( string $dir ): string {
	$svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400" preserveAspectRatio="xMidYMid slice">
	<rect width="600" height="400" fill="#15151f"/>
	<g fill="#9aa0ad">
		<circle cx="180" cy="210" r="52"/>
		<path d="M96 400c0-52 38-92 84-92s84 40 84 92z"/>
	</g>
	<g fill="#c8ced9">
		<circle cx="330" cy="150" r="62"/>
		<path d="M232 400c0-62 44-108 98-108s98 46 98 108z"/>
	</g>
	<g fill="#7d8492">
		<circle cx="470" cy="230" r="48"/>
		<path d="M394 400c0-48 34-84 76-84s76 36 76 84z"/>
	</g>
</svg>
SVG;

	file_put_contents( $dir . '/cutout.svg', $svg ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	return '/cutout.svg';
}

/**
 * Build the preview page.
 *
 * @param string $output_dir Directory the page and its assets are written to.
 * @param string $stylesheet Path to the compiled front-end stylesheet.
 * @return string The HTML document.
 */
function gsfsh_build_preview( string $output_dir, string $stylesheet ): string {
	$attributes = array(
		'title'          => 'The Flash Sale',
		'subtitle'       => '£1 a month for 12 months',
		'countdownLabel' => 'Offer ends in',
		'expiryDate'     => ( new \DateTimeImmutable( '+3 days 12 hours 48 minutes 56 seconds' ) )->format( 'Y-m-d\TH:i:s' ),
		'ctaText'        => 'Subscribe now',
		'ctaUrl'         => 'https://example.com/subscribe',
		'finePrint'      => '£1 a month for 12 months, £12 a month thereafter. This offer is only available to new subscribers outside of the UK and applies to current subscribers.',
		'imageUrl'       => gsfsh_placeholder_cutout( $output_dir ),
		'imageAlt'       => '',
	);

	// Each frame: a label, the wrapper width and the size attribute.
	$frames = array(
		array( 'Wide (locked)', 1000, 'wide' ),
		array( 'Medium (locked)', 560, 'medium' ),
		array( 'Tall (locked)', 300, 'tall' ),
		array( 'Auto at 1000px', 1000, 'auto' ),
		array( 'Auto at 560px', 560, 'auto' ),
		array( 'Auto at 300px', 300, 'auto' ),
	);

	$body = '';

	foreach ( $frames as list( $label, $width, $size ) ) {
		$attrs   = array_merge( $attributes, array( 'size' => $size ) );
		$classes = implode( ' ', array_merge( array( Renderer::BASE_CLASS ), Renderer::wrapper_classes( $attrs ) ) );

		$body .= sprintf(
			'<figure style="width:%1$dpx"><figcaption>%2$s</figcaption>%3$s</figure>',
			$width,
			esc_html( $label ),
			Renderer::render(
				$attrs,
				sprintf(
					'class="%s" style="%s"',
					esc_attr( $classes ),
					esc_attr( Renderer::wrapper_styles( $attrs ) )
				)
			)
		);
	}

	$page_styles = 'body{margin:0;padding:32px;background:#6f6f6f;'
		. 'font-family:-apple-system,system-ui,sans-serif;display:flex;flex-wrap:wrap;'
		. 'gap:32px;align-items:flex-start}figure{margin:0}'
		. 'figcaption{color:#fff;font-size:12px;margin-bottom:8px}';

	return sprintf(
		'<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
			. '<title>Flash Sale Header preview</title><style>%s</style><style>%s</style>'
			. '</head><body>%s</body></html>',
		file_get_contents( $stylesheet ), // phpcs:ignore WordPress.WP.AlternativeFunctions
		$page_styles,
		$body
	);
}

$gsfsh_output_dir = dirname( __DIR__ ) . '/artifacts';

if ( ! is_dir( $gsfsh_output_dir ) ) {
	mkdir( $gsfsh_output_dir, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

// phpcs:ignore WordPress.WP.AlternativeFunctions
file_put_contents(
	$gsfsh_output_dir . '/preview.html',
	gsfsh_build_preview( $gsfsh_output_dir, $gsfsh_style )
);

echo "Wrote artifacts/preview.html\n";
echo "Serve it with: php -S 127.0.0.1:8765 -t artifacts\n";
