import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

import Edit from '../../src/edit';

// `edit.js` is tested in isolation from the real editor chrome: InspectorControls
// normally renders into a Slot that only exists inside the full block editor, and
// RichText/MediaUpload rely on editor data stores that aren't set up here. Both are
// swapped for lightweight stand-ins so we can assert on the attribute-update
// contract (what `setAttributes` is called with) without booting the whole editor.
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: ( props = {} ) => ( { ...props } ),
	InspectorControls: ( { children } ) => <div>{ children }</div>,
	RichText: ( {
		tagName: Tag = 'div',
		value,
		onChange,
		placeholder,
		className,
	} ) => (
		<Tag
			className={ className }
			role="textbox"
			tabIndex={ 0 }
			onClick={ () => onChange( 'Updated text' ) }
		>
			{ value || placeholder }
		</Tag>
	),
	MediaUpload: ( { render: renderProp } ) =>
		renderProp( { open: jest.fn() } ),
	MediaUploadCheck: ( { children } ) => children,
} ) );

const defaultAttributes = {
	size: 'wide',
	title: 'The Flash Sale',
	subtitle: '£1 a month for 12 months',
	expiryDateTime: '',
	ctaText: 'Shop Now',
	ctaUrl: '',
	legalText: 'Offer ends soon.',
	imageId: 0,
	imageUrl: '',
	imageAlt: '',
};

function setup( overrides = {} ) {
	const setAttributes = jest.fn();
	const attributes = { ...defaultAttributes, ...overrides };
	render(
		<Edit attributes={ attributes } setAttributes={ setAttributes } />
	);
	return { setAttributes, attributes };
}

describe( 'Edit', () => {
	it( 'renders a control for each of the three layout sizes', () => {
		setup();

		expect(
			screen.getByRole( 'option', { name: 'Wide' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Medium' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Tall' } )
		).toBeInTheDocument();
	} );

	it( 'updates the size attribute when a different layout is chosen', () => {
		const { setAttributes } = setup();

		fireEvent.change( screen.getByLabelText( 'Size' ), {
			target: { value: 'medium' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( { size: 'medium' } );
	} );

	it( 'renders the current title value', () => {
		setup( { title: 'Big Sale' } );

		expect( screen.getByText( 'Big Sale' ) ).toBeInTheDocument();
	} );

	it( 'updates block state when the title field changes', () => {
		const { setAttributes } = setup();

		fireEvent.click( screen.getByText( 'The Flash Sale' ) );

		expect( setAttributes ).toHaveBeenCalledWith( {
			title: 'Updated text',
		} );
	} );

	it( 'hides the CTA preview on the wide layout', () => {
		setup( { size: 'wide', ctaText: 'Shop Now' } );

		expect( screen.queryByText( 'Shop Now' ) ).not.toBeInTheDocument();
	} );

	it( 'shows the CTA preview on the medium layout', () => {
		setup( { size: 'medium', ctaText: 'Shop Now' } );

		expect( screen.getByText( 'Shop Now' ) ).toBeInTheDocument();
	} );

	it( 'shows the CTA preview on the tall layout', () => {
		setup( { size: 'tall', ctaText: 'Shop Now' } );

		expect( screen.getByText( 'Shop Now' ) ).toBeInTheDocument();
	} );

	it( 'prompts for an expiry date when none is set', () => {
		setup();

		expect( screen.getByText( /Set an expiry date/i ) ).toBeInTheDocument();
	} );
} );
