<?php
/**
 * Attribute normalisation and sanitisation for the Tabs blocks.
 *
 * @package GlobalStore\TabsBlock
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock;

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
	 * Directions the tab list can run in.
	 */
	public const ORIENTATIONS = array( 'horizontal', 'vertical' );

	/**
	 * Visual treatments for the tab list.
	 */
	public const TAB_STYLES = array( 'underline', 'pills' );

	/**
	 * Where the tab list sits along its own axis.
	 *
	 * `left` and `right` are accepted as aliases of `start` and `end` so the
	 * control matches the labels used by `core/tabs`.
	 */
	public const ALIGNMENTS = array( 'start', 'center', 'end', 'left', 'right' );

	/**
	 * Default attributes for the container block, kept in sync with `src/tabs/block.json`.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'defaultActiveTab' => 0,
			'orientation'      => 'horizontal',
			'tabStyle'         => 'underline',
			'alignment'        => 'center',
			'showIcons'        => true,
			'accentColor'      => '',
			'accessibleLabel'  => '',
		);
	}

	/**
	 * Default attributes for a single tab, kept in sync with `src/tab/block.json`.
	 *
	 * @return array<string, mixed>
	 */
	public static function tab_defaults(): array {
		return array(
			'label' => '',
			'icon'  => Icons::NONE,
			'slug'  => '',
		);
	}

	/**
	 * Sanitise the container block attributes.
	 *
	 * @param array<string, mixed> $attributes Raw attributes as supplied by the block parser.
	 * @return array<string, mixed> Sanitised attributes.
	 */
	public static function sanitize( array $attributes ): array {
		$attributes = wp_parse_args( $attributes, self::defaults() );

		return array(
			// Clamped against the tab count by the renderer, which is the only place that knows it.
			'defaultActiveTab' => max( 0, (int) $attributes['defaultActiveTab'] ),
			'orientation'      => self::sanitize_enum( $attributes['orientation'], self::ORIENTATIONS, 'horizontal' ),
			'tabStyle'         => self::sanitize_enum( $attributes['tabStyle'], self::TAB_STYLES, 'underline' ),
			'alignment'        => self::normalize_alignment(
				self::sanitize_enum( $attributes['alignment'], self::ALIGNMENTS, 'center' )
			),
			'showIcons'        => (bool) $attributes['showIcons'],
			'accentColor'      => self::sanitize_color( $attributes['accentColor'] ),
			'accessibleLabel'  => self::sanitize_text( $attributes['accessibleLabel'] ),
		);
	}

	/**
	 * Sanitise the attributes of a single tab.
	 *
	 * @param array<string, mixed> $attributes Raw tab attributes.
	 * @return array<string, mixed> Sanitised attributes.
	 */
	public static function sanitize_tab( array $attributes ): array {
		$attributes = wp_parse_args( $attributes, self::tab_defaults() );

		$icon = is_string( $attributes['icon'] ) ? trim( $attributes['icon'] ) : Icons::NONE;

		return array(
			'label' => self::sanitize_text( $attributes['label'] ),
			'icon'  => Icons::is_valid( $icon ) ? $icon : Icons::NONE,
			'slug'  => self::sanitize_slug( $attributes['slug'] ),
		);
	}

	/**
	 * Map core-style left/right labels onto logical start/end values.
	 *
	 * @param string $alignment Sanitised alignment.
	 * @return string
	 */
	public static function normalize_alignment( string $alignment ): string {
		if ( 'left' === $alignment ) {
			return 'start';
		}

		if ( 'right' === $alignment ) {
			return 'end';
		}

		return $alignment;
	}

	/**
	 * Restrict a value to a known set.
	 *
	 * @param mixed    $value    Raw value.
	 * @param string[] $allowed  Accepted values.
	 * @param string   $fallback Value used when the input is not accepted.
	 * @return string
	 */
	public static function sanitize_enum( $value, array $allowed, string $fallback ): string {
		if ( ! is_string( $value ) ) {
			return $fallback;
		}

		$value = strtolower( trim( $value ) );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
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
	 * Sanitise the anchor slug used for deep links.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_slug( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_title( (string) $value );
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
}
