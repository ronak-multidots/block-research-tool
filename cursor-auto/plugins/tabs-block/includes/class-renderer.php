<?php
/**
 * Server-side markup builder for the Tabs block.
 *
 * @package GlobalStore\TabsBlock
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the front-end markup for the tab list and its panels.
 *
 * `render()` is deliberately free of side effects and takes the already-rendered panel
 * content as an argument, so it can be exercised directly from PHPUnit without booting
 * the block registry. Turning inner blocks into that content is the job of
 * `collect_tabs()`, which is covered by the integration suite instead.
 */
final class Renderer {

	/**
	 * Base CSS class for the container block.
	 */
	public const BASE_CLASS = 'wp-block-global-store-tabs';

	/**
	 * Base CSS class for a single tab panel.
	 */
	public const TAB_CLASS = 'wp-block-global-store-tab';

	/**
	 * Name of the child block that holds a panel's inner blocks.
	 */
	public const TAB_BLOCK = 'global-store/tab';

	/**
	 * Counter used to build unique element IDs when WordPress is not loaded.
	 *
	 * @var int
	 */
	private static $fallback_instance = 0;

	/**
	 * Render the block.
	 *
	 * @param array<string, mixed>             $attributes         Raw block attributes.
	 * @param array<int, array<string, mixed>> $tabs         Tabs, each with `label`, `icon`, `slug` and
	 *                                                       `content` keys. `content` is already-rendered
	 *                                                       inner block HTML.
	 * @param string|null                      $wrapper_attributes Pre-built wrapper attributes string. When null the
	 *                                                             renderer falls back to a minimal wrapper, which keeps
	 *                                                             the class unit-testable outside of a block context.
	 * @return string
	 */
	public static function render( array $attributes, array $tabs, ?string $wrapper_attributes = null ): string {
		$attrs = Attributes::sanitize( $attributes );
		$tabs  = self::prepare_tabs( $tabs );

		// A tabs block with nothing in it would render an empty, focusable tablist.
		if ( empty( $tabs ) ) {
			return '';
		}

		if ( null === $wrapper_attributes ) {
			$classes = array_merge( array( self::BASE_CLASS ), self::wrapper_classes( $attrs ) );

			$wrapper_attributes = sprintf( 'class="%s"', esc_attr( implode( ' ', $classes ) ) );
		}

		$active = self::active_index( $attrs['defaultActiveTab'], count( $tabs ) );
		$uid    = self::instance_id();

		$markup  = '<div ' . $wrapper_attributes . ' data-gstb-tabs>';
		$markup .= self::render_tab_list( $attrs, $tabs, $active, $uid );
		$markup .= self::render_panels( $tabs, $active, $uid );
		$markup .= '</div>';

		return $markup;
	}

	/**
	 * Turn the parsed inner blocks into the tab list `render()` expects.
	 *
	 * The container renders its children itself rather than relying on the `$content`
	 * WordPress passes to `render.php`: that string is a single blob of concatenated
	 * panels, with no way to tell where one tab ends and the next begins.
	 *
	 * @param \WP_Block|null $block Block instance.
	 * @return array<int, array<string, mixed>>
	 */
	public static function collect_tabs( ?\WP_Block $block ): array {
		if ( null === $block || empty( $block->parsed_block['innerBlocks'] ) ) {
			return array();
		}

		$tabs = array();

		foreach ( $block->parsed_block['innerBlocks'] as $child ) {
			if ( ! isset( $child['blockName'] ) || self::TAB_BLOCK !== $child['blockName'] ) {
				continue;
			}

			$attrs = isset( $child['attrs'] ) && is_array( $child['attrs'] ) ? $child['attrs'] : array();
			$tab   = Attributes::sanitize_tab( $attrs );

			$tab['content'] = self::render_inner_blocks( $child, $block );

			$tabs[] = $tab;
		}

		return $tabs;
	}

