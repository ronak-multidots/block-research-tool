import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	Button,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

import './editor.scss';

const ICON_TYPE_OPTIONS = [
	{ value: 'none', label: __( 'None', 'global-store-tabs' ) },
	{ value: 'dashicon', label: __( 'Dashicon', 'global-store-tabs' ) },
	{ value: 'image', label: __( 'Custom image', 'global-store-tabs' ) },
];

export default function Edit( {
	attributes,
	setAttributes,
	clientId,
	context,
} ) {
	const { tabId, iconType, dashicon, imageUrl, imageAlt, imageId } =
		attributes;

	// Every tab needs a stable id (used to link the parent's nav button to
	// this panel via aria-controls/aria-labelledby). Generate one once, the
	// moment the block exists, from its own clientId.
	useEffect( () => {
		if ( ! tabId ) {
			setAttributes( { tabId: `tab-${ clientId.slice( 0, 8 ) }` } );
		}
	}, [ tabId, clientId, setAttributes ] );

	const isFirstChild = useSelect(
		( select ) =>
			select( blockEditorStore ).getBlockIndex( clientId ) === 0,
		[ clientId ]
	);

	const activeTabId = context[ 'global-store/activeTabId' ];
	const isActive = activeTabId ? activeTabId === tabId : isFirstChild;

	const blockProps = useBlockProps( {
		className: `tabs-block__panel${ isActive ? '' : ' is-tab-inactive' }`,
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		templateLock: false,
	} );

	const onSelectImage = ( media ) => {
		setAttributes( {
			imageId: media.id,
			imageUrl: media.url,
			imageAlt: media.alt || '',
		} );
	};

	const onRemoveImage = () => {
		setAttributes( { imageId: 0, imageUrl: '', imageAlt: '' } );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Tab Icon', 'global-store-tabs' ) }
					initialOpen
				>
					<SelectControl
						label={ __( 'Icon type', 'global-store-tabs' ) }
						value={ iconType }
						options={ ICON_TYPE_OPTIONS }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						onChange={ ( value ) =>
							setAttributes( { iconType: value } )
						}
					/>

					{ iconType === 'dashicon' && (
						<TextControl
							label={ __(
								'Dashicon class',
								'global-store-tabs'
							) }
							value={ dashicon }
							onChange={ ( value ) =>
								setAttributes( { dashicon: value } )
							}
							placeholder="dashicons-star-filled"
							help={ __(
								'The full Dashicons class, e.g. "dashicons-star-filled". Browse the list at developer.wordpress.org/resource/dashicons/',
								'global-store-tabs'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }

					{ iconType === 'image' && (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ onSelectImage }
								allowedTypes={ [ 'image' ] }
								value={ imageId }
								render={ ( { open } ) => (
									<div className="tabs-block-icon-control">
										{ imageUrl ? (
											<>
												<img
													src={ imageUrl }
													alt={ imageAlt }
												/>
												<Button
													variant="secondary"
													onClick={ open }
												>
													{ __(
														'Replace image',
														'global-store-tabs'
													) }
												</Button>
												<Button
													variant="tertiary"
													isDestructive
													onClick={ onRemoveImage }
												>
													{ __(
														'Remove',
														'global-store-tabs'
													) }
												</Button>
											</>
										) : (
											<Button
												variant="primary"
												onClick={ open }
											>
												{ __(
													'Select image',
													'global-store-tabs'
												) }
											</Button>
										) }
									</div>
								) }
							/>
						</MediaUploadCheck>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...innerBlocksProps } />
		</>
	);
}
