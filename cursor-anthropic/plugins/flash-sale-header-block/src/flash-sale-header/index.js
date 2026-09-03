/**
 * Block registration for the Global Store Flash Sale Header.
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import icon from './icon';

import './style.scss';

/**
 * The block is rendered by `render.php`, so `save` intentionally returns null and
 * the post content only stores the block comment delimiter and its attributes.
 */
registerBlockType( metadata.name, {
	...metadata,
	icon,
	edit: Edit,
	save: () => null,
} );
