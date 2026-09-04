<?php
/**
 * Server-side markup builder for the Flash Sale Header block.
 *
 * @package GlobalStore\FlashSaleHeader
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the front-end markup for the block.
 *
 * The renderer is deliberately free of side effects so it can be exercised directly
 * from PHPUnit without booting the block registry.
 */
final class Renderer {

	/**
	 * Base CSS class for the block.
	 */
	public const BASE_CLASS = 'wp-block-global-store-flash-sale-header';

	/**
	 * Seconds in a day.
	 */
	private const DAY = DAY_IN_SECONDS;

	/**
	 * Render the block.
	 *
	 * @param array<string, mixed> $attributes         Raw block attributes.
	 * @param string|null          $wrapper_attributes Pre-built wrapper attributes string.
	 * @return string
	 */
	public static function render( array $attributes, ?string $wrapper_attributes = null ): string {
		$attrs = Attributes::sanitize( $attributes );
		$now   = self::now();

		$expiry_timestamp = '' !== $attrs['expiryDate'] ? Attributes::to_timestamp( $attrs['expiryDate'] ) : null;
		$remaining        = null === $expiry_timestamp ? null : max( 0, $expiry_timestamp - $now );
		$has_expired      = null !== $remaining && 0 === $remaining;

		if ( $has_expired && $attrs['hideWhenExpired'] ) {
			return '';
		}

		if ( null === $wrapper_attributes ) {
			$wrapper_attributes = sprintf( 'class="%s"', esc_attr( self::BASE_CLASS ) );
		}

		$markup  = '<div ' . $wrapper_attributes . '>';
		$markup .= '<div class="' . esc_attr( self::BASE_CLASS . '__inner' ) . '">';
		$markup .= self::render_content( $attrs, $remaining, $expiry_timestamp, $has_expired );
		$markup .= self::render_media( $attrs );
		$markup .= '</div></div>';

		return $markup;
	}

