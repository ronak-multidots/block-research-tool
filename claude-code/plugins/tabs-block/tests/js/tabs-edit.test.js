import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

import Edit from '../../src/tabs/edit';

// Isolate edit.js from the real editor chrome, the same way tab-edit.test.js
// does. useInnerBlocksProps is swapped for a passthrough so we can assert on
// the synthesized nav (built from sibling Tab blocks' attributes) without
// booting the real InnerBlocks list.
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: ( props = {} ) => ( { ...props } ),
	useInnerBlocksProps: ( props = {} ) => ( { ...props } ),
	RichText: ( {
		tagName: Tag = 'span',
		value,
		onChange,
		placeholder,
		className,
	} ) => (
		<Tag
			className={ className }
			role="textbox"
			tabIndex={ 0 }
			onClick={ () => onChange( 'Updated title' ) }
		>
			{ value || placeholder }
		</Tag>
	),
	store: 'core/block-editor',
} ) );

const mockTabs = [
	{
		clientId: 'tab-1',
		attributes: {
			tabId: 'tab-one',
			title: 'Tab One',
			iconType: 'none',
			dashicon: '',
			imageUrl: '',
			imageAlt: '',
		},
	},
	{
		clientId: 'tab-2',
		attributes: {
			tabId: 'tab-two',
			title: 'Tab Two',
			iconType: 'dashicon',
			dashicon: 'dashicons-star-filled',
			imageUrl: '',
			imageAlt: '',
		},
	},
];

const mockUpdateBlockAttributes = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	useSelect: ( callback ) =>
		callback( () => ( {
			getBlocks: () => mockTabs,
		} ) ),
	useDispatch: () => ( { updateBlockAttributes: mockUpdateBlockAttributes } ),
} ) );

function setup( attributes = {} ) {
	const setAttributes = jest.fn();

	render(
		<Edit
			attributes={ { activeTabId: '', ...attributes } }
			setAttributes={ setAttributes }
			clientId="parent-client-id"
		/>
	);

	return { setAttributes };
}

beforeEach( () => {
	mockUpdateBlockAttributes.mockClear();
} );

describe( 'Tabs Edit', () => {
	it( 'renders a nav button for each sibling Tab block', () => {
		setup();

		expect(
			screen.getByRole( 'tab', { name: /Tab One/ } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'tab', { name: /Tab Two/ } )
		).toBeInTheDocument();
	} );

	it( 'marks the first tab active by default, when no tab has been explicitly selected', () => {
		setup();

		expect( screen.getByRole( 'tab', { name: /Tab One/ } ) ).toHaveClass(
			'is-active'
		);
		expect(
			screen.getByRole( 'tab', { name: /Tab Two/ } )
		).not.toHaveClass( 'is-active' );
	} );

	it( 'marks whichever tab matches activeTabId as active', () => {
		setup( { activeTabId: 'tab-two' } );

		expect(
			screen.getByRole( 'tab', { name: /Tab One/ } )
		).not.toHaveClass( 'is-active' );
		expect( screen.getByRole( 'tab', { name: /Tab Two/ } ) ).toHaveClass(
			'is-active'
		);
	} );

	it( 'updates activeTabId when a nav button is clicked', () => {
		const { setAttributes } = setup();

		fireEvent.click( screen.getByRole( 'tab', { name: /Tab Two/ } ) );

		expect( setAttributes ).toHaveBeenCalledWith( {
			activeTabId: 'tab-two',
		} );
	} );

	it( 'dispatches updateBlockAttributes on the child Tab block when its title is edited', () => {
		setup();

		fireEvent.click( screen.getByText( 'Tab One' ) );

		expect( mockUpdateBlockAttributes ).toHaveBeenCalledWith( 'tab-1', {
			title: 'Updated title',
		} );
	} );

	it( 'renders a Dashicon for a tab whose iconType is dashicon', () => {
		setup();

		const tabTwoButton = screen.getByRole( 'tab', { name: /Tab Two/ } );
		expect(
			tabTwoButton.querySelector( '.dashicons-star-filled' )
		).toBeInTheDocument();
	} );
} );
