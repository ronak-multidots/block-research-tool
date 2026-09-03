/**
 * Unit tests for the editor icon library.
 */

import { render } from '@testing-library/react';

import blockMetadata from '../../src/tab/block.json';
import { ICON_NONE, iconLabel, iconNames, renderIcon } from '../../src/tabs/icons';

describe( 'iconNames', () => {
	it( 'offers "no icon" first', () => {
		expect( iconNames()[ 0 ] ).toBe( ICON_NONE );
	} );

	/*
	 * The front end draws these icons from `includes/class-icons.php`. Both lists are
	 * checked against the block metadata so neither can gain an icon on its own.
	 */
	it( 'matches the enum the block metadata advertises', () => {
		expect( iconNames() ).toEqual( blockMetadata.attributes.icon.enum );
	} );
} );

describe( 'iconLabel', () => {
	it( 'names every icon it offers', () => {
		iconNames().forEach( ( name ) => {
			expect( iconLabel( name ) ).not.toBe( name );
		} );
	} );

	it( 'falls back to the raw name for an icon it does not know', () => {
		expect( iconLabel( 'unicorn' ) ).toBe( 'unicorn' );
	} );
} );

describe( 'renderIcon', () => {
	it( 'draws nothing for "no icon" or an unknown name', () => {
		expect( renderIcon( ICON_NONE ) ).toBeNull();
		expect( renderIcon( 'unicorn' ) ).toBeNull();
	} );

	it( 'draws a decorative svg carrying the class it was given', () => {
		const { container } = render( renderIcon( 'target', 'my-icon' ) );
		const svg = container.querySelector( 'svg' );

		expect( svg ).toHaveClass( 'my-icon' );
		expect( svg ).toHaveAttribute( 'viewBox', '0 0 24 24' );
		expect( svg ).toHaveAttribute( 'aria-hidden', 'true' );
		expect( svg.querySelectorAll( 'path' ).length ).toBeGreaterThan( 0 );
	} );

	it( 'gives every icon some path data', () => {
		iconNames()
			.filter( ( name ) => name !== ICON_NONE )
			.forEach( ( name ) => {
				const { container } = render( renderIcon( name ) );

				expect( container.querySelectorAll( 'path' ).length ).toBeGreaterThan( 0 );
			} );
	} );
} );
