/**
 * Framework-free helpers shared by the editor preview and the front-end countdown.
 *
 * Everything here is pure so it can be unit tested without a DOM or a WordPress runtime.
 */

export const SECOND = 1000;
export const MINUTE = 60 * SECOND;
export const HOUR = 60 * MINUTE;
export const DAY = 24 * HOUR;

/**
 * The layout sizes the block supports. `auto` hands the decision to container queries.
 *
 * @type {string[]}
 */
export const SIZES = [ 'auto', 'wide', 'medium', 'tall' ];

/**
 * Split a duration into whole days, hours, minutes and seconds.
 *
 * @param {number} milliseconds Duration in milliseconds. Negative values clamp to zero.
 * @return {{days: number, hours: number, minutes: number, seconds: number}} Duration parts.
 */
export function splitDuration( milliseconds ) {
	const remaining = Math.max( 0, Math.floor( milliseconds ) );

	return {
		days: Math.floor( remaining / DAY ),
		hours: Math.floor( ( remaining % DAY ) / HOUR ),
		minutes: Math.floor( ( remaining % HOUR ) / MINUTE ),
		seconds: Math.floor( ( remaining % MINUTE ) / SECOND ),
	};
}

/**
 * Zero-pad a countdown value to at least two digits.
 *
 * @param {number} value Value to pad.
 * @return {string} Padded value.
 */
export function pad( value ) {
	return String( Math.max( 0, Math.floor( value ) ) ).padStart( 2, '0' );
}

/**
 * Convert a `YYYY-MM-DDTHH:mm:ss` string expressed in the site timezone to a UTC timestamp.
 *
 * The DateTimePicker component emits wall-clock time without an offset, so interpreting it
 * with `new Date()` would silently use the visitor's timezone instead of the site's.
 *
 * @param {string} value         Datetime string, with or without an explicit offset.
 * @param {number} offsetMinutes Site timezone offset from UTC, in minutes.
 * @return {number|null} UTC timestamp in milliseconds, or null when the value is unusable.
 */
export function siteDateToUtcMs( value, offsetMinutes = 0 ) {
	if ( typeof value !== 'string' || value.trim() === '' ) {
		return null;
	}

	const trimmed = value.trim();
	const match = trimmed.match(
		/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?(Z|[+-]\d{2}:?\d{2})?$/
	);

	if ( ! match ) {
		return null;
	}

	const [
		,
		year,
		month,
		day,
		hours = '0',
		minutes = '0',
		seconds = '0',
		offset,
	] = match;

	if ( offset ) {
		const parsed = Date.parse( trimmed );
		return Number.isNaN( parsed ) ? null : parsed;
	}

	const utc = Date.UTC(
		Number( year ),
		Number( month ) - 1,
		Number( day ),
		Number( hours ),
		Number( minutes ),
		Number( seconds )
	);

	if ( Number.isNaN( utc ) ) {
		return null;
	}

	return utc - offsetMinutes * MINUTE;
}

/**
 * Normalise an unknown size value to a supported one.
 *
 * @param {unknown} size Candidate size.
 * @return {string} A supported size.
 */
export function normalizeSize( size ) {
	return typeof size === 'string' && SIZES.includes( size ) ? size : 'auto';
}
