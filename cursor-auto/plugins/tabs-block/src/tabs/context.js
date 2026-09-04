/**
 * Editor-only link between the container block and its tab panels.
 *
 * Which tab is open while editing is a preview state rather than saved content, so it
 * cannot travel through block context (which only carries attributes). Both block types
 * are registered from this bundle, which is what lets them share this context object.
 */

import { createContext } from '@wordpress/element';

export const TabsContext = createContext( { activeIndex: 0 } );
