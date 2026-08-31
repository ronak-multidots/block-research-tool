import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';

import './editor.scss';

const ALLOWED_BLOCKS = [ 'global-store/tab' ];
const TEMPLATE = [
	[ 'global-store/tab', { title: __( 'Tab 1', 'global-store-tabs' ) } ],
	[ 'global-store/tab', { title: __( 'Tab 2', 'global-store-tabs' ) } ],
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { activeTabId } = attributes;

	const tabs = useSelect(
		( select ) => select( blockEditorStore ).getBlocks( clientId ),
		[ clientId ]
	);

	const { updateBlockAttributes } = useDispatch( blockEditorStore );

	const currentActiveId =
		activeTabId || ( tabs[ 0 ] && tabs[ 0 ].attributes.tabId ) || '';

	const blockProps = useBlockProps( { className: 'tabs-block' } );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'tabs-block__panels' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateInsertUpdatesSelection: false,
		}
	);

	return (
		<div { ...blockProps }>
			<div className="tabs-block__nav" role="tablist">
				{ tabs.map( ( tab ) => {
					const tabId = tab.attributes.tabId;
					const isActive = tabId ? tabId === currentActiveId : false;
					const { iconType, dashicon, imageUrl, imageAlt } =
						tab.attributes;

					return (
						<button
							key={ tab.clientId }
							type="button"
							role="tab"
							aria-selected={ isActive }
							className={ `tabs-block__tab-btn${
								isActive ? ' is-active' : ''
							}` }
							onClick={ () =>
								setAttributes( { activeTabId: tabId } )
							}
						>
							{ iconType === 'dashicon' && dashicon && (
								<span
									className={ `tabs-block__tab-icon dashicons ${ dashicon }` }
									aria-hidden="true"
								/>
							) }
							{ iconType === 'image' && imageUrl && (
								<img
									className="tabs-block__tab-icon tabs-block__tab-icon--image"
									src={ imageUrl }
									alt={ imageAlt }
								/>
							) }
							<RichText
								tagName="span"
								className="tabs-block__tab-label"
								value={ tab.attributes.title }
								onChange={ ( value ) =>
									updateBlockAttributes( tab.clientId, {
										title: value,
									} )
								}
								placeholder={ __(
									'Tab title',
									'global-store-tabs'
								) }
								allowedFormats={ [] }
							/>
						</button>
					);
				} ) }
			</div>

			<div { ...innerBlocksProps } />
		</div>
	);
}
