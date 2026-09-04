/**
 * Editor implementation for the Global Store Tabs container block.
 */

import {
	BlockControls,
	InspectorControls,
	PanelColorSettings,
	RichText,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import {
	Button,
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { alignCenter, alignLeft, alignRight } from '@wordpress/icons';

import { TabsContext } from './context';
import { renderIcon } from './icons';
import { clampIndex } from './utils';

import './editor.scss';

const BASE_CLASS = 'wp-block-global-store-tabs';
const TAB_BLOCK = 'global-store/tab';

const MISSION_TEMPLATE = [
	[
		'core/columns',
		{ className: `${ BASE_CLASS }__media-row` },
		[
			[
				'core/column',
				{ width: '58%' },
				[
					[
						'core/heading',
						{
							level: 3,
							content: __( 'Serving People. Solving Problems.', 'tabs-block' ),
						},
					],
					[
						'core/paragraph',
						{
							content: __(
								'Our Mission: To serve people by solving problems that improve productivity, increase prosperity, and create peace of mind.',
								'tabs-block'
							),
						},
					],
				],
			],
			[
				'core/column',
				{ width: '42%' },
				[
					[
						'core/html',
						{
							content:
								'<svg class="wp-block-global-store-tabs__illustration" viewBox="0 0 240 220" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Rocket"><defs><linearGradient id="gstb-rocket" x1="18%" y1="92%" x2="86%" y2="8%"><stop offset="0" stop-color="#f4a574"/><stop offset="1" stop-color="#ee6c2d"/></linearGradient></defs><circle cx="44" cy="168" r="22" fill="url(#gstb-rocket)" opacity=".9"/><circle cx="198" cy="58" r="16" fill="url(#gstb-rocket)" opacity=".75"/><circle cx="188" cy="186" r="11" fill="url(#gstb-rocket)" opacity=".55"/><path fill="url(#gstb-rocket)" d="M86 168c10-28 32-62 62-88 18-16 38-26 54-30-3 17-12 38-28 56-26 30-62 52-88 62z"/><path fill="url(#gstb-rocket)" d="M86 168c8 4 16 6 22 6-2-8-6-18-12-28-8 6-10 14-10 22z"/><circle cx="152" cy="92" r="11" fill="#fff" opacity=".35"/></svg>',
						},
					],
				],
			],
		],
	],
];

const BLANK_PANEL = [
	[ 'core/heading', { level: 3 } ],
	[ 'core/paragraph' ],
];

const TEMPLATE = [
	[ TAB_BLOCK, { label: __( 'Our Mission', 'tabs-block' ), icon: 'target' }, MISSION_TEMPLATE ],
	[ TAB_BLOCK, { label: __( 'Our Superpowers', 'tabs-block' ), icon: 'bolt' }, BLANK_PANEL ],
	[ TAB_BLOCK, { label: __( 'What We Stand For', 'tabs-block' ), icon: 'sparkle' }, BLANK_PANEL ],
];

const ORIENTATION_OPTIONS = [
	{ value: 'horizontal', label: __( 'Horizontal', 'tabs-block' ) },
	{ value: 'vertical', label: __( 'Vertical', 'tabs-block' ) },
];

const TAB_STYLE_OPTIONS = [
	{ value: 'underline', label: __( 'Underline', 'tabs-block' ) },
	{ value: 'pills', label: __( 'Pills', 'tabs-block' ) },
];

const ALIGNMENT_OPTIONS = [
	{ value: 'start', label: __( 'Left', 'tabs-block' ) },
	{ value: 'center', label: __( 'Center', 'tabs-block' ) },
	{ value: 'end', label: __( 'Right', 'tabs-block' ) },
];

const ALIGNMENT_TOOLBAR = [
	{
		value: 'start',
		icon: alignLeft,
		label: __( 'Align tabs left', 'tabs-block' ),
	},
	{
		value: 'center',
		icon: alignCenter,
		label: __( 'Align tabs center', 'tabs-block' ),
	},
	{
		value: 'end',
		icon: alignRight,
		label: __( 'Align tabs right', 'tabs-block' ),
	},
];

/**
 * Fallback name for a tab the author has not titled yet.
 *
 * @param {number} index Tab position.
 * @return {string} Label.
 */
function placeholderLabel( index ) {
	return sprintf(
		/* translators: %d: position of the tab in the list, starting at 1. */
		__( 'Tab %d', 'tabs-block' ),
		index + 1
	);
}

/**
 * Block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {string}   props.clientId      Block client ID.
 * @return {JSX.Element} Editor markup.
 */
export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		defaultActiveTab,
		orientation,
		tabStyle,
		alignment,
		showIcons,
		accentColor,
		accessibleLabel,
	} = attributes;

	const { tabs, selectedTabIndex } = useSelect(
		( select ) => {
			const {
				getBlocks,
				getBlockIndex,
				getBlockName,
				getBlockParentsByBlockName,
				getBlockRootClientId,
				getSelectedBlockClientId,
			} = select( blockEditorStore );

			const selected = getSelectedBlockClientId();
			let index = null;

			if ( selected ) {
				const tabClientId =
					getBlockName( selected ) === TAB_BLOCK
						? selected
						: getBlockParentsByBlockName( selected, TAB_BLOCK, true )[ 0 ];

				// Tabs blocks can be nested, so only claim our own descendants.
				if ( tabClientId && getBlockRootClientId( tabClientId ) === clientId ) {
					index = getBlockIndex( tabClientId );
				}
			}

			return { tabs: getBlocks( clientId ), selectedTabIndex: index };
		},
		[ clientId ]
	);

	const { insertBlock, selectBlock, updateBlockAttributes } = useDispatch( blockEditorStore );

	const [ activeIndex, setActiveIndex ] = useState( () =>
		clampIndex( defaultActiveTab, tabs.length )
	);

	// Editing a block inside a hidden panel would be impossible, so follow the selection.
	useEffect( () => {
		if ( null !== selectedTabIndex && selectedTabIndex !== activeIndex ) {
			setActiveIndex( selectedTabIndex );
		}
	}, [ selectedTabIndex, activeIndex ] );

	// Keep the preview on a tab that still exists after one is removed.
	useEffect( () => {
		if ( activeIndex > tabs.length - 1 ) {
			setActiveIndex( Math.max( 0, tabs.length - 1 ) );
		}
	}, [ tabs.length, activeIndex ] );

	const context = useMemo( () => ( { activeIndex } ), [ activeIndex ] );

	const blockProps = useBlockProps( {
		className: [
			`is-orientation-${ orientation }`,
			`is-tab-style-${ tabStyle }`,
			`is-tabs-aligned-${ alignment }`,
			showIcons ? 'has-tab-icons' : '',
			accentColor ? 'has-custom-accent' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: accentColor ? { '--gstb-accent': accentColor } : undefined,
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: `${ BASE_CLASS }__panels` },
		{
			allowedBlocks: [ TAB_BLOCK ],
			template: TEMPLATE,
			orientation: 'vertical',
			// Tabs are added from the tab list, not from an appender under the panels.
			renderAppender: false,
		}
	);

	const activateTab = ( index ) => {
		setActiveIndex( index );

		if ( tabs[ index ] ) {
			selectBlock( tabs[ index ].clientId );
		}
	};

	const addTab = () => {
		const index = tabs.length;

		insertBlock(
			createBlock( TAB_BLOCK, {}, [
				createBlock( 'core/heading', { level: 3 } ),
				createBlock( 'core/paragraph' ),
			] ),
			index,
			clientId,
			false
		);

		setActiveIndex( index );
	};

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					{ ALIGNMENT_TOOLBAR.map( ( item ) => (
						<ToolbarButton
							key={ item.value }
							icon={ item.icon }
							label={ item.label }
							isPressed={ alignment === item.value }
							disabled={ orientation === 'vertical' }
							onClick={ () =>
								setAttributes( { alignment: item.value } )
							}
						/>
					) ) }
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'tabs-block' ) } initialOpen>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Orientation', 'tabs-block' ) }
						value={ orientation }
						options={ ORIENTATION_OPTIONS }
						onChange={ ( value ) => setAttributes( { orientation: value } ) }
					/>

					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Tab style', 'tabs-block' ) }
						value={ tabStyle }
						options={ TAB_STYLE_OPTIONS }
						onChange={ ( value ) => setAttributes( { tabStyle: value } ) }
					/>

					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Tab alignment', 'tabs-block' ) }
						value={ alignment }
						options={ ALIGNMENT_OPTIONS }
						disabled={ orientation === 'vertical' }
						help={
							orientation === 'vertical'
								? __(
										'Alignment applies to horizontal tab lists.',
										'tabs-block'
								  )
								: undefined
						}
						onChange={ ( value ) => setAttributes( { alignment: value } ) }
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show icons', 'tabs-block' ) }
						checked={ showIcons }
						help={ __(
							'Each tab picks its own icon from its block settings.',
							'tabs-block'
						) }
						onChange={ ( value ) => setAttributes( { showIcons: value } ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Behaviour', 'tabs-block' ) } initialOpen={ false }>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Tab open by default', 'tabs-block' ) }
						value={ String( clampIndex( defaultActiveTab, tabs.length ) ) }
						options={ tabs.map( ( tab, index ) => ( {
							value: String( index ),
							label: tab.attributes.label || placeholderLabel( index ),
						} ) ) }
						help={ __(
							'The tab visitors see first, before they choose one.',
							'tabs-block'
						) }
						onChange={ ( value ) =>
							setAttributes( {
								defaultActiveTab: clampIndex( value, tabs.length ),
							} )
						}
					/>

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Accessible label', 'tabs-block' ) }
						value={ accessibleLabel }
						placeholder={ __( 'Tabs', 'tabs-block' ) }
						help={ __(
							'Names the tab list for screen readers. Useful when a page has more than one set of tabs.',
							'tabs-block'
						) }
						onChange={ ( value ) => setAttributes( { accessibleLabel: value } ) }
					/>
				</PanelBody>

				<PanelColorSettings
					__experimentalIsRenderedInSidebar
					title={ __( 'Accent', 'tabs-block' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: accentColor,
							label: __( 'Active tab accent', 'tabs-block' ),
							onChange: ( value ) => setAttributes( { accentColor: value || '' } ),
						},
					] }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<div className={ `${ BASE_CLASS }__list` }>
					{ tabs.map( ( tab, index ) => {
						const isActive = index === activeIndex;
						const label = tab.attributes.label || '';
						const icon = showIcons
							? renderIcon( tab.attributes.icon, `${ BASE_CLASS }__icon` )
							: null;

						return (
							<div
								key={ tab.clientId }
								className={ `${ BASE_CLASS }__tab${ isActive ? ' is-active' : '' }` }
							>
								{ icon && (
									<span className={ `${ BASE_CLASS }__tab-icon` }>{ icon }</span>
								) }

								{ isActive ? (
									<RichText
										tagName="span"
										className={ `${ BASE_CLASS }__tab-label` }
										value={ label }
										allowedFormats={ [] }
										withoutInteractiveFormatting
										disableLineBreaks
										placeholder={ placeholderLabel( index ) }
										onChange={ ( value ) =>
											updateBlockAttributes( tab.clientId, { label: value } )
										}
									/>
								) : (
									<button
										type="button"
										className={ `${ BASE_CLASS }__tab-label ${ BASE_CLASS }__tab-switch` }
										onClick={ () => activateTab( index ) }
									>
										{ label || placeholderLabel( index ) }
									</button>
								) }
							</div>
						);
					} ) }

					<Button
						className={ `${ BASE_CLASS }__add-tab` }
						variant="tertiary"
						onClick={ addTab }
					>
						{ __( '+ Add tab', 'tabs-block' ) }
					</Button>
				</div>

				<TabsContext.Provider value={ context }>
					<div { ...innerBlocksProps } />
				</TabsContext.Provider>
			</div>
		</>
	);
}
