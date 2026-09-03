/**
 * Unit tests for the shared helpers.
 */

import { clampIndex, nextIndex, slugify } from '../../src/tabs/utils';

describe( 'clampIndex', () => {
	it( 'keeps an index that is already inside the list', () => {
		expect( clampIndex( 1, 3 ) ).toBe( 1 );
	} );

	it( 'pulls an index past the end back to the last item', () => {
		expect( clampIndex( 9, 3 ) ).toBe( 2 );
	} );

	it( 'pulls a negative index up to the first item', () => {
		expect( clampIndex( -4, 3 ) ).toBe( 0 );
	} );

	it( 'accepts the strings that come out of a select control', () => {
		expect( clampIndex( '2', 4 ) ).toBe( 2 );
	} );

	it( 'returns 0 for values that are not numbers', () => {
		expect( clampIndex( 'nope', 4 ) ).toBe( 0 );
		expect( clampIndex( undefined, 4 ) ).toBe( 0 );
	} );

	it( 'returns 0 for an empty list', () => {
		expect( clampIndex( 2, 0 ) ).toBe( 0 );
	} );
} );

describe( 'slugify', () => {
	it( 'lowercases and joins words with hyphens', () => {
		expect( slugify( 'Our Mission' ) ).toBe( 'our-mission' );
	} );

	it( 'drops punctuation', () => {
		expect( slugify( 'What We Stand For!' ) ).toBe( 'what-we-stand-for' );
	} );

	it( 'strips accents rather than the letters carrying them', () => {
		expect( slugify( 'Café Crème' ) ).toBe( 'cafe-creme' );
	} );

	it( 'trims the hyphens it would otherwise leave at either end', () => {
		expect( slugify( '  -- pricing -- ' ) ).toBe( 'pricing' );
	} );

	it( 'returns an empty string when nothing usable remains', () => {
		expect( slugify( '///' ) ).toBe( '' );
		expect( slugify( null ) ).toBe( '' );
	} );
} );

describe( 'nextIndex', () => {
	const horizontal = { current: 1, total: 3, orientation: 'horizontal' };

	it( 'moves forward and backward along a horizontal list', () => {
		expect( nextIndex( { ...horizontal, key: 'ArrowRight' } ) ).toBe( 2 );
		expect( nextIndex( { ...horizontal, key: 'ArrowLeft' } ) ).toBe( 0 );
	} );

	it( 'wraps around at both ends', () => {
		expect( nextIndex( { ...horizontal, current: 2, key: 'ArrowRight' } ) ).toBe( 0 );
		expect( nextIndex( { ...horizontal, current: 0, key: 'ArrowLeft' } ) ).toBe( 2 );
	} );

	it( 'jumps to either end of the list', () => {
		expect( nextIndex( { ...horizontal, key: 'Home' } ) ).toBe( 0 );
		expect( nextIndex( { ...horizontal, key: 'End' } ) ).toBe( 2 );
	} );

	it( 'follows the reading direction in a right to left document', () => {
		expect( nextIndex( { ...horizontal, key: 'ArrowRight', isRtl: true } ) ).toBe( 0 );
		expect( nextIndex( { ...horizontal, key: 'ArrowLeft', isRtl: true } ) ).toBe( 2 );
	} );

	it( 'ignores the vertical arrows on a horizontal list', () => {
		expect( nextIndex( { ...horizontal, key: 'ArrowDown' } ) ).toBeNull();
		expect( nextIndex( { ...horizontal, key: 'ArrowUp' } ) ).toBeNull();
	} );

	it( 'uses the vertical arrows on a vertical list', () => {
		const vertical = { current: 1, total: 3, orientation: 'vertical' };

		expect( nextIndex( { ...vertical, key: 'ArrowDown' } ) ).toBe( 2 );
		expect( nextIndex( { ...vertical, key: 'ArrowUp' } ) ).toBe( 0 );
		expect( nextIndex( { ...vertical, key: 'ArrowRight' } ) ).toBeNull();
	} );

	it( 'leaves the vertical arrows alone in a right to left document', () => {
		const vertical = { current: 1, total: 3, orientation: 'vertical', isRtl: true };

		expect( nextIndex( { ...vertical, key: 'ArrowDown' } ) ).toBe( 2 );
	} );

	it( 'ignores keys it does not handle', () => {
		expect( nextIndex( { ...horizontal, key: 'a' } ) ).toBeNull();
		expect( nextIndex( { ...horizontal, key: 'Enter' } ) ).toBeNull();
	} );

	it( 'has nowhere to go in an empty list', () => {
		expect( nextIndex( { key: 'ArrowRight', current: 0, total: 0 } ) ).toBeNull();
	} );
} );