	/**
	 * Render the blocks an author placed inside one tab.
	 *
	 * Each block is rendered with the container's own context so blocks that depend on
	 * it — a post title inside a query loop, for instance — keep working inside a tab.
	 *
	 * @param array<string, mixed> $tab       Parsed tab block.
	 * @param \WP_Block            $container Container block instance.
	 * @return string
	 */
	private static function render_inner_blocks( array $tab, \WP_Block $container ): string {
		$content = '';

		foreach ( $tab['innerBlocks'] ?? array() as $inner_block ) {
			$content .= ( new \WP_Block( $inner_block, $container->context ) )->render();
		}

		return $content;
	}

	/**
	 * Build the list of wrapper classes derived from the block attributes.
	 *
	 * Sanitising here as well keeps the helper safe to call with a partial attribute
	 * array, which is how both the editor preview and other integrations use it.
	 *
	 * @param array<string, mixed> $attributes Block attributes, sanitised or raw.
	 * @return string[]
	 */
	public static function wrapper_classes( array $attributes ): array {
		$attrs = Attributes::sanitize( $attributes );

		$classes = array(
			'is-orientation-' . $attrs['orientation'],
			'is-tab-style-' . $attrs['tabStyle'],
			'is-tabs-aligned-' . $attrs['alignment'],
		);

		if ( $attrs['showIcons'] ) {
			$classes[] = 'has-tab-icons';
		}

		if ( '' !== $attrs['accentColor'] ) {
			$classes[] = 'has-custom-accent';
		}

		return $classes;
	}

	/**
	 * Build the inline style declarations derived from the block attributes.
	 *
	 * @param array<string, mixed> $attributes Block attributes, sanitised or raw.
	 * @return string
	 */
	public static function wrapper_styles( array $attributes ): string {
		$attrs = Attributes::sanitize( $attributes );

		if ( '' === $attrs['accentColor'] ) {
			return '';
		}

		return '--gstb-accent:' . $attrs['accentColor'];
	}

	/**
	 * Clamp the stored tab index to one that actually exists.
	 *
	 * @param int $requested Stored index.
	 * @param int $total     Number of tabs.
	 * @return int
	 */
	public static function active_index( int $requested, int $total ): int {
		if ( $total < 1 ) {
			return 0;
		}

		return min( max( 0, $requested ), $total - 1 );
	}

	/**
	 * Normalise the tab list: fill in missing labels and drop duplicate slugs.
	 *
	 * @param array<int, mixed> $tabs Raw tabs.
	 * @return array<int, array<string, mixed>>
	 */
	private static function prepare_tabs( array $tabs ): array {
		$prepared = array();
		$slugs    = array();

		foreach ( array_values( $tabs ) as $index => $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}

			$prepared_tab = Attributes::sanitize_tab( $tab );

			if ( '' === $prepared_tab['label'] ) {
				$prepared_tab['label'] = sprintf(
					/* translators: %d: position of the tab in the list, starting at 1. */
					__( 'Tab %d', 'tabs-block' ),
					$index + 1
				);
			}

			// Two tabs answering to the same deep link would make the target ambiguous.
			if ( '' !== $prepared_tab['slug'] && in_array( $prepared_tab['slug'], $slugs, true ) ) {
				$prepared_tab['slug'] = '';
			}

			if ( '' !== $prepared_tab['slug'] ) {
				$slugs[] = $prepared_tab['slug'];
			}

			$prepared_tab['content'] = isset( $tab['content'] ) && is_string( $tab['content'] ) ? $tab['content'] : '';

			$prepared[] = $prepared_tab;
		}

