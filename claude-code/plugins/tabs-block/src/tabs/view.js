/**
 * Frontend tab-switching behaviour, following the WAI-ARIA "Tabs (Automatic
 * Activation)" pattern: click or arrow-key through the tab list, the
 * matching panel is shown and the rest are hidden.
 *
 * @see https://www.w3.org/WAI/ARIA/apg/patterns/tabs/
 */

/**
 * Wire up one `.tabs-block` instance.
 *
 * @param {HTMLElement} root The `.tabs-block` wrapper element.
 */
function initTabs( root ) {
	const buttons = Array.from(
		root.querySelectorAll(
			':scope > .tabs-block__nav > .tabs-block__tab-btn'
		)
	);
	const panels = Array.from(
		root.querySelectorAll(
			':scope > .tabs-block__panels > .tabs-block__panel'
		)
	);

	if ( ! buttons.length || ! panels.length ) {
		return;
	}

	/**
	 * Activate the tab at `index`, updating both the nav buttons and panels.
	 *
	 * @param {number}  index         Index of the tab to activate.
	 * @param {Object}  options       Options.
	 * @param {boolean} options.focus Whether to move keyboard focus to the
	 *                                newly active tab button.
	 */
	function activate( index, { focus = false } = {} ) {
		buttons.forEach( ( button, i ) => {
			const isActive = i === index;
			button.classList.toggle( 'is-active', isActive );
			button.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			button.tabIndex = isActive ? 0 : -1;
		} );

		panels.forEach( ( panel, i ) => {
			const isActive = i === index;
			panel.classList.toggle( 'is-active', isActive );
			panel.hidden = ! isActive;
		} );

		if ( focus && buttons[ index ] ) {
			buttons[ index ].focus();
		}
	}

	buttons.forEach( ( button, index ) => {
		button.addEventListener( 'click', () => activate( index ) );

		button.addEventListener( 'keydown', ( event ) => {
			let nextIndex = null;

			if ( event.key === 'ArrowRight' ) {
				nextIndex = ( index + 1 ) % buttons.length;
			} else if ( event.key === 'ArrowLeft' ) {
				nextIndex = ( index - 1 + buttons.length ) % buttons.length;
			} else if ( event.key === 'Home' ) {
				nextIndex = 0;
			} else if ( event.key === 'End' ) {
				nextIndex = buttons.length - 1;
			}

			if ( nextIndex !== null ) {
				event.preventDefault();
				activate( nextIndex, { focus: true } );
			}
		} );
	} );

	const serverActiveIndex = buttons.findIndex( ( button ) =>
		button.classList.contains( 'is-active' )
	);

	activate( serverActiveIndex === -1 ? 0 : serverActiveIndex );
}

document
	.querySelectorAll( '.tabs-block' )
	.forEach( ( root ) => initTabs( root ) );
