import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

import Edit from '../../src/tab/edit';

// Isolate edit.js from the real editor chrome: InspectorControls normally
// renders into a Slot that only exists inside the full block editor, and
// InnerBlocks/MediaUpload rely on editor data stores that aren't set up
// here. Both are swapped for lightweight stand-ins so we can assert on the
// attribute-update contract (what `setAttributes` is called with) without
// booting the whole editor.
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: ( props = {} ) => ( { ...props } ),
	useInnerBlocksProps: ( props = {} ) => ( { ...props } ),
	InspectorControls: ( { children } ) => <div>{ children }</div>,
	MediaUpload: ( { render: renderProp } ) =>
		renderProp( { open: jest.fn() } ),
	MediaUploadCheck: ( { children } ) => children,
	store: 'core/block-editor',
} ) );

// @wordpress/components is mocked below with plain form-control stand-ins
// (rather than rendering the real components), specifically so this file
// doesn't need to pull in real @wordpress/data at all — real
// @wordpress/components transitively needs @wordpress/rich-text's data
// store internally, which would fight with mocking @wordpress/data below.
jest.mock( '@wordpress/components', () => ( {
	PanelBody: ( { children } ) => <div>{ children }</div>,
	SelectControl: ( { label, value, options, onChange } ) => (
		<>
			<label htmlFor={ label }>{ label }</label>
			<select
				id={ label }
				value={ value }
				onChange={ ( event ) => onChange( event.target.value ) }
			>
				{ options.map( ( option ) => (
					<option key={ option.value } value={ option.value }>
						{ option.label }
					</option>
				) ) }
			</select>
		</>
	),
	TextControl: ( { label, value, onChange, help } ) => (
		<>
			<label htmlFor={ label }>{ label }</label>
			<input
				id={ label }
				value={ value }
				onChange={ ( event ) => onChange( event.target.value ) }
			/>
			{ help && <span>{ help }</span> }
		</>
	),
	Button: ( { children, onClick } ) => (
		<button type="button" onClick={ onClick }>
			{ children }
		</button>
	),
} ) );

let mockBlockIndex = 0;

jest.mock( '@wordpress/data', () => ( {
	useSelect: ( callback ) =>
		callback( () => ( {
			getBlockIndex: () => mockBlockIndex,
		} ) ),
} ) );

const defaultAttributes = {
	tabId: 'tab-abc123',
	title: 'My Tab',
	iconType: 'none',
	dashicon: '',
	imageId: 0,
	imageUrl: '',
	imageAlt: '',
};

function setup( { attributes = {}, context = {}, blockIndex = 0 } = {} ) {
	mockBlockIndex = blockIndex;
	const setAttributes = jest.fn();
	const mergedAttributes = { ...defaultAttributes, ...attributes };

	const { container } = render(
		<Edit
			attributes={ mergedAttributes }
			setAttributes={ setAttributes }
			clientId="0123456789abcdef"
			context={ context }
		/>
	);

	return { setAttributes, container };
}

describe( 'Tab Edit', () => {
	it( 'generates a stable tabId from clientId when none is saved yet', () => {
		const { setAttributes } = setup( { attributes: { tabId: '' } } );

		expect( setAttributes ).toHaveBeenCalledWith( {
			tabId: 'tab-01234567',
		} );
	} );

	it( 'does not regenerate tabId once one is already saved', () => {
		const { setAttributes } = setup();

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'shows the dashicon class field only when icon type is dashicon', () => {
		setup( { attributes: { iconType: 'none' } } );
		expect(
			screen.queryByLabelText( 'Dashicon class' )
		).not.toBeInTheDocument();
	} );

	it( 'updates the dashicon attribute from the icon type field', () => {
		const { setAttributes } = setup( {
			attributes: { iconType: 'dashicon' },
		} );

		fireEvent.change( screen.getByLabelText( 'Dashicon class' ), {
			target: { value: 'dashicons-star-filled' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			dashicon: 'dashicons-star-filled',
		} );
	} );

	it( 'shows a "Select image" action when icon type is image and none is set', () => {
		setup( { attributes: { iconType: 'image' } } );

		expect(
			screen.getByRole( 'button', { name: 'Select image' } )
		).toBeInTheDocument();
	} );

	it( 'marks the panel active when it is the first child and no context is set', () => {
		const { container } = setup( { blockIndex: 0, context: {} } );

		expect(
			container.querySelector( '.tabs-block__panel' )
		).not.toHaveClass( 'is-tab-inactive' );
	} );

	it( 'marks the panel inactive when it is not the first child and no context is set', () => {
		const { container } = setup( { blockIndex: 1, context: {} } );

		expect( container.querySelector( '.tabs-block__panel' ) ).toHaveClass(
			'is-tab-inactive'
		);
	} );

	it( 'marks the panel active when its tabId matches the active-tab context', () => {
		const { container } = setup( {
			attributes: { tabId: 'tab-xyz' },
			blockIndex: 1,
			context: { 'global-store/activeTabId': 'tab-xyz' },
		} );

		expect(
			container.querySelector( '.tabs-block__panel' )
		).not.toHaveClass( 'is-tab-inactive' );
	} );

	it( 'marks the panel inactive when the active-tab context points elsewhere', () => {
		const { container } = setup( {
			attributes: { tabId: 'tab-xyz' },
			blockIndex: 0,
			context: { 'global-store/activeTabId': 'tab-other' },
		} );

		expect( container.querySelector( '.tabs-block__panel' ) ).toHaveClass(
			'is-tab-inactive'
		);
	} );
} );
