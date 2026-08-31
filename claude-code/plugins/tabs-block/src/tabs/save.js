// Dynamic block: render.php builds the nav + panel markup from the saved
// attributes and inner blocks at render time. `save` only needs to
// preserve the InnerBlocks (Tab) content in the post's stored HTML.
import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