		return $prepared;
	}

	/**
	 * Render the tab list.
	 *
	 * @param array<string, mixed>             $attrs  Sanitised attributes.
	 * @param array<int, array<string, mixed>> $tabs   Prepared tabs.
	 * @param int                              $active Index of the open tab.
	 * @param string                           $uid    Unique prefix for element IDs.
	 * @return string
	 */
	private static function render_tab_list( array $attrs, array $tabs, int $active, string $uid ): string {
		$base = self::BASE_CLASS;

		$label = '' !== $attrs['accessibleLabel'] ? $attrs['accessibleLabel'] : __( 'Tabs', 'tabs-block' );

		$markup = sprintf(
			'<div class="%1$s" role="tablist" aria-label="%2$s" aria-orientation="%3$s">',
			esc_attr( $base . '__list' ),
			esc_attr( $label ),
			esc_attr( $attrs['orientation'] )
		);

		foreach ( $tabs as $index => $tab ) {
			$is_active = $index === $active;

			$markup .= sprintf(
				'<button type="button" class="%1$s" id="%2$s" role="tab" aria-controls="%3$s" aria-selected="%4$s" tabindex="%5$s"%6$s>',
				esc_attr( $base . '__tab' . ( $is_active ? ' is-active' : '' ) ),
				esc_attr( self::tab_id( $uid, $index ) ),
				esc_attr( self::panel_id( $uid, $index ) ),
				$is_active ? 'true' : 'false',
				// Roving tabindex: the tab list is a single stop, arrow keys move within it.
				$is_active ? '0' : '-1',
				'' !== $tab['slug'] ? ' data-gstb-slug="' . esc_attr( $tab['slug'] ) . '"' : ''
			);

			$icon = $attrs['showIcons'] ? Icons::markup( $tab['icon'], $base . '__icon' ) : '';

			if ( '' !== $icon ) {
				$markup .= '<span class="' . esc_attr( $base . '__tab-icon' ) . '">' . $icon . '</span>';
			}

			$markup .= '<span class="' . esc_attr( $base . '__tab-label' ) . '">' . esc_html( $tab['label'] ) . '</span>';
			$markup .= '</button>';
		}

		return $markup . '</div>';
	}

	/**
	 * Render every panel, with only the active one visible.
	 *
	 * All panels are present in the HTML so the content stays crawlable and printable,
	 * and so a visitor without JavaScript still gets the default tab.
	 *
	 * @param array<int, array<string, mixed>> $tabs   Prepared tabs.
	 * @param int                              $active Index of the open tab.
	 * @param string                           $uid    Unique prefix for element IDs.
	 * @return string
	 */
	private static function render_panels( array $tabs, int $active, string $uid ): string {
		$base = self::BASE_CLASS;

		$markup = '<div class="' . esc_attr( $base . '__panels' ) . '">';

		foreach ( $tabs as $index => $tab ) {
			$is_active = $index === $active;

			$markup .= sprintf(
				'<div class="%1$s" id="%2$s" role="tabpanel" aria-labelledby="%3$s" tabindex="0"%4$s>',
				esc_attr( self::TAB_CLASS . ' ' . $base . '__panel' . ( $is_active ? ' is-active' : '' ) ),
				esc_attr( self::panel_id( $uid, $index ) ),
				esc_attr( self::tab_id( $uid, $index ) ),
				$is_active ? '' : ' hidden'
			);

			// Inner block output has already been rendered and escaped by WordPress.
			$markup .= $tab['content'];
			$markup .= '</div>';
		}

		return $markup . '</div>';
	}

	/**
	 * ID of the button that controls a panel.
	 *
	 * @param string $uid   Unique prefix.
	 * @param int    $index Tab position.
	 * @return string
	 */
	private static function tab_id( string $uid, int $index ): string {
		return $uid . '-tab-' . $index;
	}

	/**
	 * ID of a panel.
	 *
	 * @param string $uid   Unique prefix.
	 * @param int    $index Tab position.
	 * @return string
	 */
	private static function panel_id( string $uid, int $index ): string {
		return $uid . '-panel-' . $index;
	}

	/**
	 * Prefix that keeps element IDs unique when a page holds several tabs blocks.
	 *
	 * IDs are not stable between requests, which is why deep links match on the
	 * author-defined slug in `data-gstb-slug` instead.
	 *
	 * @return string
	 */
	private static function instance_id(): string {
		if ( function_exists( 'wp_unique_id' ) ) {
			return wp_unique_id( 'gstb-' );
		}

		++self::$fallback_instance;

		return 'gstb-' . self::$fallback_instance;
	}
}
