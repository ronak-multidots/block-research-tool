/**
 * Pure helpers shared between the editor preview (edit.js) and the
 * lightweight frontend countdown script (view.js).
 */

/**
 * Calculate the days/hours/minutes/seconds remaining until `expiry`.
 *
 * @param {string} expiry An ISO-8601 date/time string.
 * @return {{total: number, days: number, hours: number, minutes: number, seconds: number, expired: boolean}} The breakdown of time remaining.
 */
export function getTimeRemaining( expiry ) {
	const total = Date.parse( expiry ) - Date.now();
	const clamped = Math.max( total, 0 );

	const seconds = Math.floor( ( clamped / 1000 ) % 60 );
	const minutes = Math.floor( ( clamped / ( 1000 * 60 ) ) % 60 );
	const hours = Math.floor( ( clamped / ( 1000 * 60 * 60 ) ) % 24 );
	const days = Math.floor( clamped / ( 1000 * 60 * 60 * 24 ) );

	return {
		total: clamped,
		days,
		hours,
		minutes,
		seconds,
		expired: clamped <= 0,
	};
}
