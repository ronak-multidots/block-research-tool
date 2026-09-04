/**
 * Front-end behaviour for the tabs block.
 *
 * The markup already arrives fully rendered from `render.php` with the default panel
 * open, so this script only handles switching. It uses no framework and no WordPress
 * packages to keep the front-end payload small.
 */

import { clampIndex, nextIndex } from './utils';

const BLOCK_SELECTOR = '[data-gstb-tabs]';

/**
 * Wire up a single tabs block.
 *
 * @param {HTMLElement} root Block wrapper.
 * @return {void}
 */
function initTabs( root ) {
	const list = root.querySelector( '[role="tablist"]' );

	if ( ! list ) {
		return;
	}

	const tabs = Array.from( list.querySelectorAll( '[role="tab"]' ) );

	if ( tabs.length === 0 ) {
		return;
	}

	const panels = tabs.map( ( tab ) =>
		document.getElementById( tab.getAttribute( 'aria-controls' ) )
	);

	const selected = tabs.findIndex(
		( tab ) => tab.getAttribute( 'aria-selected' ) === 'true'
	);

	let activeIndex = clampIndex( selected < 0 ? 0 : selected, tabs.length );

	/**
	 * Open a tab.
	 *
	 * @param {number}  index              Tab to open.
	 * @param {Object}  options            Behaviour options.
	 * @param {boolean} options.focus      Whether to move focus onto the tab.
	 * @param {boolean} options.updateHash Whether to record the tab in the URL.
	 * @return {void}
	 */
	const activate = ( index, { focus = false, updateHash = false } = {} ) => {
		const target = clampIndex( index, tabs.length );

		tabs.forEach( ( tab, position ) => {
			const isActive = position === target;
			const panel = panels[ position ];

			tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			// Roving tabindex keeps the whole tab list to a single stop in the tab order.
			tab.setAttribute( 'tabindex', isActive ? '0' : '-1' );
			tab.classList.toggle( 'is-active', isActive );

			if ( panel ) {
				panel.hidden = ! isActive;
				panel.classList.toggle( 'is-active', isActive );
			}
		} );

		activeIndex = target;

		if ( focus ) {
			tabs[ target ].focus();
		}

		const slug = tabs[ target ].getAttribute( 'data-gstb-slug' );

		/*
		 * `replaceState` rather than assigning to `location.hash`: the latter jumps the
		 * viewport to the panel the visitor just opened.
		 */
		if ( updateHash && slug && window.history?.replaceState ) {
			window.history.replaceState( null, '', `#${ slug }` );
		}
	};

	tabs.forEach( ( tab, index ) => {
		tab.addEventListener( 'click', () => activate( index, { updateHash: true } ) );
	} );

	list.addEventListener( 'keydown', ( event ) => {
		const target = nextIndex( {
			key: event.key,
			current: activeIndex,
			total: tabs.length,
			orientation:
				list.getAttribute( 'aria-orientation' ) === 'vertical'
					? 'vertical'
					: 'horizontal',
			isRtl: document.documentElement.dir === 'rtl',
		} );

		if ( target === null ) {
			return;
		}

		event.preventDefault();
		activate( target, { focus: true, updateHash: true } );
	} );

	/**
	 * Open the tab a URL fragment points at.
	 *
	 * Element IDs change between requests, so deep links match the author-defined slug.
	 *
	 * @return {void}
	 */
	const applyHash = () => {
		const hash = window.location.hash.replace( /^#/, '' );

		if ( ! hash ) {
			return;
		}

		const index = tabs.findIndex(
			( tab ) => tab.getAttribute( 'data-gstb-slug' ) === hash
		);

		if ( index >= 0 ) {
			activate( index );
		}
	};

	applyHash();
	window.addEventListener( 'hashchange', applyHash );
}

/**
 * Initialise every tabs block on the page.
 *
 * @return {void}
 */
function init() {
	document.querySelectorAll( BLOCK_SELECTOR ).forEach( initTabs );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}

export { init, initTabs };
