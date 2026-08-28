<?php
/**
 * Plugin Name:       Global Store Flash Sale Header
 * Plugin URI:        https://example.com/plugins/flash-sale-header-block
 * Description:       A responsive Gutenberg block that displays a countdown-driven flash sale header with three container-query-aware layouts (wide, medium, tall).
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Global Store
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       global-store
 *
 * @package GlobalStore\FlashSaleHeader
 */

defined( 'ABSPATH' ) || exit;

define( 'GSFSH_VERSION', '1.0.0' );
define( 'GSFSH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GSFSH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the block using metadata loaded from the compiled `block.json`.
 */
function gsfsh_register_block() {
	$build_dir = GSFSH_PLUGIN_DIR . 'build';

	if ( ! file_exists( $build_dir . '/block.json' ) ) {
		return;
	}

	register_block_type( $build_dir );
}
add_action( 'init', 'gsfsh_register_block' );
