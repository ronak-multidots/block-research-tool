import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	RichText,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	DateTimePicker,
	Button,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

import { getTimeRemaining } from './utils';
import './editor.scss';

const SIZE_OPTIONS = [
	{ value: 'wide', label: __( 'Wide', 'global-store' ) },
	{ value: 'medium', label: __( 'Medium', 'global-store' ) },
	{ value: 'tall', label: __( 'Tall', 'global-store' ) },
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		size,
		title,
		subtitle,
		expiryDateTime,
		ctaText,
		ctaUrl,
		legalText,
		imageUrl,
		imageAlt,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `is-size-${ size }`,
	} );

	const [ remaining, setRemaining ] = useState( () =>
		getTimeRemaining( expiryDateTime || new Date().toISOString() )
	);

	useEffect( () => {
		if ( ! expiryDateTime ) {
			return;
		}

		setRemaining( getTimeRemaining( expiryDateTime ) );
		const intervalId = setInterval( () => {
			setRemaining( getTimeRemaining( expiryDateTime ) );
		}, 1000 );

		return () => clearInterval( intervalId );
	}, [ expiryDateTime ] );

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

	const showCta = size !== 'wide';

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'global-store' ) } initialOpen>
					<SelectControl
						label={ __( 'Size', 'global-store' ) }
						value={ size }
						options={ SIZE_OPTIONS }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						onChange={ ( value ) =>
							setAttributes( { size: value } )
						}
						help={ __(
							'The block also auto-adapts to its container width via CSS container queries.',
							'global-store'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Countdown', 'global-store' ) }
					initialOpen={ false }
				>
					<DateTimePicker
						currentDate={ expiryDateTime || undefined }
						onChange={ ( date ) =>
							setAttributes( { expiryDateTime: date } )
						}
						is12Hour
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Call to Action', 'global-store' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'Button text', 'global-store' ) }
						value={ ctaText }
						onChange={ ( value ) =>
							setAttributes( { ctaText: value } )
						}
					/>
					<TextControl
						label={ __( 'Button URL', 'global-store' ) }
						type="url"
						value={ ctaUrl }
						onChange={ ( value ) =>
							setAttributes( { ctaUrl: value } )
						}
						help={ __(
							'Shown on the medium and tall layouts only.',
							'global-store'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Cutout Image', 'global-store' ) }
					initialOpen={ false }
				>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImage }
							allowedTypes={ [ 'image' ] }
							value={ attributes.imageId }
							render={ ( { open } ) => (
								<div className="gsfsh-image-control">
									{ imageUrl ? (
										<>
											<img
												src={ imageUrl }
												alt={ imageAlt }
											/>
											<div className="gsfsh-image-control__actions">
												<Button
													variant="secondary"
													onClick={ open }
												>
													{ __(
														'Replace image',
														'global-store'
													) }
												</Button>
												<Button
													variant="tertiary"
													isDestructive
													onClick={ onRemoveImage }
												>
													{ __(
														'Remove',
														'global-store'
													) }
												</Button>
											</div>
										</>
									) : (
										<Button
											variant="primary"
											onClick={ open }
										>
											{ __(
												'Select cutout image',
												'global-store'
											) }
										</Button>
									) }
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="flash-sale-header__media">
					{ imageUrl ? (
						<img
							src={ imageUrl }
							alt={ imageAlt }
							className="flash-sale-header__image"
						/>
					) : (
						<div className="flash-sale-header__media-placeholder">
							{ __( 'Cutout image', 'global-store' ) }
						</div>
					) }
				</div>

				<div className="flash-sale-header__content">
					<RichText
						tagName="h2"
						className="flash-sale-header__title"
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
						placeholder={ __( 'The Flash Sale', 'global-store' ) }
						allowedFormats={ [] }
					/>
					<RichText
						tagName="p"
						className="flash-sale-header__subtitle"
						value={ subtitle }
						onChange={ ( value ) =>
							setAttributes( { subtitle: value } )
						}
						placeholder={ __(
							'£1 a month for 12 months',
							'global-store'
						) }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
					/>

					<div
						className="flash-sale-header__countdown"
						aria-hidden="true"
					>
						{ expiryDateTime ? (
							<>
								<div className="flash-sale-header__countdown-unit">
									<span className="flash-sale-header__countdown-value">
										{ remaining.days }
									</span>
									<span className="flash-sale-header__countdown-label">
										{ __( 'Days', 'global-store' ) }
									</span>
								</div>
								<div className="flash-sale-header__countdown-unit">
									<span className="flash-sale-header__countdown-value">
										{ remaining.hours }
									</span>
									<span className="flash-sale-header__countdown-label">
										{ __( 'Hrs', 'global-store' ) }
									</span>
								</div>
								<div className="flash-sale-header__countdown-unit">
									<span className="flash-sale-header__countdown-value">
										{ remaining.minutes }
									</span>
									<span className="flash-sale-header__countdown-label">
										{ __( 'Mins', 'global-store' ) }
									</span>
								</div>
								<div className="flash-sale-header__countdown-unit">
									<span className="flash-sale-header__countdown-value">
										{ remaining.seconds }
									</span>
									<span className="flash-sale-header__countdown-label">
										{ __( 'Secs', 'global-store' ) }
									</span>
								</div>
							</>
						) : (
							<p className="flash-sale-header__countdown-placeholder">
								{ __(
									'Set an expiry date to show a live countdown.',
									'global-store'
								) }
							</p>
						) }
					</div>

					{ showCta && (
						<Button
							className="flash-sale-header__cta is-editor-preview"
							variant="primary"
						>
							{ ctaText || __( 'Shop Now', 'global-store' ) }
						</Button>
					) }

					<RichText
						tagName="p"
						className="flash-sale-header__legal"
						value={ legalText }
						onChange={ ( value ) =>
							setAttributes( { legalText: value } )
						}
						placeholder={ __(
							'Fine print / legal disclaimer',
							'global-store'
						) }
						allowedFormats={ [ 'core/link' ] }
					/>
				</div>
			</div>
		</>
	);
}
