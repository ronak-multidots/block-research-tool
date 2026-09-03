<?php
/**
 * Attribute normalisation and sanitisation for the Flash Sale Header block.
 *
 * @package GlobalStore\FlashSaleHeader
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalises raw block attributes into a predictable, safe shape.
 *
 * `block.json` already declares types and defaults, but attributes can still arrive
 * from hand-edited post content or the REST API, so every value is re-validated here
 * before it reaches the renderer.
 */
final class Attributes {

	/**
	 * Layout sizes the block understands.
	 *
	 * `auto` defers the decision to CSS container queries.
	 */
	public const SIZES = array( 'auto', 'wide', 'medium', 'tall' );

	/**
	 * Supported image focal positions.
	 */
	public const IMAGE_POSITIONS = array( 'center', 'top', 'bottom', 'left', 'right' );

	/**
	 * Default attribute values, kept in sync with `block.json`.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'size'             => 'auto',
			'title'            => '',
			'subtitle'         => '',
			'countdownLabel'   => '',
			'expiryDate'       => '',
			'expiredMessage'   => '',
			'ctaText'          => '',
			'ctaUrl'           => '',
			'ctaOpensInNewTab' => false,
			'finePrint'        => '',
			'imageId'          => 0,
			'imageUrl'         => '',
			'imageAlt'         => '',
			'imagePosition'    => 'center',
			'accentColor'      => '',
			'hideWhenExpired'  => false,
		);
	}

	/**
	 * Sanitise a raw attribute array.
	 *
	 * @param array<string, mixed> $attributes Raw attributes as supplied by the block parser.
	 * @return array<string, mixed> Sanitised attributes.
	 */
	public static function sanitize( array $attributes ): array {
		$attributes = wp_parse_args( $attributes, self::defaults() );

		$size = is_string( $attributes['size'] ) ? strtolower( trim( $attributes['size'] ) ) : 'auto';

		$image_position = is_string( $attributes['imagePosition'] ) ? strtolower( trim( $attributes['imagePosition'] ) ) : 'center';

		return array(
			'size'             => in_array( $size, self::SIZES, true ) ? $size : 'auto',
			'title'            => self::sanitize_inline_html( $attributes['title'] ),
			'subtitle'         => self::sanitize_inline_html( $attributes['subtitle'] ),
			'countdownLabel'   => self::sanitize_text( $attributes['countdownLabel'] ),
			'expiryDate'       => self::sanitize_datetime( $attributes['expiryDate'] ),
			'expiredMessage'   => self::sanitize_text( $attributes['expiredMessage'] ),
			'ctaText'          => self::sanitize_text( $attributes['ctaText'] ),
			'ctaUrl'           => self::sanitize_url( $attributes['ctaUrl'] ),
			'ctaOpensInNewTab' => (bool) $attributes['ctaOpensInNewTab'],
			'finePrint'        => self::sanitize_inline_html( $attributes['finePrint'] ),
			// Clamped rather than absint()'d: a negative ID must not flip to a real one.
			'imageId'          => max( 0, (int) $attributes['imageId'] ),
			'imageUrl'         => self::sanitize_url( $attributes['imageUrl'] ),
			'imageAlt'         => self::sanitize_text( $attributes['imageAlt'] ),
			'imagePosition'    => in_array( $image_position, self::IMAGE_POSITIONS, true ) ? $image_position : 'center',
			'accentColor'      => self::sanitize_color( $attributes['accentColor'] ),
			'hideWhenExpired'  => (bool) $attributes['hideWhenExpired'],
		);
	}

	/**
	 * Strip every tag from a value and normalise it to a plain string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_text( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( wp_strip_all_tags( (string) $value, false ) );
	}

	/**
	 * Allow the small subset of inline formatting that RichText can produce.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_inline_html( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return wp_kses(
			(string) $value,
			array(
				'strong' => array(),
				'b'      => array(),
				'em'     => array(),
				'i'      => array(),
				'sup'    => array(),
				'sub'    => array(),
				'br'     => array(),
				'span'   => array( 'class' => true ),
				'a'      => array(
					'href'   => true,
					'rel'    => true,
					'target' => true,
					'title'  => true,
				),
			)
		);
	}

	/**
	 * Sanitise a URL, restricting it to safe protocols.
	 *
	 * @param mixed $value Raw value.
	 * @return string Empty string when the URL is not usable.
	 */
	public static function sanitize_url( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return esc_url_raw( trim( (string) $value ), array( 'http', 'https', 'mailto', 'tel' ) );
	}

	/**
	 * Sanitise a colour value, accepting hex colours and CSS custom properties only.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_color( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$hex = sanitize_hex_color( $value );

		if ( is_string( $hex ) && '' !== $hex ) {
			return $hex;
		}

		// Allow theme palette custom properties such as `var(--wp--preset--color--primary)`.
		if ( 1 === preg_match( '/^var\(\s*--[A-Za-z0-9_-]+\s*\)$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Sanitise the `Y-m-d\TH:i:s` value produced by the DateTimePicker component.
	 *
	 * @param mixed $value Raw value.
	 * @return string Normalised datetime string, or an empty string when invalid.
	 */
	public static function sanitize_datetime( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$timestamp = self::to_timestamp( $value );

		if ( null === $timestamp ) {
			return '';
		}

		return $value;
	}

	/**
	 * Convert a stored datetime string into a UTC timestamp.
	 *
	 * Values without an explicit offset are interpreted in the site timezone, which is
	 * what the editor's DateTimePicker produces.
	 *
	 * @param string $value Datetime string.
	 * @return int|null UTC timestamp, or null when the value cannot be parsed.
	 */
	public static function to_timestamp( string $value ): ?int {
		$value = trim( $value );

		if ( '' === $value ) {
			return null;
		}

		// Reject anything that is not an ISO-8601-ish date to keep the parser predictable.
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})([T ](\d{2}):(\d{2})(:(\d{2}))?)?(Z|[+-]\d{2}:?\d{2})?$/', $value, $matches ) ) {
			return null;
		}

		// PHP happily rolls over impossible dates such as 2030-13-01, so reject them first.
		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return null;
		}

		if ( isset( $matches[5] ) && ( (int) $matches[5] > 23 || (int) $matches[6] > 59 ) ) {
			return null;
		}

		$has_offset = (bool) preg_match( '/(Z|[+-]\d{2}:?\d{2})$/', $value );

		try {
			$date = new \DateTimeImmutable( $value, $has_offset ? new \DateTimeZone( 'UTC' ) : wp_timezone() );
		} catch ( \Exception $exception ) {
			return null;
		}

		return $date->getTimestamp();
	}
}
