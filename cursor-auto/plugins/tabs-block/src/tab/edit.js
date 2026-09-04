/**
 * Editor implementation for a single tab panel.
 *
 * The panel itself is just a canvas for whatever blocks the author drops into it; the
 * label and icon are drawn by the container block's tab list.
 */

import {
	InspectorControls,
	store as blockEditorStore,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { BaseControl, Button, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { TabsContext } from '../tabs/context';
import { ICON_NONE, iconLabel, iconNames, renderIcon } from '../tabs/icons';
import { slugify } from '../tabs/utils';

const BASE_CLASS = 'wp-block-global-store-tabs';

const TEMPLATE = [ [ 'core/paragraph' ] ];

/**
 * Tab panel edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @param {string}   props.clientId      Block client ID.
 * @return {JSX.Element} Editor markup.
 */
export default function TabEdit( { attributes, setAttributes, clientId } ) {
	const { label, icon, slug } = attributes;

	const index = useSelect(
		( select ) => select( blockEditorStore ).getBlockIndex( clientId ),
		[ clientId ]
	);

	const { activeIndex } = useContext( TabsContext );
	const isActive = index === activeIndex;

	const blockProps = useBlockProps( {
		className: `${ BASE_CLASS }__panel${ isActive ? ' is-active' : '' }`,
		hidden: ! isActive,
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: false,
	} );

	return (
		<>
			<InspectorControls>
				<BaseControl
					__nextHasNoMarginBottom
					id={ `gstb-icon-${ clientId }` }
					className={ `${ BASE_CLASS }__icon-picker` }
					label={ __( 'Tab icon', 'tabs-block' ) }
				>
					<div className={ `${ BASE_CLASS }__icon-grid` }>
						{ iconNames().map( ( name ) => (
							<Button
								key={ name }
								className={ `${ BASE_CLASS }__icon-choice` }
								isPressed={ name === icon }
								label={ iconLabel( name ) }
								showTooltip
								onClick={ () => setAttributes( { icon: name } ) }
							>
								{ name === ICON_NONE ? (
									<span aria-hidden="true">&mdash;</span>
								) : (
									renderIcon( name, `${ BASE_CLASS }__icon` )
								) }
							</Button>
						) ) }
					</div>
				</BaseControl>

				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Deep link slug', 'tabs-block' ) }
					value={ slug }
					placeholder={ slugify( label ) }
					help={ __(
						'Opens this tab when the page URL ends with #slug. Leave empty to skip.',
						'tabs-block'
					) }
					onChange={ ( value ) => setAttributes( { slug: slugify( value ) } ) }
				/>
			</InspectorControls>

			<div { ...innerBlocksProps } />
		</>
	);
}
