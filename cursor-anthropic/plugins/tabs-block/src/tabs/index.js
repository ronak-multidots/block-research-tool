/**
 * Block registration for Global Store Tabs.
 *
 * Both block types are registered from this single entry point so the container and its
 * panels can share the React context that tracks which tab is open while editing.
 */

import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';

import TabEdit from '../tab/edit';
import tabMetadata from '../tab/block.json';
import Edit from './edit';
import icon from './icon';
import metadata from './block.json';

import './style.scss';

/**
 * Dynamic blocks still need `InnerBlocks.Content` in `save` so the authored panels
 * (and whatever blocks sit inside them) are written into the post.
 */
registerBlockType( metadata.name, {
	...metadata,
	icon,
	edit: Edit,
	save: () => <InnerBlocks.Content />,
} );

registerBlockType( tabMetadata.name, {
	...tabMetadata,
	icon,
	edit: TabEdit,
	save: () => <InnerBlocks.Content />,
} );
