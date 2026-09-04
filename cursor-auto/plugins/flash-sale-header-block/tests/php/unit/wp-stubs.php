<?php
/**
 * Minimal WordPress function stubs for the isolated unit suite.
 *
 * These are deliberately faithful for the behaviour the block relies on (protocol
 * filtering, entity encoding, tag stripping) so the unit tests still catch escaping
 * regressions. The real implementations are exercised by the integration suite.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
	define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
	define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
}

/**
 * Registry of filter callbacks added during a test.
 *
 * @var array<string, callable[]>
 */
$GLOBALS['gsfsh_test_filters'] = array();

/**
 * Site timezone used by the stubs. Tests may override it.
 *
 * @var string
 */
$GLOBALS['gsfsh_test_timezone'] = 'UTC';

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Register a filter callback.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 * @return bool
	 */
	function add_filter( string $hook, callable $callback ): bool {
		$GLOBALS['gsfsh_test_filters'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Apply registered filter callbacks.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value being filtered.
	 * @return mixed
	 */
	function apply_filters( string $hook, $value ) {
		foreach ( $GLOBALS['gsfsh_test_filters'][ $hook ] ?? array() as $callback ) {
			$value = $callback( $value );
		}

		return $value;
	}
}

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

if ( ! function_exists( 'wp_allowed_protocols' ) ) {
	/**
	 * Protocols WordPress considers safe by default.
	 *
	 * @return string[]
	 */
	function wp_allowed_protocols(): array {
		return array( 'http', 'https', 'mailto', 'tel' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Filter a URL down to a safe protocol.
	 *
	 * @param string        $url       URL.
	 * @param string[]|null $protocols Allowed protocols.
	 * @return string
	 */
	function esc_url_raw( string $url, ?array $protocols = null ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		// Strip control characters the way WordPress does before inspecting the scheme.
		$url = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );

		if ( ! is_string( $url ) || '' === $url ) {
			return '';
		}

		$protocols = $protocols ?? wp_allowed_protocols();

		if ( str_starts_with( $url, '/' ) || str_starts_with( $url, '#' ) ) {
			return $url;
		}

		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );

		if ( '' === $scheme || ! in_array( $scheme, $protocols, true ) ) {
			return '';
		}

		return $url;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Wrapper around parse_url().
	 *
	 * @param string $url       URL.
	 * @param int    $component Component to return.
	 * @return mixed
	 */
	function wp_parse_url( string $url, int $component = -1 ) {
		return parse_url( $url, $component ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Filter a URL and encode it for output.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url( string $url ): string {
		$url = esc_url_raw( $url );

		return str_replace( array( '&', "'" ), array( '&#038;', '&#039;' ), $url );
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
		$filtered = preg_replace( '/[\r\n\t]+/', ' ', $filtered );
		$filtered = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $filtered );

		return trim( (string) $filtered );
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	/**
	 * Approximate wp_kses(): keep allowed tags, drop event handlers and unsafe URLs.
	 *
	 * @param string                            $content       Content.
	 * @param array<string, array<string, bool>> $allowed_html Allowed tags.
	 * @return string
	 */
	function wp_kses( string $content, array $allowed_html ): string {
		$allowed = '';

		foreach ( array_keys( $allowed_html ) as $tag ) {
			$allowed .= '<' . $tag . '>';
		}

		$content = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $content );
		$content = strip_tags( (string) $content, $allowed );
		$content = preg_replace( '/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $content );
		$content = preg_replace( '/(href|src)\s*=\s*("|\')\s*(javascript|data|vbscript):[^"\']*\\2/i', '', (string) $content );

		return (string) $content;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Approximate wp_kses_post() with the inline subset the block emits.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	function wp_kses_post( string $content ): string {
		return wp_kses(
			$content,
			array(
				'strong' => array(),
				'b'      => array(),
				'em'     => array(),
				'i'      => array(),
				'sup'    => array(),
				'sub'    => array(),
				'br'     => array(),
				'span'   => array(),
				'a'      => array(),
			)
		);
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

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Cast a value to a non-negative integer.
	 *
	 * @param mixed $maybeint Value.
	 * @return int
	 */
	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
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

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * Site timezone.
	 *
	 * @return DateTimeZone
	 */
	function wp_timezone(): DateTimeZone {
		return new DateTimeZone( $GLOBALS['gsfsh_test_timezone'] );
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	/**
	 * Build a REST URL.
	 *
	 * @param string $path Route path.
	 * @return string
	 */
	function rest_url( string $path = '' ): string {
		return 'https://example.test/wp-json/' . ltrim( $path, '/' );
	}
}
