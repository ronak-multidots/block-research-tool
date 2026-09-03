/**
 * Tests for the front-end countdown hydration.
 *
 * The markup mirrors what `render.php` outputs so the two stay in step.
 */

const NOW = Date.UTC( 2030, 0, 1, 0, 0, 0 );
const ANNOUNCE_TEMPLATE =
	'Offer ends in {days} days, {hours} hours and {minutes} minutes.';

/**
 * Build server-rendered countdown markup.
 *
 * @param {Object}  options                 Options.
 * @param {number}  options.expirySeconds   Expiry timestamp in seconds.
 * @param {number}  options.serverNow       Server timestamp baked into the markup, in seconds.
 * @param {boolean} options.hideWhenExpired Whether the block removes itself when the offer ends.
 * @return {HTMLElement} The countdown element.
 */
function renderMarkup( {
	expirySeconds,
	serverNow = Math.floor( NOW / 1000 ),
	hideWhenExpired = false,
} ) {
	const units = [ 'days', 'hours', 'minutes', 'seconds' ]
		.map(
			( unit ) =>
				`<li><span data-gsfsh-unit="${ unit }">00</span><span>${ unit }</span></li>`
		)
		.join( '' );

	document.body.innerHTML = `
		<div class="wp-block-global-store-flash-sale-header">
			<div
				class="wp-block-global-store-flash-sale-header__countdown"
				data-gsfsh-countdown
				data-expiry="${ expirySeconds }"
				data-server-now="${ serverNow }"
				data-sync-url="https://example.test/wp-json/global-store/v1/flash-sale/time"
				${ hideWhenExpired ? 'data-hide-when-expired="1"' : '' }
			>
				<ul data-gsfsh-units>${ units }</ul>
				<p data-gsfsh-expired hidden>This offer has ended.</p>
				<p data-gsfsh-announce data-announce-template="${ ANNOUNCE_TEMPLATE }"></p>
			</div>
		</div>
	`;

	return document.querySelector( '[data-gsfsh-countdown]' );
}

/**
 * Read the rendered digits.
 *
 * @return {Object} Map of unit name to rendered text.
 */
function digits() {
	return Array.from(
		document.querySelectorAll( '[data-gsfsh-unit]' )
	).reduce(
		( carry, node ) => ( {
			...carry,
			[ node.getAttribute( 'data-gsfsh-unit' ) ]: node.textContent,
		} ),
		{}
	);
}

describe( 'view', () => {
	let initCountdown;

	beforeEach( () => {
		jest.useFakeTimers();
		jest.setSystemTime( NOW );
		jest.resetModules();
		document.body.innerHTML = '';

		// The module self-initialises on import, so load it per test.
		initCountdown =
			require( '../../src/flash-sale-header/view' ).initCountdown;
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete window.fetch;
	} );

	it( 'renders the remaining time on hydration', () => {
		const root = renderMarkup( {
			expirySeconds: Math.floor( NOW / 1000 ) + 3 * 86400 + 45296,
		} );

		initCountdown( root );

		expect( digits() ).toEqual( {
			days: '03',
			hours: '12',
			minutes: '34',
			seconds: '56',
		} );
	} );

	it( 'counts down once per second', () => {
		const root = renderMarkup( {
			expirySeconds: Math.floor( NOW / 1000 ) + 65,
		} );

		initCountdown( root );

		expect( digits().seconds ).toBe( '05' );
		expect( digits().minutes ).toBe( '01' );

		jest.advanceTimersByTime( 6000 );

		expect( digits().seconds ).toBe( '59' );
		expect( digits().minutes ).toBe( '00' );
	} );

	it( 'switches to the expired state when the timer runs out', () => {
		const root = renderMarkup( {
			expirySeconds: Math.floor( NOW / 1000 ) + 2,
		} );

		initCountdown( root );

		expect( root.querySelector( '[data-gsfsh-expired]' ).hidden ).toBe(
			true
		);

		jest.advanceTimersByTime( 3000 );

		expect( root.querySelector( '[data-gsfsh-units]' ).hidden ).toBe(
			true
		);
		expect( root.querySelector( '[data-gsfsh-expired]' ).hidden ).toBe(
			false
		);
		expect( digits() ).toEqual( {
			days: '00',
			hours: '00',
			minutes: '00',
			seconds: '00',
		} );
	} );

	it( 'stops ticking once the offer has ended', () => {
		const root = renderMarkup( {
			expirySeconds: Math.floor( NOW / 1000 ) + 1,
		} );

		initCountdown( root );
		jest.advanceTimersByTime( 5000 );

		expect( jest.getTimerCount() ).toBe( 0 );
	} );

	it( 'removes the block when the author asked for it to disappear', () => {
		const root = renderMarkup( {
			expirySeconds: Math.floor( NOW / 1000 ) + 1,
			hideWhenExpired: true,
		} );

		initCountdown( root );
		jest.advanceTimersByTime( 2000 );

		expect(
			document.querySelector( '.wp-block-global-store-flash-sale-header' )
		).toBeNull();
	} );

	it( 'announces the remaining time in whole minutes', () => {
		const root = renderMarkup( {
			expirySeconds:
				Math.floor( NOW / 1000 ) + 2 * 86400 + 3 * 3600 + 240,
		} );

		initCountdown( root );

		expect(
			root.querySelector( '[data-gsfsh-announce]' ).textContent
		).toBe( 'Offer ends in 2 days, 3 hours and 4 minutes.' );
	} );

	it( 'ignores markup without a usable expiry timestamp', () => {
		const root = renderMarkup( { expirySeconds: 'not-a-date' } );

		initCountdown( root );

		expect( digits().seconds ).toBe( '00' );
		expect( jest.getTimerCount() ).toBe( 0 );
	} );

	it( 'trusts the local clock when the rendered timestamp is fresh', () => {
		window.fetch = jest.fn();

		initCountdown(
			renderMarkup( { expirySeconds: Math.floor( NOW / 1000 ) + 120 } )
		);

		expect( window.fetch ).not.toHaveBeenCalled();
	} );

	it( 'resynchronises with the server when the page came from a cache', async () => {
		const serverSeconds = Math.floor( NOW / 1000 ) + 3600;

		window.fetch = jest.fn( () =>
			Promise.resolve( {
				ok: true,
				json: () => Promise.resolve( { timestamp: serverSeconds } ),
			} )
		);

		// The markup claims to have been generated two hours ago.
		const root = renderMarkup( {
			expirySeconds: serverSeconds + 61,
			serverNow: Math.floor( NOW / 1000 ) - 7200,
		} );

		initCountdown( root );

		expect( window.fetch ).toHaveBeenCalledWith(
			'https://example.test/wp-json/global-store/v1/flash-sale/time',
			{ credentials: 'omit', cache: 'no-store' }
		);

		// Let the fetch promise chain settle before the countdown starts.
		await jest.advanceTimersByTimeAsync( 1 );

		// Trusting the local clock would have left an hour on the timer.
		expect( digits().hours ).toBe( '00' );
		expect( digits().minutes ).toBe( '01' );
	} );
} );
