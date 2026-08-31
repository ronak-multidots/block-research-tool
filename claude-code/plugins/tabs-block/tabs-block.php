<?php
/**
 * Plugin Name:       Global Store Tabs
 * Plugin URI:        https://example.com/plugins/tabs-block
 * Description:       A dynamic, accessible Tabs block. Each tab title supports an icon (Dashicon or custom image) plus text, and any block can be nested inside a tab's content.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Global Store
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       global-store-tabs
 *
 * @package GlobalStore\Tabs
 */

defined( 'ABSPATH' ) || exit;

define( 'GST_VERSION', '1.0.0' );
define( 'GST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the Tabs and Tab block types using metadata loaded from the
 * compiled `block.json` files.
 */
function gst_register_blocks() {
	$blocks = array( 'tabs', 'tab' );

	foreach ( $blocks as $block ) {
		$build_dir = GST_PLUGIN_DIR . 'build/' . $block;

		if ( ! file_exists( $build_dir . '/block.json' ) ) {
			continue;
		}

		register_block_type( $build_dir );
	}
}
add_action( 'init', 'gst_register_blocks' );

/**
 * Dashicons is only enqueued by default in wp-admin, not on the frontend
 * (see src/tabs/render.php for the frontend enqueue). The block editor's
 * canvas also renders inside an iframe with its own isolated stylesheets;
 * `enqueue_block_editor_assets` alone does not reach that iframe, only the
 * styles listed in block editor settings do (the same mechanism behind
 * `add_editor_style()`), so append Dashicons there directly.
 *
 * @param array $settings Existing block editor settings.
 * @return array Filtered block editor settings.
 */
function gst_add_dashicons_to_editor_iframe( $settings ) {
	$settings['styles'][] = array(
		'css' => '@import url("' . esc_url( includes_url( 'css/dashicons.css' ) ) . '");',
	);

	return $settings;
}
add_filter( 'block_editor_settings_all', 'gst_add_dashicons_to_editor_iframe' );
