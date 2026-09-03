<?php
/**
 * Plugin Name:       Global Store Tabs
 * Plugin URI:        https://example.com/plugins/tabs-block
 * Description:       An accessible tabs block. Every tab panel accepts any blocks, so authors can build their content freely inside each tab.
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Global Store Engineering
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tabs-block
 * Domain Path:       /languages
 *
 * @package GlobalStore\TabsBlock
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION     = '1.0.0';
const PLUGIN_FILE = __FILE__;

/**
 * Blocks shipped by the plugin, in build-directory order.
 *
 * The child block carries no scripts of its own: `src/tabs/index.js` registers both
 * block types so the editor can share one React context between them.
 */
const BLOCKS = array( 'tabs', 'tab' );

/**
 * Absolute path to the plugin directory, with a trailing slash.
 *
 * @return string
 */
function plugin_dir(): string {
	return plugin_dir_path( PLUGIN_FILE );
}

require_once plugin_dir() . 'includes/class-icons.php';
require_once plugin_dir() . 'includes/class-attributes.php';
require_once plugin_dir() . 'includes/class-renderer.php';

/**
 * Register the block types from their compiled metadata.
 *
 * @return void
 */
function register_blocks(): void {
	foreach ( BLOCKS as $block ) {
		$metadata_dir = plugin_dir() . 'build/' . $block;

		if ( ! file_exists( $metadata_dir . '/block.json' ) ) {
			add_action( 'admin_notices', __NAMESPACE__ . '\\render_missing_build_notice' );
			return;
		}

		$args = 'tabs' === $block ? array( 'skip_inner_blocks' => true ) : array();

		register_block_type( $metadata_dir, $args );
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks' );

/**
 * Load the plugin translations.
 *
 * @return void
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'tabs-block', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );

/**
 * Tell the site owner that the assets still need compiling.
 *
 * @return void
 */
function render_missing_build_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__(
			'Global Store Tabs could not find its compiled assets. Run "npm install && npm run build" inside the plugin directory.',
			'tabs-block'
		)
	);
}
