/**
 * Front-end hydration for the Flash Sale Header countdown.
 *
 * The markup already arrives fully rendered from `render.php`; this script only keeps
 * the digits ticking. It uses no framework and no WordPress packages so the front-end
 * payload stays in the low single-digit kilobytes.
 */

import { pad, splitDuration } from './utils';

const COUNTDOWN_SELECTOR = '[data-gsfsh-countdown]';
const UNIT_SELECTOR = '[data-gsfsh-unit]';

/**
 * Difference between the server clock and this browser's clock, in milliseconds.
 *
 * @type {number}
 */
let clockOffset = 0;

/**
 * Shared promise so several blocks on one page trigger at most one sync request.
 *
 * @type {Promise<void>|null}
 */
let syncRequest = null;

/**
 * A skew smaller than this is not worth a network round trip.
 */
const SKEW_TOLERANCE_MS = 60 * 1000;

/**
 * Current time according to the server.
 *
 * @return {number} Timestamp in milliseconds.
 */
function serverNow() {
	return Date.now() + clockOffset;
}

/**
 * Ask the server for its clock when the rendered timestamp looks untrustworthy.
 *
 * `data-server-now` is baked into the HTML, so a cached page reports a time in the
 * past. Rather than guessing whether the page or the visitor's clock is wrong, the
 * script confirms with a single uncached request.
 *
 * @param {string} url Endpoint URL.
 * @return {Promise<void>} Resolves once the offset has been applied, or immediately on failure.
 */
function syncClock( url ) {
	if ( syncRequest ) {
		return syncRequest;
	}

	if ( ! url || typeof window.fetch !== 'function' ) {
		return Promise.resolve();
	}

	const requestedAt = Date.now();

	syncRequest = window
		.fetch( url, { credentials: 'omit', cache: 'no-store' } )
		.then( ( response ) => ( response.ok ? response.json() : null ) )
		.then( ( payload ) => {
			if ( ! payload || typeof payload.timestamp !== 'number' ) {
				return;
			}

			// Assume the response took half the round trip to reach us.
			const latency = ( Date.now() - requestedAt ) / 2;
			clockOffset = payload.timestamp * 1000 + latency - Date.now();
		} )
		.catch( () => {
			/* A failed sync is not fatal: the local clock remains the fallback. */
		} );

	return syncRequest;
}

/**
 * Fill the translated screen reader template with the current values.
 *
 * The template is rendered by PHP so the strings stay translatable without shipping
 * the i18n runtime to the front-end.
 *
 * @param {string} template Template containing `{days}`, `{hours}` and `{minutes}` tokens.
 * @param {Object} parts    Duration parts.
 * @return {string} Announcement text.
 */
function formatAnnouncement( template, parts ) {
	return template
		.replace( '{days}', String( parts.days ) )
		.replace( '{hours}', String( parts.hours ) )
		.replace( '{minutes}', String( parts.minutes ) );
}

/**
 * Wire up a single countdown element.
 *
 * @param {HTMLElement} root Countdown container.
 * @return {void}
 */
function initCountdown( root ) {
	const expirySeconds = Number.parseInt(
		root.getAttribute( 'data-expiry' ),
		10
	);

	if ( ! Number.isFinite( expirySeconds ) ) {
		return;
	}

	const expiryMs = expirySeconds * 1000;
	const units = {};

	root.querySelectorAll( UNIT_SELECTOR ).forEach( ( node ) => {
		units[ node.getAttribute( 'data-gsfsh-unit' ) ] = node;
	} );

	const unitsList = root.querySelector( '[data-gsfsh-units]' );
	const expiredNotice = root.querySelector( '[data-gsfsh-expired]' );
	const announcer = root.querySelector( '[data-gsfsh-announce]' );
	const announceTemplate = announcer
		? announcer.getAttribute( 'data-announce-template' )
		: '';
	const hideWhenExpired =
		root.getAttribute( 'data-hide-when-expired' ) === '1';

	let timer = null;
	let lastAnnouncedMinute = null;
	let finished = false;

	const showExpired = () => {
		finished = true;

		if ( timer ) {
			window.clearTimeout( timer );
			timer = null;
		}

		if ( hideWhenExpired ) {
			const block = root.closest(
				'.wp-block-global-store-flash-sale-header'
			);

			if ( block ) {
				block.remove();
				return;
			}
		}

		if ( unitsList ) {
			unitsList.hidden = true;
		}

		if ( expiredNotice ) {
			expiredNotice.hidden = false;

			if ( announcer ) {
				announcer.textContent = expiredNotice.textContent;
			}
		}
	};

	const tick = () => {
		const now = serverNow();
		const remaining = expiryMs - now;

		if ( remaining <= 0 ) {
			Object.values( units ).forEach( ( node ) => {
				node.textContent = '00';
			} );
			showExpired();
			return;
		}

		const parts = splitDuration( remaining );

		Object.keys( parts ).forEach( ( unit ) => {
			const node = units[ unit ];
			const value = pad( parts[ unit ] );

			if ( node && node.textContent !== value ) {
				node.textContent = value;
			}
		} );

		// The digits change every second; announcing that often would be unusable.
		if (
			announcer &&
			announceTemplate &&
			lastAnnouncedMinute !== parts.minutes
		) {
			lastAnnouncedMinute = parts.minutes;
			announcer.textContent = formatAnnouncement(
				announceTemplate,
				parts
			);
		}

		// Re-align to the next whole second instead of letting setInterval drift.
		timer = window.setTimeout( tick, 1000 - ( now % 1000 ) );
	};

	const start = () => {
		if ( finished ) {
			return;
		}

		if ( timer ) {
			window.clearTimeout( timer );
		}

		tick();
	};

	// Stop burning CPU while the tab is in the background, then resync on return.
	document.addEventListener( 'visibilitychange', () => {
		if ( document.hidden ) {
			if ( timer ) {
				window.clearTimeout( timer );
				timer = null;
			}
		} else {
			start();
		}
	} );

	const renderedNow =
		Number.parseInt( root.getAttribute( 'data-server-now' ), 10 ) * 1000;
	const skew = Number.isFinite( renderedNow )
		? Math.abs( renderedNow - Date.now() )
		: 0;

	if ( skew > SKEW_TOLERANCE_MS ) {
		syncClock( root.getAttribute( 'data-sync-url' ) ).then( start );
	} else {
		start();
	}
}

/**
 * Initialise every countdown on the page.
 *
 * @return {void}
 */
function init() {
	document.querySelectorAll( COUNTDOWN_SELECTOR ).forEach( initCountdown );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}

export { init, initCountdown };
