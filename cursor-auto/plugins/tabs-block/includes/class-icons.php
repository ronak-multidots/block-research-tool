<?php
/**
 * Icon library shared by the tab list.
 *
 * @package GlobalStore\TabsBlock
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stroke icons that can be shown above (or beside) a tab label.
 *
 * The geometry is duplicated in `src/tabs/icons.js` because the editor draws the same
 * icons in React. `IconsTest` and `icons.test.js` both assert the icon names against the
 * enum in `src/tab/block.json`, so the two lists cannot drift apart unnoticed.
 */
final class Icons {

	/**
	 * Value stored when a tab should not show an icon.
	 */
	public const NONE = 'none';

	/**
	 * Every value the `icon` attribute accepts, including `none`.
	 *
	 * @return string[]
	 */
	public static function names(): array {
		return array_merge( array( self::NONE ), array_keys( self::paths() ) );
	}

	/**
	 * Whether a value names a known icon.
	 *
	 * @param string $name Icon name.
	 * @return bool
	 */
	public static function is_valid( string $name ): bool {
		return in_array( $name, self::names(), true );
	}

	/**
	 * Build the SVG for an icon.
	 *
	 * @param string $name       Icon name.
	 * @param string $class_name Class applied to the `<svg>` element.
	 * @return string Empty string for `none` and for unknown names.
	 */
	public static function markup( string $name, string $class_name = '' ): string {
		$paths = self::paths();

		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		$markup = sprintf(
			'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">',
			esc_attr( $class_name )
		);

		foreach ( $paths[ $name ] as $path ) {
			$markup .= '<path d="' . esc_attr( $path ) . '" />';
		}

		return $markup . '</svg>';
	}

	/**
	 * Path data for each icon, drawn on a 24x24 grid.
	 *
	 * Circles are expressed as arc pairs so every icon is a plain list of `d` strings,
	 * which keeps this table trivially portable to the editor bundle.
	 *
	 * @return array<string, string[]>
	 */
	private static function paths(): array {
		return array(
			'target'       => array(
				'M18.5 12.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z',
				'M14.5 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z',
				'm11 12.5 8.5-8',
				'M15 4.5h4.5V9',
			),
			'bolt'         => array(
				'M20.5 12a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z',
				'M12.9 7.1 9 12.6h3.2l-1.1 4.3 3.9-5.5h-3.2l1.1-4.3Z',
			),
			'compass'      => array(
				'M20.5 12a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z',
				'm15.4 8.6-2 4.8-4.8 2 2-4.8 4.8-2Z',
			),
			'rocket'       => array(
				'M13.6 4.4c2.7 1 4.9 3.2 5.9 5.9l-5.5 5.5-5.9-5.9 5.5-5.5Z',
				'M15.4 10a1.7 1.7 0 1 1-3.4 0 1.7 1.7 0 0 1 3.4 0Z',
				'm8.6 15.5-3.1 3.1',
				'm7.7 12.3-2.9.7.8 2.2',
				'm11.9 16.5-.7 2.9 2.2.8',
			),
			'star'         => array(
				'm12 4 2.5 5.2 5.7.8-4.1 4 1 5.6-5.1-2.7-5.1 2.7 1-5.6-4.1-4 5.7-.8L12 4Z',
			),
			'heart'        => array(
				'M12 19.6S4.4 15.1 4.4 9.9a3.9 3.9 0 0 1 7.6-1.7 3.9 3.9 0 0 1 7.6 1.7c0 5.2-7.6 9.7-7.6 9.7Z',
			),
			'shield'       => array(
				'M12 3.7 5.4 6.3v5.2c0 4 2.7 7.2 6.6 8.8 3.9-1.6 6.6-4.8 6.6-8.8V6.3L12 3.7Z',
				'm9.2 11.9 2.1 2.1 3.5-3.7',
			),
			'sparkle'      => array(
				'm9.8 3.8 1.6 4.4 4.4 1.6-4.4 1.6-1.6 4.4-1.6-4.4L3.8 9.8l4.4-1.6 1.6-4.4Z',
				'm17 13.8.9 2.3 2.3.9-2.3.9-.9 2.3-.9-2.3-2.3-.9 2.3-.9.9-2.3Z',
			),
			'lightbulb'    => array(
				'M12 3.6a5.6 5.6 0 0 0-3.3 10.1c.5.4.8 1 .8 1.7v.3h5v-.3c0-.7.3-1.3.8-1.7A5.6 5.6 0 0 0 12 3.6Z',
				'M9.7 18.2h4.6',
				'M10.5 20.4h3',
			),
			'users'        => array(
				'M12.5 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
				'M4 19.6c0-3 2.5-5.4 5.5-5.4s5.5 2.4 5.5 5.4',
				'M15.3 6.3a3 3 0 0 1 0 5.4',
				'M16.8 14.6c1.9.8 3.2 2.6 3.2 5',
			),
			'chart'        => array(
				'M4.5 19.5h15',
				'M7.6 16.4v-4.2',
				'M12 16.4V8.2',
				'M16.4 16.4v-6.3',
			),
			'check-circle' => array(
				'M20.5 12a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z',
				'm8.2 12.2 2.7 2.7 5-5.5',
			),
			'globe'        => array(
				'M20.5 12a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z',
				'M3.5 12h17',
				'M12 3.5c2.2 2.4 3.4 5.4 3.4 8.5S14.2 18.1 12 20.5c-2.2-2.4-3.4-5.4-3.4-8.5S9.8 5.9 12 3.5Z',
			),
			'clock'        => array(
				'M20.5 12a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z',
				'M12 7.3V12l3.1 2.1',
			),
			'layers'       => array(
				'm12 3.8 8.1 4.3-8.1 4.3-8.1-4.3L12 3.8Z',
				'm4.5 12.5 7.5 4 7.5-4',
				'm4.5 16.3 7.5 4 7.5-4',
			),
			'shopping-bag' => array(
				'M6.2 8.2h11.6l.9 11.3H5.3L6.2 8.2Z',
				'M9.2 10.4V7.2a2.8 2.8 0 0 1 5.6 0v3.2',
			),
		);
	}
}
