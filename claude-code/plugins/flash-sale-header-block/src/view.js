import { getTimeRemaining } from './utils';

/**
 * Frontend countdown hydration. Kept dependency-free (besides the pure
 * `getTimeRemaining` helper) so the compiled viewScript bundle stays tiny.
 */

function formatUnit( value ) {
	return String( value ).padStart( 2, '0' );
}

function initCountdown( el ) {
	const expiry = el.getAttribute( 'data-expiry' );

	if ( ! expiry ) {
		return;
	}

	const unitEls = {
		days: el.querySelector(
			'[data-unit="days"] .flash-sale-header__countdown-value'
		),
		hours: el.querySelector(
			'[data-unit="hours"] .flash-sale-header__countdown-value'
		),
		minutes: el.querySelector(
			'[data-unit="minutes"] .flash-sale-header__countdown-value'
		),
		seconds: el.querySelector(
			'[data-unit="seconds"] .flash-sale-header__countdown-value'
		),
	};

	const tick = () => {
		const remaining = getTimeRemaining( expiry );

		if ( unitEls.days ) {
			unitEls.days.textContent = formatUnit( remaining.days );
		}
		if ( unitEls.hours ) {
			unitEls.hours.textContent = formatUnit( remaining.hours );
		}
		if ( unitEls.minutes ) {
			unitEls.minutes.textContent = formatUnit( remaining.minutes );
		}
		if ( unitEls.seconds ) {
			unitEls.seconds.textContent = formatUnit( remaining.seconds );
		}

		if ( remaining.expired ) {
			el.classList.add( 'is-expired' );
			clearInterval( intervalId );
		}
	};

	const intervalId = setInterval( tick, 1000 );
	tick();
}

function init() {
	document
		.querySelectorAll( '[data-flash-sale-countdown]' )
		.forEach( initCountdown );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
