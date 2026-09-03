/**
 * Unit tests for the shared countdown helpers.
 */

import {
	DAY,
	HOUR,
	MINUTE,
	normalizeSize,
	pad,
	siteDateToUtcMs,
	splitDuration,
} from '../../src/flash-sale-header/utils';

describe( 'splitDuration', () => {
	it( 'splits a duration into days, hours, minutes and seconds', () => {
		expect(
			splitDuration( 3 * DAY + 12 * HOUR + 48 * MINUTE + 56 * 1000 )
		).toEqual( {
			days: 3,
			hours: 12,
			minutes: 48,
			seconds: 56,
		} );
	} );

	it( 'clamps negative durations to zero', () => {
		expect( splitDuration( -5000 ) ).toEqual( {
			days: 0,
			hours: 0,
			minutes: 0,
			seconds: 0,
		} );
	} );

	it( 'keeps day counts above 99', () => {
		expect( splitDuration( 400 * DAY ).days ).toBe( 400 );
	} );
} );

describe( 'pad', () => {
	it( 'pads to two digits', () => {
		expect( pad( 0 ) ).toBe( '00' );
		expect( pad( 7 ) ).toBe( '07' );
	} );

	it( 'leaves longer values intact', () => {
		expect( pad( 365 ) ).toBe( '365' );
	} );

	it( 'never renders a negative value', () => {
		expect( pad( -3 ) ).toBe( '00' );
	} );
} );

describe( 'siteDateToUtcMs', () => {
	it( 'interprets a naive datetime in the site timezone', () => {
		// UTC+1: noon local is 11:00 UTC.
		expect( siteDateToUtcMs( '2030-06-01T12:00:00', 60 ) ).toBe(
			Date.UTC( 2030, 5, 1, 11, 0, 0 )
		);
	} );

	it( 'handles negative offsets', () => {
		expect( siteDateToUtcMs( '2030-06-01T12:00:00', -300 ) ).toBe(
			Date.UTC( 2030, 5, 1, 17, 0, 0 )
		);
	} );

	it( 'respects an explicit offset in the value', () => {
		expect( siteDateToUtcMs( '2030-06-01T12:00:00Z', 600 ) ).toBe(
			Date.UTC( 2030, 5, 1, 12, 0, 0 )
		);
	} );

	it( 'accepts a date without a time', () => {
		expect( siteDateToUtcMs( '2030-06-01', 0 ) ).toBe(
			Date.UTC( 2030, 5, 1, 0, 0, 0 )
		);
	} );

	it.each( [ '', '   ', 'tomorrow', '2030/06/01', null, undefined, 42 ] )(
		'returns null for unusable input: %p',
		( value ) => {
			expect( siteDateToUtcMs( value, 0 ) ).toBeNull();
		}
	);
} );

describe( 'normalizeSize', () => {
	it.each( [ 'auto', 'wide', 'medium', 'tall' ] )(
		'keeps the supported size %s',
		( size ) => {
			expect( normalizeSize( size ) ).toBe( size );
		}
	);

	it.each( [ 'huge', '', null, undefined, 5, [ 'wide' ] ] )(
		'falls back to auto for %p',
		( size ) => {
			expect( normalizeSize( size ) ).toBe( 'auto' );
		}
	);
} );
