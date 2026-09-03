/**
 * Tests for the block's editor component.
 *
 * `@wordpress/block-editor` is replaced with light stand-ins: those components need a
 * full editor store to mount, and the behaviour under test belongs to this block, not
 * to the editor framework.
 */

import { act, fireEvent, render, screen, within } from '@testing-library/react';

import Edit from '../../src/flash-sale-header/edit';

// Declared virtual: the package is provided by WordPress at runtime, not installed here.
jest.mock(
	'@wordpress/block-editor',
	() => {
		const { createElement: h } = require( '@wordpress/element' );

		return {
			useBlockProps: ( props = {} ) => ( {
				...props,
				'data-testid': 'block-wrapper',
			} ),
			InspectorControls: ( { children } ) =>
				h( 'div', { 'data-testid': 'inspector' }, children ),
			BlockControls: ( { children } ) =>
				h( 'div', { 'data-testid': 'block-controls' }, children ),
			RichText: ( {
				value,
				onChange,
				className,
				placeholder,
				'aria-label': ariaLabel,
			} ) =>
				h( 'textarea', {
					className,
					placeholder,
					'aria-label': ariaLabel,
					value,
					onChange: ( event ) => onChange( event.target.value ),
				} ),
			MediaPlaceholder: ( { onSelect } ) =>
				h(
					'button',
					{
						type: 'button',
						onClick: () =>
							onSelect( {
								id: 99,
								url: 'https://example.com/cutout.png',
								alt: 'Party leaders',
							} ),
					},
					'Select image'
				),
			MediaReplaceFlow: ( { onSelect } ) =>
				h(
					'button',
					{
						type: 'button',
						onClick: () =>
							onSelect( {
								id: 100,
								url: 'https://example.com/replacement.png',
								alt: 'Replacement',
							} ),
					},
					'Replace image'
				),
			PanelColorSettings: ( { colorSettings } ) =>
				h(
					'button',
					{
						type: 'button',
						onClick: () => colorSettings[ 0 ].onChange( '#ff0000' ),
					},
					'Set accent colour'
				),
		};
	},
	{ virtual: true }
);

// Pin the site timezone to UTC so the countdown maths is deterministic.
jest.mock( '@wordpress/date', () => {
	const actual = jest.requireActual( '@wordpress/date' );

	return {
		...actual,
		getSettings: () => ( {
			...actual.getSettings(),
			timezone: { offset: '0', string: 'UTC', abbr: 'UTC' },
		} ),
	};
} );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

// eslint-disable-next-line import/order
import apiFetch from '@wordpress/api-fetch';

const DEFAULT_ATTRIBUTES = {
	size: 'auto',
	title: 'The Flash Sale',
	subtitle: '£1 a month for 12 months',
	countdownLabel: 'Offer ends in',
	expiryDate: '',
	expiredMessage: 'This offer has ended.',
	hideWhenExpired: false,
	ctaText: 'Subscribe now',
	ctaUrl: '',
	ctaOpensInNewTab: false,
	finePrint: '',
	imageId: 0,
	imageUrl: '',
	imageAlt: '',
	imagePosition: 'center',
	accentColor: '',
};

/**
 * Render the edit component with sensible defaults.
 *
 * @param {Object} attributes Attribute overrides.
 * @return {Object} Testing Library render result plus the setAttributes spy.
 */
function setup( attributes = {} ) {
	const setAttributes = jest.fn();
	const result = render(
		<Edit
			attributes={ { ...DEFAULT_ATTRIBUTES, ...attributes } }
			setAttributes={ setAttributes }
		/>
	);

	return { ...result, setAttributes };
}

/**
 * Open a collapsed inspector panel.
 *
 * @param {string} name Panel title.
 */
function openPanel( name ) {
	fireEvent.click( screen.getByRole( 'button', { name } ) );
}

