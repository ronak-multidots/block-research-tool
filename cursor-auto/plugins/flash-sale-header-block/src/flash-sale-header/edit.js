/**
 * Editor implementation for the Global Store Flash Sale Header block.
 */

import apiFetch from '@wordpress/api-fetch';
import {
	BlockControls,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	MediaReplaceFlow,
	PanelColorSettings,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	DateTimePicker,
	Notice,
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	ToolbarGroup,
} from '@wordpress/components';
import { getSettings as getDateSettings } from '@wordpress/date';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { normalizeSize, pad, siteDateToUtcMs, splitDuration } from './utils';

import './editor.scss';

const BASE_CLASS = 'wp-block-global-store-flash-sale-header';

const SIZE_OPTIONS = [
	{
		value: 'auto',
		label: __( 'Auto (container query)', 'flash-sale-header-block' ),
	},
	{ value: 'wide', label: __( 'Wide', 'flash-sale-header-block' ) },
	{ value: 'medium', label: __( 'Medium', 'flash-sale-header-block' ) },
	{ value: 'tall', label: __( 'Tall', 'flash-sale-header-block' ) },
];

const IMAGE_POSITION_OPTIONS = [
	{ value: 'center', label: __( 'Center', 'flash-sale-header-block' ) },
	{ value: 'top', label: __( 'Top', 'flash-sale-header-block' ) },
	{ value: 'bottom', label: __( 'Bottom', 'flash-sale-header-block' ) },
	{ value: 'left', label: __( 'Left', 'flash-sale-header-block' ) },
	{ value: 'right', label: __( 'Right', 'flash-sale-header-block' ) },
];

const UNIT_LABELS = {
	days: __( 'Days', 'flash-sale-header-block' ),
	hours: __( 'Hours', 'flash-sale-header-block' ),
	minutes: __( 'Mins', 'flash-sale-header-block' ),
	seconds: __( 'Secs', 'flash-sale-header-block' ),
};

/**
 * Read the site timezone offset, in minutes, from the editor's date settings.
 *
 * @return {number} Offset in minutes.
 */
function getSiteOffsetMinutes() {
	try {
		const settings = getDateSettings();
		const offsetHours = parseFloat( settings?.timezone?.offset );

		return Number.isNaN( offsetHours ) ? 0 : offsetHours * 60;
	} catch ( error ) {
		return 0;
	}
}

/**
 * Keep a countdown ticking for the editor preview.
 *
 * @param {number|null} expiryMs UTC timestamp in milliseconds, or null.
 * @return {{parts: Object, hasExpired: boolean}} Countdown state.
 */
function useCountdownPreview( expiryMs ) {
	const [ now, setNow ] = useState( () => Date.now() );

	useEffect( () => {
		if ( ! expiryMs ) {
			return undefined;
		}

		const interval = window.setInterval( () => setNow( Date.now() ), 1000 );

		return () => window.clearInterval( interval );
	}, [ expiryMs ] );

	const remaining = expiryMs ? expiryMs - now : 0;

	return {
		parts: splitDuration( Math.max( 0, remaining ) ),
		hasExpired: Boolean( expiryMs ) && remaining <= 0,
	};
}

/**
 * Ask the server to validate the chosen expiry date.
 *
 * The endpoint requires `edit_posts` and a valid REST nonce, both of which
 * `apiFetch` supplies from the editor session.
 *
 * @param {string} expiryDate Raw attribute value.
 * @return {Object|null} Validation payload, or null while unknown.
 */
