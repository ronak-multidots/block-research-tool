<?php
/**
 * PHPUnit bootstrap file.
 *
 * Expects a WordPress test install available via `WP_TESTS_DIR` (as set up
 * by `bin/install-wp-tests.sh` or `wp-env run tests-cli`).
 *
 * @package GlobalStore\FlashSaleHeader
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI-only bootstrap message, runs before WP (and esc_html()) is available.
	echo "Could not find {$_tests_dir}/includes/functions.php - have you run bin/install-wp-tests.sh?" . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin under test.
 */
function _gsfsh_manually_load_plugin() {
	require dirname( dirname( __DIR__ ) ) . '/flash-sale-header-block.php';
}
tests_add_filter( 'muplugins_loaded', '_gsfsh_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