describe( 'Edit', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		jest.setSystemTime( Date.UTC( 2030, 0, 1, 0, 0, 0 ) );
		apiFetch.mockReset();
		apiFetch.mockResolvedValue( {
			timestamp: 0,
			isPast: false,
			secondsRemaining: 10,
			formatted: '1 January 2030 00:00',
		} );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	describe( 'inspector controls', () => {
		it( 'renders every size option', () => {
			setup();

			const select = screen.getByLabelText( 'Size' );

			expect( select ).toHaveValue( 'auto' );
			expect(
				Array.from( select.options ).map( ( option ) => option.value )
			).toEqual( [ 'auto', 'wide', 'medium', 'tall' ] );
		} );

		it.each( [ 'wide', 'medium', 'tall' ] )(
			'updates the size attribute to %s',
			( size ) => {
				const { setAttributes } = setup();

				fireEvent.change( screen.getByLabelText( 'Size' ), {
					target: { value: size },
				} );

				expect( setAttributes ).toHaveBeenCalledWith( { size } );
			}
		);

		it( 'ignores an unsupported size coming from the markup', () => {
			setup( { size: 'enormous' } );

			expect( screen.getByLabelText( 'Size' ) ).toHaveValue( 'auto' );
			expect( screen.getByTestId( 'block-wrapper' ) ).toHaveClass(
				'is-size-auto'
			);
		} );

		it( 'renders the countdown and call to action fields', () => {
			setup();

			expect( screen.getByLabelText( 'Countdown label' ) ).toHaveValue(
				'Offer ends in'
			);
			expect( screen.getByLabelText( 'Expired message' ) ).toHaveValue(
				'This offer has ended.'
			);

			openPanel( 'Call to action' );

			expect( screen.getByLabelText( 'Button label' ) ).toHaveValue(
				'Subscribe now'
			);
			expect( screen.getByLabelText( 'Button URL' ) ).toHaveValue( '' );
		} );

		it( 'updates the call to action attributes', () => {
			const { setAttributes } = setup();

			openPanel( 'Call to action' );

			fireEvent.change( screen.getByLabelText( 'Button URL' ), {
				target: { value: 'https://example.com/subscribe' },
			} );

			expect( setAttributes ).toHaveBeenCalledWith( {
				ctaUrl: 'https://example.com/subscribe',
			} );

			fireEvent.click( screen.getByLabelText( 'Open in a new tab' ) );

			expect( setAttributes ).toHaveBeenCalledWith( {
				ctaOpensInNewTab: true,
			} );
		} );

		it( 'toggles hiding the block once the offer ends', () => {
			const { setAttributes } = setup();

			fireEvent.click(
				screen.getByLabelText( 'Hide the block once the offer ends' )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				hideWhenExpired: true,
			} );
		} );

		it( 'stores the accent colour', () => {
			const { setAttributes } = setup();

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Set accent colour' } )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				accentColor: '#ff0000',
			} );
		} );
	} );

	describe( 'content fields', () => {
		it( 'renders the editable text fields with their values', () => {
			setup( { finePrint: 'New subscribers only.' } );

			expect( screen.getByLabelText( 'Header title' ) ).toHaveValue(
				'The Flash Sale'
			);
			expect( screen.getByLabelText( 'Offer details' ) ).toHaveValue(
				'£1 a month for 12 months'
			);
			expect( screen.getByLabelText( 'Fine print' ) ).toHaveValue(
				'New subscribers only.'
			);
		} );

		it.each( [
			[ 'Header title', 'title', 'Winter Flash Sale' ],
			[ 'Offer details', 'subtitle', '£2 a month' ],
			[ 'Call to action label', 'ctaText', 'Join now' ],
			[ 'Fine print', 'finePrint', 'Terms apply.' ],
		] )( 'updates %s', ( label, attribute, value ) => {
			const { setAttributes } = setup();

			fireEvent.change( screen.getByLabelText( label ), {
				target: { value },
			} );

			expect( setAttributes ).toHaveBeenCalledWith( {
				[ attribute ]: value,
			} );
		} );
	} );

	describe( 'countdown preview', () => {
		it( 'prompts for an expiry date when none is set', () => {
			setup();

			expect(
				screen.getByText(
					'Set an expiry date in the block sidebar to show the countdown.'
				)
			).toBeInTheDocument();
			expect(
				screen.queryByTestId( 'countdown-units' )
			).not.toBeInTheDocument();
		} );

		it( 'renders the remaining time from the expiry date', () => {
			setup( { expiryDate: '2030-01-04T12:48:56' } );

			const units = screen.getByTestId( 'countdown-units' );

			expect( units ).toHaveTextContent( '03Days' );
			expect( units ).toHaveTextContent( '12Hours' );
			expect( units ).toHaveTextContent( '48Mins' );
			expect( units ).toHaveTextContent( '56Secs' );
		} );

		it( 'ticks every second', async () => {
			setup( { expiryDate: '2030-01-01T00:01:00' } );

			expect( screen.getByTestId( 'countdown-units' ) ).toHaveTextContent(
				'01Mins00Secs'
			);

			// Async so the debounced validation request settles inside act().
			await act( async () => {
				jest.advanceTimersByTime( 5000 );
			} );

			expect( screen.getByTestId( 'countdown-units' ) ).toHaveTextContent(
				'00Mins55Secs'
			);
		} );

		it( 'shows the expired message for a date in the past', () => {
			setup( { expiryDate: '2029-01-01T00:00:00' } );

			expect(
				screen.getByTestId( 'countdown-expired' )
			).toHaveTextContent( 'This offer has ended.' );
			expect(
				screen.queryByTestId( 'countdown-units' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'cutout image', () => {
		it( 'stores the selected media', () => {
			const { setAttributes } = setup();

			openPanel( 'Cutout image' );
			fireEvent.click(
				screen.getByRole( 'button', { name: 'Select image' } )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				imageId: 99,
				imageUrl: 'https://example.com/cutout.png',
				imageAlt: 'Party leaders',
			} );
		} );

		it( 'clears the media', () => {
			const { setAttributes } = setup( {
				imageId: 5,
				imageUrl: 'https://example.com/cutout.png',
				imageAlt: 'Party leaders',
			} );

			openPanel( 'Cutout image' );
			fireEvent.click(
				screen.getByRole( 'button', { name: 'Remove image' } )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				imageId: 0,
				imageUrl: '',
				imageAlt: '',
			} );
		} );

		it( 'offers a replace flow only once an image exists', () => {
			setup();
			expect(
				screen.queryByRole( 'button', { name: 'Replace image' } )
			).not.toBeInTheDocument();

			setup( { imageUrl: 'https://example.com/cutout.png' } );
			expect(
				screen.getAllByRole( 'button', { name: 'Replace image' } )
			).toHaveLength( 1 );
		} );
	} );

	describe( 'wrapper', () => {
		it( 'reflects the size, image and accent state in the markup', () => {
			setup( {
				size: 'wide',
				imageUrl: 'https://example.com/cutout.png',
				accentColor: '#ff0000',
				imagePosition: 'top',
			} );

			const wrapper = screen.getByTestId( 'block-wrapper' );

			expect( wrapper ).toHaveClass(
				'is-size-wide',
				'has-cutout-image',
				'has-custom-accent'
			);
			expect( wrapper ).toHaveStyle( { '--gsfsh-accent': '#ff0000' } );
			expect( wrapper ).toHaveStyle( {
				'--gsfsh-image-position': 'top',
			} );
		} );
	} );

	describe( 'expiry validation', () => {
		it( 'does not call the server without an expiry date', () => {
			setup();

			act( () => {
				jest.advanceTimersByTime( 1000 );
			} );

			expect( apiFetch ).not.toHaveBeenCalled();
		} );

		it( 'asks the server to validate the expiry date', async () => {
			setup( { expiryDate: '2030-01-04T12:48:56' } );

			await act( async () => {
				jest.advanceTimersByTime( 500 );
			} );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: '/global-store/v1/flash-sale/validate-expiry',
				method: 'POST',
				data: { expiryDate: '2030-01-04T12:48:56' },
			} );
		} );

		it( 'warns when the server reports the offer already ended', async () => {
			apiFetch.mockResolvedValue( {
				isPast: true,
				formatted: '1 January 2029 00:00',
			} );

			const { container } = setup( {
				expiryDate: '2029-01-01T00:00:00',
			} );

			await act( async () => {
				jest.advanceTimersByTime( 500 );
			} );

			expect(
				within( container ).getByText(
					'This offer already ended on 1 January 2029 00:00.'
				)
			).toBeInTheDocument();
		} );

		it( 'stays quiet when the request fails', async () => {
			apiFetch.mockRejectedValue( new Error( 'Network down' ) );

			const { container } = setup( {
				expiryDate: '2030-01-04T12:48:56',
			} );

			await act( async () => {
				jest.advanceTimersByTime( 500 );
			} );

			expect(
				within( container ).queryByText( /already ended/ )
			).not.toBeInTheDocument();
		} );
	} );
} );