function useExpiryValidation( expiryDate ) {
	const [ validation, setValidation ] = useState( null );
	const requestId = useRef( 0 );

	useEffect( () => {
		if ( ! expiryDate ) {
			setValidation( null );
			return undefined;
		}

		requestId.current += 1;
		const currentRequest = requestId.current;

		const timer = window.setTimeout( () => {
			apiFetch( {
				path: '/global-store/v1/flash-sale/validate-expiry',
				method: 'POST',
				data: { expiryDate },
			} )
				.then( ( response ) => {
					if ( currentRequest === requestId.current ) {
						setValidation( response );
					}
				} )
				.catch( () => {
					if ( currentRequest === requestId.current ) {
						setValidation( null );
					}
				} );
		}, 400 );

		return () => window.clearTimeout( timer );
	}, [ expiryDate ] );

	return validation;
}

/**
 * Block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {JSX.Element} Editor markup.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		size,
		title,
		subtitle,
		countdownLabel,
		expiryDate,
		expiredMessage,
		hideWhenExpired,
		ctaText,
		ctaUrl,
		ctaOpensInNewTab,
		finePrint,
		imageId,
		imageUrl,
		imageAlt,
		imagePosition,
		accentColor,
	} = attributes;

	const resolvedSize = normalizeSize( size );
	const hasImage = Boolean( imageUrl );

	const blockProps = useBlockProps( {
		className: [
			`is-size-${ resolvedSize }`,
			hasImage ? 'has-cutout-image' : '',
			accentColor ? 'has-custom-accent' : '',
		]
			.filter( Boolean )
			.join( ' ' ),
		style: {
			...( accentColor ? { '--gsfsh-accent': accentColor } : {} ),
			...( imagePosition !== 'center'
				? { '--gsfsh-image-position': imagePosition }
				: {} ),
		},
	} );

	const expiryMs = siteDateToUtcMs( expiryDate, getSiteOffsetMinutes() );
	const { parts, hasExpired } = useCountdownPreview( expiryMs );
	const validation = useExpiryValidation( expiryDate );

	const onSelectImage = ( media ) => {
		if ( ! media || ! media.url ) {
			return;
		}

		setAttributes( {
			imageId: media.id ? parseInt( media.id, 10 ) : 0,
			imageUrl: media.url,
			imageAlt: media.alt || imageAlt || '',
		} );
	};

	const onRemoveImage = () =>
		setAttributes( { imageId: 0, imageUrl: '', imageAlt: '' } );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					{ SIZE_OPTIONS.filter( ( option ) => option.value !== 'auto' ).map(
						( option ) => (
							<Button
								key={ option.value }
								isPressed={ resolvedSize === option.value }
								onClick={ () =>
									setAttributes( { size: option.value } )
								}
							>
								{ option.label }
							</Button>
						)
					) }
				</ToolbarGroup>
				{ hasImage && (
					<MediaReplaceFlow
						mediaId={ imageId }
						mediaURL={ imageUrl }
						allowedTypes={ [ 'image' ] }
						accept="image/*"
						onSelect={ onSelectImage }
						name={ __(
							'Replace image',
							'flash-sale-header-block'
						) }
					/>
				) }
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={ __( 'Layout', 'flash-sale-header-block' ) }
					initialOpen
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Size', 'flash-sale-header-block' ) }
						value={ resolvedSize }
						options={ SIZE_OPTIONS }
						help={ __(
							'Auto lets CSS container queries pick the layout from the available width. Choose a size to lock it.',
							'flash-sale-header-block'
						) }
						onChange={ ( value ) =>
							setAttributes( { size: normalizeSize( value ) } )
						}
					/>

					<BaseControl
						__nextHasNoMarginBottom
						id="gsfsh-size-toggles"
						label={ __(
							'Lock layout',
							'flash-sale-header-block'
						) }
					>
						<div className="gsfsh-size-toggles">
							{ SIZE_OPTIONS.map( ( option ) => (
								<Button
									key={ option.value }
									variant={
										resolvedSize === option.value
											? 'primary'
											: 'secondary'
									}
									isPressed={ resolvedSize === option.value }
									onClick={ () =>
										setAttributes( {
											size: option.value,
										} )
									}
								>
									{ option.label }
								</Button>
							) ) }
						</div>
					</BaseControl>
				</PanelBody>

				<PanelBody
					title={ __( 'Countdown', 'flash-sale-header-block' ) }
					initialOpen
				>
					<BaseControl
						__nextHasNoMarginBottom
						id="gsfsh-expiry-date"
						label={ __(
							'Offer expires',
							'flash-sale-header-block'
						) }
						help={ __(
							'Entered in the site timezone.',
							'flash-sale-header-block'
						) }
					>
						<DateTimePicker
							currentDate={ expiryDate || undefined }
							onChange={ ( value ) =>
								setAttributes( { expiryDate: value || '' } )
							}
							__nextRemoveHelpButton
							__nextRemoveResetButton
						/>
					</BaseControl>

					{ expiryDate && (
						<Button
							variant="tertiary"
							isDestructive
							onClick={ () =>
								setAttributes( { expiryDate: '' } )
							}
						>
							{ __(
								'Clear expiry date',
								'flash-sale-header-block'
							) }
						</Button>
					) }

					{ validation?.isPast && (
						<Notice status="warning" isDismissible={ false }>
							{ sprintf(
								/* translators: %s: formatted date and time. */
								__(
									'This offer already ended on %s.',
									'flash-sale-header-block'
								),
								validation.formatted
							) }
						</Notice>
					) }

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Countdown label',
							'flash-sale-header-block'
						) }
						value={ countdownLabel }
						onChange={ ( value ) =>
							setAttributes( { countdownLabel: value } )
						}
					/>

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Expired message',
							'flash-sale-header-block'
						) }
						value={ expiredMessage }
						onChange={ ( value ) =>
							setAttributes( { expiredMessage: value } )
						}
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Hide the block once the offer ends',
							'flash-sale-header-block'
						) }
						checked={ hideWhenExpired }
						onChange={ ( value ) =>
							setAttributes( { hideWhenExpired: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Call to action', 'flash-sale-header-block' ) }
					initialOpen={ false }
				>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Button label',
							'flash-sale-header-block'
						) }
						value={ ctaText }
						onChange={ ( value ) =>
							setAttributes( { ctaText: value } )
						}
					/>

					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						type="url"
						inputMode="url"
						label={ __( 'Button URL', 'flash-sale-header-block' ) }
						value={ ctaUrl }
						placeholder="https://"
						onChange={ ( value ) =>
							setAttributes( { ctaUrl: value } )
						}
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Open in a new tab',
							'flash-sale-header-block'
						) }
						checked={ ctaOpensInNewTab }
						onChange={ ( value ) =>
							setAttributes( { ctaOpensInNewTab: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Cutout image', 'flash-sale-header-block' ) }
					initialOpen={ false }
				>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelectImage }
							allowedTypes={ [ 'image' ] }
							accept="image/*"
							value={ imageId }
							render={ ( { open } ) => (
								<Button
									variant="secondary"
									onClick={ open }
								>
									{ hasImage
										? __(
												'Replace image',
												'flash-sale-header-block'
										  )
										: __(
												'Select image',
												'flash-sale-header-block'
										  ) }
								</Button>
							) }
						/>
					</MediaUploadCheck>

					{ hasImage ? (
						<>
							<img
								className="gsfsh-image-preview"
								src={ imageUrl }
								alt=""
							/>
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __(
									'Alternative text',
									'flash-sale-header-block'
								) }
								value={ imageAlt }
								help={ __(
									'Leave empty when the image is purely decorative.',
									'flash-sale-header-block'
								) }
								onChange={ ( value ) =>
									setAttributes( { imageAlt: value } )
								}
							/>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __(
									'Focal position',
									'flash-sale-header-block'
								) }
								value={ imagePosition }
								options={ IMAGE_POSITION_OPTIONS }
								onChange={ ( value ) =>
									setAttributes( { imagePosition: value } )
								}
							/>
							<Button
								variant="secondary"
								isDestructive
								onClick={ onRemoveImage }
							>
								{ __(
									'Remove image',
									'flash-sale-header-block'
								) }
							</Button>
						</>
					) : null }
				</PanelBody>

				<PanelColorSettings
					title={ __( 'Accent', 'flash-sale-header-block' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: accentColor,
							label: __(
								'Accent colour',
								'flash-sale-header-block'
							),
							onChange: ( value ) =>
								setAttributes( { accentColor: value || '' } ),
						},
					] }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				<div className={ `${ BASE_CLASS }__inner` }>
					<div className={ `${ BASE_CLASS }__content` }>
						<RichText
							tagName="h2"
							className={ `${ BASE_CLASS }__title` }
							value={ title }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							placeholder={ __(
								'The Flash Sale',
								'flash-sale-header-block'
							) }
							onChange={ ( value ) =>
								setAttributes( { title: value } )
							}
							aria-label={ __(
								'Header title',
								'flash-sale-header-block'
							) }
						/>

						<RichText
							tagName="p"
							className={ `${ BASE_CLASS }__subtitle` }
							value={ subtitle }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							placeholder={ __(
								'£1 a month for 12 months',
								'flash-sale-header-block'
							) }
							onChange={ ( value ) =>
								setAttributes( { subtitle: value } )
							}
							aria-label={ __(
								'Offer details',
								'flash-sale-header-block'
							) }
						/>

						{ expiryDate ? (
							<div className={ `${ BASE_CLASS }__countdown` }>
								{ countdownLabel && (
									<p
										className={ `${ BASE_CLASS }__countdown-label` }
									>
										{ countdownLabel }
									</p>
								) }

								{ hasExpired ? (
									<p
										className={ `${ BASE_CLASS }__expired` }
										data-testid="countdown-expired"
									>
										{ expiredMessage }
									</p>
								) : (
									<ul
										className={ `${ BASE_CLASS }__units` }
										data-testid="countdown-units"
									>
										{ Object.keys( UNIT_LABELS ).map(
											( unit ) => (
												<li
													key={ unit }
													className={ `${ BASE_CLASS }__unit` }
												>
													<span
														className={ `${ BASE_CLASS }__unit-value` }
														data-gsfsh-unit={ unit }
													>
														{ pad( parts[ unit ] ) }
													</span>
													<span
														className={ `${ BASE_CLASS }__unit-label` }
													>
														{ UNIT_LABELS[ unit ] }
													</span>
												</li>
											)
										) }
									</ul>
								) }
							</div>
						) : (
							<p className={ `${ BASE_CLASS }__countdown-hint` }>
								{ __(
									'Set an expiry date in the block sidebar to show the countdown.',
									'flash-sale-header-block'
								) }
							</p>
						) }

						<RichText
							tagName="span"
							className={ `${ BASE_CLASS }__cta` }
							value={ ctaText }
							allowedFormats={ [] }
							placeholder={ __(
								'Subscribe now',
								'flash-sale-header-block'
							) }
							onChange={ ( value ) =>
								setAttributes( { ctaText: value } )
							}
							aria-label={ __(
								'Call to action label',
								'flash-sale-header-block'
							) }
						/>

						<RichText
							tagName="p"
							className={ `${ BASE_CLASS }__fine-print` }
							value={ finePrint }
							allowedFormats={ [ 'core/bold', 'core/link' ] }
							placeholder={ __(
								'Add the legal small print…',
								'flash-sale-header-block'
							) }
							onChange={ ( value ) =>
								setAttributes( { finePrint: value } )
							}
							aria-label={ __(
								'Fine print',
								'flash-sale-header-block'
							) }
						/>
					</div>

					{ hasImage && (
						<div
							className={ `${ BASE_CLASS }__media` }
							aria-hidden={ ! imageAlt }
						>
							<img
								className={ `${ BASE_CLASS }__image` }
								src={ imageUrl }
								alt={ imageAlt }
							/>
						</div>
					) }
				</div>
			</div>
		</>
	);
}