	/**
	 * Build the list of wrapper classes derived from the block attributes.
	 *
	 * @param array<string, mixed> $attributes Block attributes, sanitised or raw.
	 * @return string[]
	 */
	public static function wrapper_classes( array $attributes ): array {
		$attrs   = Attributes::sanitize( $attributes );
		$classes = array( 'is-size-' . $attrs['size'] );

		if ( '' !== $attrs['imageUrl'] || $attrs['imageId'] > 0 ) {
			$classes[] = 'has-cutout-image';
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
		$attrs  = Attributes::sanitize( $attributes );
		$styles = array();

		if ( '' !== $attrs['accentColor'] ) {
			$styles[] = '--gsfsh-accent:' . $attrs['accentColor'];
		}

		if ( 'center' !== $attrs['imagePosition'] ) {
			$styles[] = '--gsfsh-image-position:' . $attrs['imagePosition'];
		}

		return implode( ';', $styles );
	}

	/**
	 * Render the textual column: title, subtitle, countdown, CTA and fine print.
	 *
	 * @param array<string, mixed> $attrs            Sanitised attributes.
	 * @param int|null             $remaining        Seconds remaining, or null when no expiry is set.
	 * @param int|null             $expiry_timestamp UTC expiry timestamp, or null.
	 * @param bool                 $has_expired      Whether the offer has already ended.
	 * @return string
	 */
	private static function render_content( array $attrs, ?int $remaining, ?int $expiry_timestamp, bool $has_expired ): string {
		$base = self::BASE_CLASS;

		$markup = '<div class="' . esc_attr( $base . '__content' ) . '">';

		if ( '' !== $attrs['title'] ) {
			$markup .= '<h2 class="' . esc_attr( $base . '__title' ) . '">' . wp_kses_post( $attrs['title'] ) . '</h2>';
		}

		if ( '' !== $attrs['subtitle'] ) {
			$markup .= '<p class="' . esc_attr( $base . '__subtitle' ) . '">' . wp_kses_post( $attrs['subtitle'] ) . '</p>';
		}

		if ( null !== $expiry_timestamp ) {
			$markup .= self::render_countdown( $attrs, $remaining, $expiry_timestamp, $has_expired );
		}

		if ( '' !== $attrs['ctaText'] && '' !== $attrs['ctaUrl'] ) {
			$rel     = $attrs['ctaOpensInNewTab'] ? ' rel="noopener noreferrer" target="_blank"' : '';
			$markup .= sprintf(
				'<a class="%1$s" href="%2$s"%3$s>%4$s</a>',
				esc_attr( $base . '__cta' ),
				esc_url( $attrs['ctaUrl'] ),
				$rel,
				esc_html( $attrs['ctaText'] )
			);
		}

		if ( '' !== $attrs['finePrint'] ) {
			$markup .= '<p class="' . esc_attr( $base . '__fine-print' ) . '">' . wp_kses_post( $attrs['finePrint'] ) . '</p>';
		}

		$markup .= '</div>';

		return $markup;
	}

	/**
	 * Render the countdown, pre-filled with server-calculated values.
	 *
	 * @param array<string, mixed> $attrs            Sanitised attributes.
	 * @param int|null             $remaining        Seconds remaining.
	 * @param int                  $expiry_timestamp UTC expiry timestamp.
	 * @param bool                 $has_expired      Whether the offer has already ended.
	 * @return string
	 */
	private static function render_countdown( array $attrs, ?int $remaining, int $expiry_timestamp, bool $has_expired ): string {
		$base   = self::BASE_CLASS;
		$parts  = self::split_duration( (int) $remaining );
		$labels = self::unit_labels();

		$expired_message = '' !== $attrs['expiredMessage']
			? $attrs['expiredMessage']
			: __( 'This offer has ended.', 'flash-sale-header-block' );

		$markup = sprintf(
			'<div class="%1$s" data-gsfsh-countdown data-expiry="%2$s" data-server-now="%3$s" data-sync-url="%4$s"%5$s>',
			esc_attr( $base . '__countdown' ),
			esc_attr( (string) $expiry_timestamp ),
			esc_attr( (string) self::now() ),
			esc_url( self::sync_url() ),
			$attrs['hideWhenExpired'] ? ' data-hide-when-expired="1"' : ''
		);

		if ( '' !== $attrs['countdownLabel'] ) {
			$markup .= '<p class="' . esc_attr( $base . '__countdown-label' ) . '">' . esc_html( $attrs['countdownLabel'] ) . '</p>';
		}

		$markup .= sprintf(
			'<ul class="%1$s" role="list" data-gsfsh-units%2$s>',
			esc_attr( $base . '__units' ),
			$has_expired ? ' hidden' : ''
		);

		foreach ( $labels as $unit => $label ) {
			$markup .= sprintf(
				'<li class="%1$s"><span class="%2$s" data-gsfsh-unit="%3$s">%4$s</span><span class="%5$s">%6$s</span></li>',
				esc_attr( $base . '__unit' ),
				esc_attr( $base . '__unit-value' ),
				esc_attr( $unit ),
				esc_html( self::pad( $parts[ $unit ] ) ),
				esc_attr( $base . '__unit-label' ),
				esc_html( $label )
			);
		}

		$markup .= '</ul>';

		$markup .= sprintf(
			'<p class="%1$s" data-gsfsh-expired%2$s>%3$s</p>',
			esc_attr( $base . '__expired' ),
			$has_expired ? '' : ' hidden',
			esc_html( $expired_message )
		);

		$markup .= sprintf(
			'<p class="%3$s" aria-live="polite" data-gsfsh-announce data-announce-template="%1$s">%2$s</p>',
			esc_attr( self::announce_template() ),
			esc_html( self::describe_remaining( (int) $remaining, $has_expired, $expired_message ) ),
			esc_attr( $base . '__sr-only' )
		);

		$markup .= '</div>';

		return $markup;
	}

	/**
	 * Render the cutout image column.
	 *
	 * @param array<string, mixed> $attrs Sanitised attributes.
	 * @return string
	 */
	private static function render_media( array $attrs ): string {
		if ( 0 === $attrs['imageId'] && '' === $attrs['imageUrl'] ) {
			return '';
		}

		$base  = self::BASE_CLASS;
		$image = '';

		if ( $attrs['imageId'] > 0 && function_exists( 'wp_get_attachment_image' ) ) {
			$image = wp_get_attachment_image(
				$attrs['imageId'],
				'full',
				false,
				array(
					'class'    => $base . '__image',
					'alt'      => $attrs['imageAlt'],
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		}

		if ( '' === $image && '' !== $attrs['imageUrl'] ) {
			$image = sprintf(
				'<img class="%1$s" src="%2$s" alt="%3$s" loading="lazy" decoding="async" />',
				esc_attr( $base . '__image' ),
				esc_url( $attrs['imageUrl'] ),
				esc_attr( $attrs['imageAlt'] )
			);
		}

		if ( '' === $image ) {
			return '';
		}

		$hidden = '' === $attrs['imageAlt'] ? ' aria-hidden="true"' : '';

		return '<div class="' . esc_attr( $base . '__media' ) . '"' . $hidden . '>' . $image . '</div>';
	}

	/**
	 * Split a duration in seconds into days, hours, minutes and seconds.
	 *
	 * @param int $seconds Remaining seconds.
	 * @return array<string, int>
	 */
	public static function split_duration( int $seconds ): array {
		$seconds = max( 0, $seconds );

		return array(
			'days'    => (int) floor( $seconds / self::DAY ),
			'hours'   => (int) floor( ( $seconds % self::DAY ) / HOUR_IN_SECONDS ),
			'minutes' => (int) floor( ( $seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ),
			'seconds' => $seconds % MINUTE_IN_SECONDS,
		);
	}

	/**
	 * Translated labels for each countdown unit.
	 *
	 * @return array<string, string>
	 */
	public static function unit_labels(): array {
		return array(
			'days'    => __( 'Days', 'flash-sale-header-block' ),
			'hours'   => __( 'Hours', 'flash-sale-header-block' ),
			'minutes' => __( 'Mins', 'flash-sale-header-block' ),
			'seconds' => __( 'Secs', 'flash-sale-header-block' ),
		);
	}

	/**
	 * Translatable template for the screen reader announcement.
	 *
	 * @return string
	 */
	public static function announce_template(): string {
		return __(
			'Offer ends in {days} days, {hours} hours and {minutes} minutes.',
			'flash-sale-header-block'
		);
	}

	/**
	 * Build the sentence announced to assistive technology.
	 *
	 * @param int    $remaining       Seconds remaining.
	 * @param bool   $has_expired     Whether the offer has ended.
	 * @param string $expired_message Message shown once the offer has ended.
	 * @return string
	 */
	public static function describe_remaining( int $remaining, bool $has_expired, string $expired_message ): string {
		if ( $has_expired ) {
			return $expired_message;
		}

		$parts = self::split_duration( $remaining );

		return strtr(
			self::announce_template(),
			array(
				'{days}'    => (string) $parts['days'],
				'{hours}'   => (string) $parts['hours'],
				'{minutes}' => (string) $parts['minutes'],
			)
		);
	}

	/**
	 * Zero-pad a countdown value to at least two digits.
	 *
	 * @param int $value Value to pad.
	 * @return string
	 */
	private static function pad( int $value ): string {
		return str_pad( (string) max( 0, $value ), 2, '0', STR_PAD_LEFT );
	}

	/**
	 * URL the front-end can call to resolve clock skew.
	 *
	 * @return string
	 */
	private static function sync_url(): string {
		if ( ! function_exists( 'rest_url' ) ) {
			return '';
		}

		return (string) rest_url( REST_Controller::ROUTE_NAMESPACE . '/flash-sale/time' );
	}

	/**
	 * Current UTC timestamp.
	 *
	 * @return int
	 */
	private static function now(): int {
		/**
		 * Filters the timestamp used to calculate the remaining time.
		 *
		 * @since 1.0.0
		 *
		 * @param int $timestamp Current UTC timestamp.
		 */
		return (int) apply_filters( 'gsfsh_current_time', time() );
	}
}
