/**
 * Framework-free helpers shared by the editor and the front-end script.
 */

/**
 * Clamp an index to a position that exists in a list.
 *
 * @param {number|string} index Requested index.
 * @param {number}        total Number of items.
 * @return {number} A valid index, or 0 when the list is empty.
 */
export function clampIndex( index, total ) {
	if ( ! total || total < 1 ) {
		return 0;
	}

	const parsed = Number.parseInt( index, 10 );

	if ( ! Number.isFinite( parsed ) ) {
		return 0;
	}

	return Math.min( Math.max( 0, parsed ), total - 1 );
}

/**
 * Turn a label into an anchor slug.
 *
 * Mirrors the subset of `sanitize_title()` that matters for deep links, so a slug
 * typed in the editor survives the round trip through PHP unchanged.
 *
 * @param {string} value Raw value.
 * @return {string} Slug, or an empty string when nothing usable remains.
 */
export function slugify( value ) {
	if ( typeof value !== 'string' ) {
		return '';
	}

	return value
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /[\u0300-\u036f]/g, '' )
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-+|-+$/g, '' );
}

/**
 * Work out which tab a key press should move to.
 *
 * Follows the ARIA tabs pattern: the arrows that run along the tab list move by one
 * and wrap around, Home and End jump to either end, and everything else is ignored so
 * the browser keeps its default behaviour.
 *
 * @param {Object}  options             Key handling options.
 * @param {string}  options.key         Value of `KeyboardEvent.key`.
 * @param {number}  options.current     Index of the tab that currently has focus.
 * @param {number}  options.total       Number of tabs.
 * @param {string}  options.orientation Either `horizontal` or `vertical`.
 * @param {boolean} options.isRtl       Whether the document runs right to left.
 * @return {number|null} Index to move to, or null when the key is not handled.
 */
export function nextIndex( {
	key,
	current,
	total,
	orientation = 'horizontal',
	isRtl = false,
} ) {
	if ( ! total || total < 1 ) {
		return null;
	}

	if ( key === 'Home' ) {
		return 0;
	}

	if ( key === 'End' ) {
		return total - 1;
	}

	const isVertical = orientation === 'vertical';
	const forwardKey = isVertical ? 'ArrowDown' : 'ArrowRight';
	const backwardKey = isVertical ? 'ArrowUp' : 'ArrowLeft';

	if ( key !== forwardKey && key !== backwardKey ) {
		return null;
	}

	// Along a horizontal list the arrows follow the reading direction, not the screen.
	const flip = ! isVertical && isRtl;
	const forward = flip ? key === backwardKey : key === forwardKey;
	const start = clampIndex( current, total );

	return ( start + ( forward ? 1 : -1 ) + total ) % total;
}
