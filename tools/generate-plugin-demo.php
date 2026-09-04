<?php
/**
 * Writes layout + responsive demo HTML for one plugin into its docs/ folder.
 *
 * Usage:
 *   php tools/generate-plugin-demo.php <plugin-dir> <flash-sale|tabs> <label>
 *
 * @package BlockResearchTool
 */

declare( strict_types = 1 );

if ( 4 !== $argc ) {
	fwrite( STDERR, "Usage: php tools/generate-plugin-demo.php <plugin-dir> <flash-sale|tabs> <label>\n" );
	exit( 1 );
}

$plugin_dir = $argv[1];
$kind       = $argv[2];
$label      = $argv[3];
$root       = dirname( __DIR__ );

if ( ! is_dir( $plugin_dir ) ) {
	fwrite( STDERR, "Not a directory: {$plugin_dir}\n" );
	exit( 1 );
}

$docs = $plugin_dir . '/docs';
if ( ! is_dir( $docs ) ) {
	mkdir( $docs, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

if ( 'flash-sale' === $kind ) {
	gs_write_flash_sale_demos( $plugin_dir, $docs, $label, $root );
} elseif ( 'tabs' === $kind ) {
	gs_write_tabs_demos( $plugin_dir, $docs, $label, $root );
} else {
	fwrite( STDERR, "Unknown kind: {$kind}\n" );
	exit( 1 );
}

echo "Wrote demos in {$docs}\n";

/**
 * Flash Sale Header demos.
 *
 * @param string $plugin_dir Plugin root.
 * @param string $docs       docs/ output directory.
 * @param string $label      Tool label.
 * @param string $root       Repository root.
 */
function gs_write_flash_sale_demos( string $plugin_dir, string $docs, string $label, string $root ): void {
	$is_claude = false !== strpos( $plugin_dir, 'claude-code' );
	$css_path  = $is_claude
		? $plugin_dir . '/build/style-index.css'
		: $plugin_dir . '/build/flash-sale-header/style-index.css';

	if ( ! file_exists( $css_path ) ) {
		fwrite( STDERR, "Missing built CSS: {$css_path}\n" );
		exit( 1 );
	}

	$css    = file_get_contents( $css_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$cutout = gs_write_cutout( $docs );

	if ( $is_claude ) {
		$frames = array(
			array( 'Wide (locked)', 1000, 'wide' ),
			array( 'Medium (locked)', 560, 'medium' ),
			array( 'Tall (locked)', 300, 'tall' ),
			array( 'Wide at 1000px', 1000, 'wide' ),
			array( 'Wide at 560px', 560, 'wide' ),
			array( 'Wide at 300px', 300, 'wide' ),
		);
		$body   = '';
		foreach ( $frames as list( $caption, $width, $size ) ) {
			$body .= sprintf(
				'<figure style="width:%dpx"><figcaption>%s</figcaption>%s</figure>',
				$width,
				htmlspecialchars( $caption, ENT_QUOTES, 'UTF-8' ),
				gs_claude_flash_sale_markup( $size, $cutout )
			);
		}
		$responsive = gs_wrap_page(
			$label . ' · Flash Sale Header · responsive',
			$css,
			gs_demo_bar( $label, 'Flash Sale Header' ) . '<div class="demo-stage demo-stage--flash">' . gs_claude_flash_sale_markup( 'wide', $cutout ) . '</div>',
			gs_page_css( 'flash-responsive' )
		);
	} else {
		require $plugin_dir . '/tests/php/unit/bootstrap.php';

		$attributes = array(
			'title'          => 'The Flash Sale',
			'subtitle'       => '£1 a month for 12 months',
			'countdownLabel' => 'Offer ends in',
			'expiryDate'     => ( new DateTimeImmutable( '+3 days 12 hours 48 minutes 56 seconds' ) )->format( 'Y-m-d\TH:i:s' ),
			'ctaText'        => 'Subscribe now',
			'ctaUrl'         => 'https://example.com/subscribe',
			'finePrint'      => '£1 a month for 12 months, £12 a month thereafter. This offer is only available to new subscribers outside of the UK and applies to current subscribers.',
			'imageUrl'       => $cutout,
			'imageAlt'       => '',
		);

		$frames = array(
			array( 'Wide (locked)', 1000, 'wide' ),
			array( 'Medium (locked)', 560, 'medium' ),
			array( 'Tall (locked)', 300, 'tall' ),
			array( 'Auto at 1000px', 1000, 'auto' ),
			array( 'Auto at 560px', 560, 'auto' ),
			array( 'Auto at 300px', 300, 'auto' ),
		);
		$body   = '';
		foreach ( $frames as list( $caption, $width, $size ) ) {
			$body .= sprintf(
				'<figure style="width:%dpx"><figcaption>%s</figcaption>%s</figure>',
				$width,
				esc_html( $caption ),
				gs_cursor_flash_sale_block( $attributes, $size )
			);
		}
		$responsive = gs_wrap_page(
			$label . ' · Flash Sale Header · responsive',
			$css,
			gs_demo_bar( $label, 'Flash Sale Header' ) . '<div class="demo-stage demo-stage--flash">' . gs_cursor_flash_sale_block( $attributes, 'auto' ) . '</div>',
			gs_page_css( 'flash-responsive' )
		);
	}

	$layouts = gs_wrap_page(
		$label . ' · Flash Sale Header · layouts',
		$css,
		gs_demo_bar( $label, 'Flash Sale Header · locked + auto layouts' ) . '<div class="demo-stage demo-stage--flash-layouts">' . $body . '</div>',
		gs_page_css( 'flash-layouts' )
	);

	file_put_contents( $docs . '/layouts.html', $layouts ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	file_put_contents( $docs . '/responsive.html', $responsive ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Tabs demos.
 *
 * @param string $plugin_dir Plugin root.
 * @param string $docs       docs/ output directory.
 * @param string $label      Tool label.
 * @param string $root       Repository root.
 */
function gs_write_tabs_demos( string $plugin_dir, string $docs, string $label, string $root ): void {
	$css_path = $plugin_dir . '/build/tabs/style-index.css';
	if ( ! file_exists( $css_path ) ) {
		fwrite( STDERR, "Missing built CSS: {$css_path}\n" );
		exit( 1 );
	}

	$stubs = $plugin_dir . '/tests/php/unit/wp-stubs.php';
	if ( ! file_exists( $stubs ) ) {
		$stubs = $root . '/cursor-auto/plugins/tabs-block/tests/php/unit/wp-stubs.php';
	}
	require $stubs;
	require $plugin_dir . '/includes/class-icons.php';
	require $plugin_dir . '/includes/class-attributes.php';
	require $plugin_dir . '/includes/class-renderer.php';

	$css  = file_get_contents( $css_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$tabs = gs_sample_tabs();

	$frames = array(
		array( 'Default card', array() ),
		array( 'Pills', array( 'tabStyle' => 'pills' ) ),
		array( 'Vertical', array( 'orientation' => 'vertical' ) ),
	);

	$body = '';
	foreach ( $frames as list( $caption, $attributes ) ) {
		$body .= sprintf(
			'<figure><figcaption>%s</figcaption>%s</figure>',
			esc_html( $caption ),
			gs_tabs_block( $attributes, $tabs )
		);
	}

	$layouts = gs_wrap_page(
		$label . ' · Tabs · variants',
		$css,
		gs_demo_bar( $label, 'Tabs · underline, pills, vertical' ) . '<div class="demo-stage demo-stage--tabs-layouts">' . $body . '</div>',
		gs_page_css( 'tabs-layouts' )
	);

	$responsive = gs_wrap_page(
		$label . ' · Tabs · responsive',
		$css,
		gs_demo_bar( $label, 'Tabs' ) . '<div class="demo-stage demo-stage--tabs">' . gs_tabs_block( array(), $tabs ) . '</div>',
		gs_page_css( 'tabs-responsive' )
	);

	file_put_contents( $docs . '/layouts.html', $layouts ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	file_put_contents( $docs . '/responsive.html', $responsive ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Render a Cursor-family flash sale block.
 *
 * @param array<string, mixed> $attributes Attributes.
 * @param string               $size       Size attribute.
 * @return string
 */
function gs_cursor_flash_sale_block( array $attributes, string $size ): string {
	$attrs   = array_merge( $attributes, array( 'size' => $size ) );
	$classes = implode( ' ', array_merge( array( \GlobalStore\FlashSaleHeader\Renderer::BASE_CLASS ), \GlobalStore\FlashSaleHeader\Renderer::wrapper_classes( $attrs ) ) );

	return \GlobalStore\FlashSaleHeader\Renderer::render(
		$attrs,
		sprintf(
			'class="%s" style="%s"',
			esc_attr( $classes ),
			esc_attr( \GlobalStore\FlashSaleHeader\Renderer::wrapper_styles( $attrs ) )
		)
	);
}

/**
 * Render a tabs block.
 *
 * @param array<string, mixed>             $attributes Attributes.
 * @param array<int, array<string, mixed>> $tabs       Tabs.
 * @return string
 */
function gs_tabs_block( array $attributes, array $tabs ): string {
	$classes = implode( ' ', array_merge( array( \GlobalStore\TabsBlock\Renderer::BASE_CLASS ), \GlobalStore\TabsBlock\Renderer::wrapper_classes( $attributes ) ) );

	return \GlobalStore\TabsBlock\Renderer::render(
		$attributes,
		$tabs,
		sprintf( 'class="%s"', esc_attr( $classes ) )
	);
}

/**
 * Claude Code flash sale markup (no WordPress renderer).
 *
 * @param string $size   Layout size.
 * @param string $cutout Cutout image URL.
 * @return string
 */
function gs_claude_flash_sale_markup( string $size, string $cutout ): string {
	$chevron = 'tall' === $size ? '<span class="flash-sale-header__chevron" aria-hidden="true"></span>' : '';

	return <<<HTML
<div class="flash-sale-header is-size-{$size}">
	<div class="flash-sale-header__inner">
		<div class="flash-sale-header__media">
			<img class="flash-sale-header__image" src="{$cutout}" alt="" />
		</div>
		<div class="flash-sale-header__content">
			<h2 class="flash-sale-header__title">The Flash Sale</h2>
			<p class="flash-sale-header__subtitle">£1 a month for 12 months</p>
			<p class="flash-sale-header__countdown-label-static">Offer ends in</p>
			<div class="flash-sale-header__countdown">
				<div class="flash-sale-header__countdown-unit"><span class="flash-sale-header__countdown-value">03</span><span class="flash-sale-header__countdown-label">Days</span></div>
				<div class="flash-sale-header__countdown-unit"><span class="flash-sale-header__countdown-value">12</span><span class="flash-sale-header__countdown-label">Hours</span></div>
				<div class="flash-sale-header__countdown-unit"><span class="flash-sale-header__countdown-value">48</span><span class="flash-sale-header__countdown-label">Mins</span></div>
				<div class="flash-sale-header__countdown-unit"><span class="flash-sale-header__countdown-value">56</span><span class="flash-sale-header__countdown-label">Secs</span></div>
			</div>
			<a class="flash-sale-header__cta" href="https://example.com/subscribe">Subscribe now</a>
			<p class="flash-sale-header__legal">£1 a month for 12 months, £10 a month thereafter. This trial offer is only available to readers outside of the UK and excludes current subscribers.</p>
		</div>
		{$chevron}
	</div>
</div>
HTML;
}

/**
 * Sample tabs used by every implementation.
 *
 * @return array<int, array<string, mixed>>
 */
function gs_sample_tabs(): array {
	$rocket = <<<'SVG'
<svg class="wp-block-global-store-tabs__illustration" viewBox="0 0 240 220" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Rocket">
	<defs><linearGradient id="gstb-rocket" x1="18%" y1="92%" x2="86%" y2="8%"><stop offset="0" stop-color="#f4a574"/><stop offset="1" stop-color="#ee6c2d"/></linearGradient></defs>
	<circle cx="44" cy="168" r="22" fill="url(#gstb-rocket)" opacity=".9"/>
	<circle cx="198" cy="58" r="16" fill="url(#gstb-rocket)" opacity=".75"/>
	<circle cx="188" cy="186" r="11" fill="url(#gstb-rocket)" opacity=".55"/>
	<path fill="url(#gstb-rocket)" d="M86 168c10-28 32-62 62-88 18-16 38-26 54-30-3 17-12 38-28 56-26 30-62 52-88 62z"/>
	<path fill="url(#gstb-rocket)" d="M86 168c8 4 16 6 22 6-2-8-6-18-12-28-8 6-10 14-10 22z"/>
	<circle cx="152" cy="92" r="11" fill="#fff" opacity=".35"/>
</svg>
SVG;

	$panel = static function ( string $heading, string $body, string $media = '' ): string {
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
	};

	return array(
		array(
			'label'   => 'Our Mission',
			'icon'    => 'target',
			'slug'    => 'mission',
			'content' => $panel(
				'Serving People. Solving Problems.',
				'Our Mission: To serve people by solving problems that improve productivity, increase prosperity, and create peace of mind.',
				$rocket
			),
		),
		array(
			'label'   => 'Our Superpowers',
			'icon'    => 'bolt',
			'slug'    => 'superpowers',
			'content' => $panel( 'What we are uncommonly good at.', 'Speed, craft, and the discipline to put both to work on problems that matter.' ),
		),
		array(
			'label'   => 'What We Stand For',
			'icon'    => 'sparkle',
			'slug'    => 'stand-for',
			'content' => $panel( 'The principles we hold to.', 'The easy answer and the right answer are not always the same. We choose the latter.' ),
		),
	);
}

/**
 * Write the placeholder cutout used by Flash Sale demos.
 *
 * @param string $docs docs/ directory.
 * @return string Relative URL.
 */
function gs_write_cutout( string $docs ): string {
	$svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 400" preserveAspectRatio="xMidYMid slice">
	<rect width="600" height="400" fill="#15151f"/>
	<g fill="#9aa0ad"><circle cx="180" cy="210" r="52"/><path d="M96 400c0-52 38-92 84-92s84 40 84 92z"/></g>
	<g fill="#c8ced9"><circle cx="330" cy="150" r="62"/><path d="M232 400c0-62 44-108 98-108s98 46 98 108z"/></g>
	<g fill="#7d8492"><circle cx="470" cy="230" r="48"/><path d="M394 400c0-48 34-84 76-84s76 36 76 84z"/></g>
</svg>
SVG;
	file_put_contents( $docs . '/cutout.svg', $svg ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	return '/cutout.svg';
}

/**
 * Sticky chrome for the CTO demo pages.
 *
 * @param string $tool  Tool name.
 * @param string $block Block name.
 * @return string
 */
function gs_demo_bar( string $tool, string $block ): string {
	return '<header class="demo-bar"><span class="demo-bar__tool">'
		. htmlspecialchars( $tool, ENT_QUOTES, 'UTF-8' )
		. '</span><span class="demo-bar__block">'
		. htmlspecialchars( $block, ENT_QUOTES, 'UTF-8' )
		. '</span><span class="demo-bar__vp" id="demo-vp"></span></header>'
		. '<script>document.getElementById("demo-vp").textContent=innerWidth+"×"+innerHeight;</script>';
}

/**
 * Page chrome CSS.
 *
 * @param string $variant Page variant.
 * @return string
 */
function gs_page_css( string $variant ): string {
	$base = 'html,body{margin:0;padding:0}body{font-family:Inter,system-ui,-apple-system,sans-serif}'
		. '.demo-bar{display:flex;align-items:center;gap:12px;padding:10px 18px;background:#111;color:#f4f4f5;font-size:12px;letter-spacing:.02em;position:sticky;top:0;z-index:5}'
		. '.demo-bar__tool{font-weight:700}'
		. '.demo-bar__block{opacity:.75}'
		. '.demo-bar__vp{margin-left:auto;opacity:.6;font-variant-numeric:tabular-nums}'
		. 'figure{margin:0}figcaption{font-size:12px;margin-bottom:8px}';

	if ( 'flash-layouts' === $variant ) {
		return $base . 'body{background:#6f6f6f}.demo-stage--flash-layouts{padding:28px;display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start}figcaption{color:#fff}';
	}
	if ( 'flash-responsive' === $variant ) {
		return $base . 'body{background:#6f6f6f}.demo-stage--flash{padding:20px}';
	}
	if ( 'tabs-layouts' === $variant ) {
		return $base . 'body{background:#e8eaef}.demo-stage--tabs-layouts{padding:36px 24px}figure{margin:0 auto 40px;max-width:920px}figcaption{color:#5c6370}'
			. '.wp-block-columns{display:flex;gap:2rem;align-items:center}.wp-block-column{min-width:0}'
			. '@media(max-width:640px){.wp-block-columns{flex-direction:column}}';
	}

	return $base . 'body{background:#e8eaef}.demo-stage--tabs{padding:20px}'
		. '.wp-block-columns{display:flex;gap:2rem;align-items:center}.wp-block-column{min-width:0}'
		. '@media(max-width:640px){.wp-block-columns{flex-direction:column}}';
}

/**
 * Wrap a full HTML document.
 *
 * @param string $title     Document title.
 * @param string $block_css Compiled block CSS.
 * @param string $body      Body HTML.
 * @param string $page_css  Page chrome CSS.
 * @return string
 */
function gs_wrap_page( string $title, string $block_css, string $body, string $page_css ): string {
	return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
		. '<meta name="viewport" content="width=device-width,initial-scale=1">'
		. '<title>' . htmlspecialchars( $title, ENT_QUOTES, 'UTF-8' ) . '</title>'
		. '<style>' . $block_css . '</style><style>' . $page_css . '</style>'
		. '</head><body>' . $body . '</body></html>';
}
