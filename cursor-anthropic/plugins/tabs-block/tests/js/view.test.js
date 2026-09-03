/**
 * Unit tests for the front-end tab switching.
 */

import { initTabs } from '../../src/tabs/view';

const LABELS = [ 'Our Mission', 'Our Superpowers', 'What We Stand For' ];

/**
 * Build markup shaped like the output of `Renderer::render()`.
 *
 * @param {Object}   options             Markup options.
 * @param {string}   options.orientation Tab list orientation.
 * @param {string[]} options.slugs       Deep link slug for each tab.
 * @return {HTMLElement} The block wrapper, already initialised.
 */
function buildTabs( { orientation = 'horizontal', slugs = [] } = {} ) {
	const buttons = LABELS.map( ( label, index ) => {
		const slug = slugs[ index ] ? ` data-gstb-slug="${ slugs[ index ] }"` : '';

		return `<button type="button" class="tab" id="t-${ index }" role="tab"
			aria-controls="p-${ index }" aria-selected="${ index === 0 }"
			tabindex="${ index === 0 ? 0 : -1 }"${ slug }>${ label }</button>`;
	} ).join( '' );

	const panels = LABELS.map(
		( label, index ) =>
			`<div id="p-${ index }" role="tabpanel" aria-labelledby="t-${ index }" tabindex="0"${
				index === 0 ? '' : ' hidden'
			}><p>${ label }</p></div>`
	).join( '' );

	document.body.innerHTML = `<div data-gstb-tabs>
		<div role="tablist" aria-orientation="${ orientation }">${ buttons }</div>
		<div class="panels">${ panels }</div>
	</div>`;

	const root = document.querySelector( '[data-gstb-tabs]' );

	initTabs( root );

	return root;
}

/**
 * Read the index of the open tab.
 *
 * @return {number} Index, or -1 when nothing is open.
 */
function openTab() {
	return Array.from( document.querySelectorAll( '[role="tab"]' ) ).findIndex(
		( tab ) => tab.getAttribute( 'aria-selected' ) === 'true'
	);
}

/**
 * Press a key on the tab list.
 *
 * @param {string} key Value of `KeyboardEvent.key`.
 * @return {void}
 */
function pressKey( key ) {
	document
		.querySelector( '[role="tablist"]' )
		.dispatchEvent( new KeyboardEvent( 'keydown', { key, bubbles: true } ) );
}

describe( 'initTabs', () => {
	afterEach( () => {
		window.location.hash = '';
		document.documentElement.dir = 'ltr';
		document.body.innerHTML = '';
	} );

	it( 'leaves the server-rendered tab open', () => {
		buildTabs();

		expect( openTab() ).toBe( 0 );
		expect( document.getElementById( 'p-0' ).hidden ).toBe( false );
		expect( document.getElementById( 'p-1' ).hidden ).toBe( true );
	} );

	it( 'opens the tab that was clicked and closes the others', () => {
		buildTabs();

		document.getElementById( 't-2' ).click();

		expect( openTab() ).toBe( 2 );
		expect( document.getElementById( 'p-2' ).hidden ).toBe( false );
		expect( document.getElementById( 'p-0' ).hidden ).toBe( true );
	} );

	it( 'keeps the tab list to a single stop in the tab order', () => {
		buildTabs();

		document.getElementById( 't-1' ).click();

		expect( document.getElementById( 't-1' ).getAttribute( 'tabindex' ) ).toBe( '0' );
		expect( document.getElementById( 't-0' ).getAttribute( 'tabindex' ) ).toBe( '-1' );
	} );

	it( 'moves between tabs with the arrow keys and wraps around', () => {
		buildTabs();

		pressKey( 'ArrowRight' );
		expect( openTab() ).toBe( 1 );

		pressKey( 'ArrowLeft' );
		expect( openTab() ).toBe( 0 );

		pressKey( 'ArrowLeft' );
		expect( openTab() ).toBe( 2 );
	} );

	it( 'moves focus along with the selection', () => {
		buildTabs();

		pressKey( 'ArrowRight' );

		expect( document.activeElement ).toBe( document.getElementById( 't-1' ) );
	} );

	it( 'jumps to either end of the list', () => {
		buildTabs();

		pressKey( 'End' );
		expect( openTab() ).toBe( 2 );

		pressKey( 'Home' );
		expect( openTab() ).toBe( 0 );
	} );

	it( 'ignores the horizontal arrows on a vertical list', () => {
		buildTabs( { orientation: 'vertical' } );

		pressKey( 'ArrowRight' );
		expect( openTab() ).toBe( 0 );

		pressKey( 'ArrowDown' );
		expect( openTab() ).toBe( 1 );
	} );

	it( 'opens the tab a deep link points at', () => {
		window.location.hash = '#values';

		buildTabs( { slugs: [ 'mission', 'superpowers', 'values' ] } );

		expect( openTab() ).toBe( 2 );
	} );

	it( 'ignores a fragment that does not name a tab', () => {
		window.location.hash = '#somewhere-else';

		buildTabs( { slugs: [ 'mission', 'superpowers', 'values' ] } );

		expect( openTab() ).toBe( 0 );
	} );

	it( 'records the open tab in the URL without scrolling to it', () => {
		const replaceState = jest.spyOn( window.history, 'replaceState' );

		buildTabs( { slugs: [ 'mission', 'superpowers', 'values' ] } );

		document.getElementById( 't-1' ).click();

		expect( replaceState ).toHaveBeenCalledWith( null, '', '#superpowers' );

		replaceState.mockRestore();
	} );

	it( 'leaves the URL alone for a tab without a slug', () => {
		const replaceState = jest.spyOn( window.history, 'replaceState' );

		buildTabs();

		document.getElementById( 't-1' ).click();

		expect( replaceState ).not.toHaveBeenCalled();

		replaceState.mockRestore();
	} );

	it( 'does nothing when the markup has no tabs', () => {
		document.body.innerHTML =
			'<div data-gstb-tabs><div role="tablist"></div></div>';

		expect( () =>
			initTabs( document.querySelector( '[data-gstb-tabs]' ) )
		).not.toThrow();
	} );
} );
