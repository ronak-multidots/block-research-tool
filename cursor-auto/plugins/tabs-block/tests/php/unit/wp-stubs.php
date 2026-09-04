<?php
/**
 * Minimal WordPress function stubs for the isolated unit suite.
 *
 * These are deliberately faithful for the behaviour the block relies on (entity
 * encoding, tag stripping, slug building) so the unit tests still catch escaping
 * regressions. The real implementations are exercised by the integration suite.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/**
 * Counter behind the `wp_unique_id()` stub.
 *
 * @var int
 */
$GLOBALS['gstb_test_unique_id'] = 0;

if ( ! function_exists( '__' ) ) {
	/**
	 * Pass-through translation.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( string $text ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Encode a value for HTML text nodes.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Encode a value for an HTML attribute.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Remove all tags from a string.
	 *
	 * @param string $text          Text.
	 * @param bool   $remove_breaks Whether to collapse whitespace.
	 * @return string
	 */
	function wp_strip_all_tags( string $text, bool $remove_breaks = false ): string {
		$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
		$text = strip_tags( (string) $text );

		if ( $remove_breaks ) {
			$text = preg_replace( '/[\r\n\t ]+/', ' ', (string) $text );
		}

		return trim( (string) $text );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Sanitise a single-line text value.
	 *
	 * @param string $str Value.
	 * @return string
	 */
	function sanitize_text_field( string $str ): string {
		$filtered = wp_strip_all_tags( $str );
		$filtered = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $filtered );
		// WordPress collapses every run of whitespace, not just newlines.
		$filtered = preg_replace( '/[\r\n\t ]+/', ' ', (string) $filtered );

		return trim( (string) $filtered );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Build a slug from an arbitrary string.
	 *
	 * @param string $title Value.
	 * @return string
	 */
	function sanitize_title( string $title ): string {
		$title = strtolower( wp_strip_all_tags( $title ) );
		$title = preg_replace( '/[^a-z0-9\s-]/', '', $title );
		$title = preg_replace( '/[\s-]+/', '-', (string) $title );

		return trim( (string) $title, '-' );
	}
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	/**
	 * Validate a hex colour.
	 *
	 * @param string $color Colour.
	 * @return string|null
	 */
	function sanitize_hex_color( string $color ): ?string {
		if ( '' === $color ) {
			return '';
		}

		return preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ? $color : null;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Merge arguments with defaults.
	 *
	 * @param array<string, mixed> $args     Arguments.
	 * @param array<string, mixed> $defaults Defaults.
	 * @return array<string, mixed>
	 */
	function wp_parse_args( array $args, array $defaults = array() ): array {
		return array_merge( $defaults, $args );
	}
}

if ( ! function_exists( 'wp_unique_id' ) ) {
	/**
	 * Incrementing identifier.
	 *
	 * @param string $prefix Prefix.
	 * @return string
	 */
	function wp_unique_id( string $prefix = '' ): string {
		++$GLOBALS['gstb_test_unique_id'];

		return $prefix . $GLOBALS['gstb_test_unique_id'];
	}
}
