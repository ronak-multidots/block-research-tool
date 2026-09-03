/**
 * Shared Jest setup.
 */

import '@testing-library/jest-dom';

// jsdom implements neither API, and several @wordpress/components rely on them.
if ( ! window.matchMedia ) {
	window.matchMedia = ( query ) => ( {
		matches: false,
		media: query,
		onchange: null,
		addListener: () => {},
		removeListener: () => {},
		addEventListener: () => {},
		removeEventListener: () => {},
		dispatchEvent: () => false,
	} );
}

if ( ! window.ResizeObserver ) {
	window.ResizeObserver = class ResizeObserver {
		observe() {}
		unobserve() {}
		disconnect() {}
	};
}
