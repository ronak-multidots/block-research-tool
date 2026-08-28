import { getTimeRemaining } from '../../src/utils';

describe( 'getTimeRemaining', () => {
	it( 'returns zeroed, expired values for a date in the past', () => {
		const result = getTimeRemaining( '2000-01-01T00:00:00Z' );

		expect( result.expired ).toBe( true );
		expect( result.days ).toBe( 0 );
		expect( result.hours ).toBe( 0 );
		expect( result.minutes ).toBe( 0 );
		expect( result.seconds ).toBe( 0 );
	} );

	it( 'calculates the correct breakdown for a future date', () => {
		const future = new Date(
			Date.now() +
				2 * 24 * 60 * 60 * 1000 +
				3 * 60 * 60 * 1000 +
				4 * 60 * 1000 +
				30 * 1000
		);
		const result = getTimeRemaining( future.toISOString() );

		expect( result.expired ).toBe( false );
		expect( result.days ).toBe( 2 );
		expect( result.hours ).toBe( 3 );
		expect( result.minutes ).toBe( 4 );
	} );

	it( 'never returns a negative total', () => {
		const result = getTimeRemaining( '1990-01-01T00:00:00Z' );

		expect( result.total ).toBeGreaterThanOrEqual( 0 );
	} );
} );
