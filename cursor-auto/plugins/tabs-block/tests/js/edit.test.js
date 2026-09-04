/**
 * Tests for the tabs container editor.
 */

import { fireEvent, render, screen } from '@testing-library/react';

import Edit from '../../src/tabs/edit';

jest.mock(
	'@wordpress/block-editor',
	() => {
		const { createElement: h } = require( '@wordpress/element' );

		return {
			useBlockProps: ( props = {} ) => ( {
				...props,
				'data-testid': 'tabs-wrapper',
			} ),
			useInnerBlocksProps: ( props = {} ) => ( {
				...props,
				'data-testid': 'tab-panels',
			} ),
			InspectorControls: ( { children } ) =>
				h( 'div', { 'data-testid': 'inspector' }, children ),
			BlockControls: ( { children } ) =>
				h( 'div', { 'data-testid': 'block-controls' }, children ),
			PanelColorSettings: ( { colorSettings } ) =>
				h(
					'button',
					{
						type: 'button',
						onClick: () => colorSettings[ 0 ].onChange( '#ee6c2d' ),
					},
					'Set accent colour'
				),
			RichText: ( { value, onChange, placeholder } ) =>
				h( 'input', {
					value,
					placeholder,
					onChange: ( event ) => onChange( event.target.value ),
				} ),
			store: 'core/block-editor',
		};
	},
	{ virtual: true }
);

jest.mock(
	'@wordpress/components',
	() => {
		const { createElement: h } = require( '@wordpress/element' );

		return {
			Button: ( { children, onClick, disabled } ) =>
				h( 'button', { type: 'button', onClick, disabled }, children ),
			ToolbarGroup: ( { children } ) => h( 'div', null, children ),
			ToolbarButton: ( { label, onClick, disabled, isPressed } ) =>
				h(
					'button',
					{
						type: 'button',
						'aria-label': label,
						'aria-pressed': isPressed,
						onClick,
						disabled,
					},
					label
				),
			PanelBody: ( { children, title } ) =>
				h( 'div', null, title ? h( 'h2', null, title ) : null, children ),
			SelectControl: ( { label, value, options, onChange, disabled } ) =>
				h(
					'label',
					null,
					label,
					h(
						'select',
						{
							'aria-label': label,
							value,
							disabled,
							onChange: ( event ) => onChange( event.target.value ),
						},
						( options || [] ).map( ( option ) =>
							h(
								'option',
								{ key: option.value, value: option.value },
								option.label
							)
						)
					)
				),
			TextControl: ( { label, value, onChange } ) =>
				h(
					'label',
					null,
					label,
					h( 'input', {
						'aria-label': label,
						value,
						onChange: ( event ) => onChange( event.target.value ),
					} )
				),
			ToggleControl: ( { label, checked, onChange } ) =>
				h(
					'label',
					null,
					label,
					h( 'input', {
						type: 'checkbox',
						'aria-label': label,
						checked,
						onChange: ( event ) => onChange( event.target.checked ),
					} )
				),
		};
	},
	{ virtual: true }
);

jest.mock(
	'@wordpress/data',
	() => ( {
		useSelect: ( map ) =>
			map( () => ( {
				getBlocks: () => [
					{
						clientId: 'tab-1',
						attributes: { label: 'Our Mission', icon: 'target' },
					},
					{
						clientId: 'tab-2',
						attributes: {
							label: 'Our Superpowers',
							icon: 'bolt',
						},
					},
				],
				getBlockIndex: () => 0,
				getBlockName: () => 'global-store/tab',
				getBlockParentsByBlockName: () => [],
				getBlockRootClientId: () => 'tabs-1',
				getSelectedBlockClientId: () => 'tab-1',
			} ) ),
		useDispatch: () => ( {
			insertBlock: jest.fn(),
			selectBlock: jest.fn(),
			updateBlockAttributes: jest.fn(),
		} ),
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/blocks',
	() => ( {
		createBlock: jest.fn( ( name ) => ( { name, clientId: 'new-tab' } ) ),
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/icons',
	() => ( {
		alignLeft: 'align-left',
		alignCenter: 'align-center',
		alignRight: 'align-right',
	} ),
	{ virtual: true }
);

const DEFAULT_ATTRIBUTES = {
	defaultActiveTab: 0,
	orientation: 'horizontal',
	tabStyle: 'underline',
	alignment: 'center',
	showIcons: true,
	accentColor: '',
	accessibleLabel: '',
};

/**
 * Render the tabs editor.
 *
 * @param {Object} attributes Attribute overrides.
 * @return {Object} Render result and setter spy.
 */
function setup( attributes = {} ) {
	const setAttributes = jest.fn();
	const result = render(
		<Edit
			attributes={ { ...DEFAULT_ATTRIBUTES, ...attributes } }
			setAttributes={ setAttributes }
			clientId="tabs-1"
		/>
	);

	return { ...result, setAttributes };
}

describe( 'Tabs Edit', () => {
	it( 'renders alignment controls matching core/tabs', () => {
		setup();

		expect(
			screen.getByRole( 'button', { name: 'Align tabs left' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Align tabs center' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Align tabs right' } )
		).toBeInTheDocument();
	} );

	it.each( [
		[ 'Align tabs left', 'start' ],
		[ 'Align tabs center', 'center' ],
		[ 'Align tabs right', 'end' ],
	] )( 'stores %s as %s', ( label, alignment ) => {
		const { setAttributes } = setup();

		fireEvent.click( screen.getByRole( 'button', { name: label } ) );

		expect( setAttributes ).toHaveBeenCalledWith( { alignment } );
	} );

	it( 'applies the alignment class on the wrapper', () => {
		setup( { alignment: 'end' } );

		expect( screen.getByTestId( 'tabs-wrapper' ) ).toHaveClass(
			'is-tabs-aligned-end'
		);
	} );

	it( 'updates tab alignment from the inspector', () => {
		const { setAttributes } = setup();

		fireEvent.change( screen.getByLabelText( 'Tab alignment' ), {
			target: { value: 'start' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( { alignment: 'start' } );
	} );

	it( 'disables alignment when the list is vertical', () => {
		setup( { orientation: 'vertical' } );

		expect(
			screen.getByRole( 'button', { name: 'Align tabs left' } )
		).toBeDisabled();
		expect( screen.getByLabelText( 'Tab alignment' ) ).toBeDisabled();
	} );
} );
