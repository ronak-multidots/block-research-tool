<?php
/**
 * Plugin Name:       Global Store Flash Sale Header
 * Plugin URI:        https://example.com/plugins/flash-sale-header-block
 * Description:       A secure, container-query driven Gutenberg block that renders a flash sale header with a live countdown timer in three layouts.
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Global Store Engineering
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flash-sale-header-block
 * Domain Path:       /languages
 *
 * @package GlobalStore\FlashSaleHeader
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION     = '1.0.0';
const PLUGIN_FILE = __FILE__;

/**
 * Absolute path to the plugin directory, with a trailing slash.
 *
 * @return string
 */
function plugin_dir(): string {
	return plugin_dir_path( PLUGIN_FILE );
}

require_once plugin_dir() . 'includes/class-attributes.php';
require_once plugin_dir() . 'includes/class-renderer.php';
require_once plugin_dir() . 'includes/class-rest-controller.php';

/**
 * Register the block type from compiled metadata.
 *
 * @return void
 */
function register_block(): void {
	$metadata_dir = plugin_dir() . 'build/flash-sale-header';

	if ( ! file_exists( $metadata_dir . '/block.json' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\render_missing_build_notice' );
		return;
	}

	register_block_type( $metadata_dir );
}
add_action( 'init', __NAMESPACE__ . '\\register_block' );

/**
 * Warn administrators when compiled assets are missing.
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
			'Global Store Flash Sale Header: assets are not built. Run "npm install && npm run build" inside the plugin directory.',
			'flash-sale-header-block'
		)
	);
}

/**
 * Load plugin translations.
 *
 * @return void
 */
function load_textdomain(): void {
	load_plugin_textdomain( 'flash-sale-header-block', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain' );

/**
 * Boot the REST controller.
 *
 * @return void
 */
function register_rest_routes(): void {
	( new REST_Controller() )->register_routes();
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_rest_routes' );
