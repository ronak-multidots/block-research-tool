<?php
/**
 * Bootstrap for the WordPress integration suite.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

$gsfsh_plugin_dir = dirname( __DIR__, 3 );

if ( ! file_exists( $gsfsh_plugin_dir . '/build/flash-sale-header/block.json' ) ) {
	echo "Build the block before running the integration suite: npm run build\n";
	exit( 1 );
}

$gsfsh_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $gsfsh_tests_dir ) {
	$gsfsh_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $gsfsh_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test suite in {$gsfsh_tests_dir}.\n";
	echo "Set WP_TESTS_DIR, or run the suite inside wp-env:\n";
	echo "  npx wp-env run tests-cli --env-cwd=wp-content/plugins/flash-sale-header-block \\\n";
	echo "    vendor/bin/phpunit --configuration phpunit-integration.xml.dist\n";
	exit( 1 );
}

require_once $gsfsh_tests_dir . '/includes/functions.php';

/**
 * Load the plugin before WordPress finishes booting.
 */
tests_add_filter(
	'muplugins_loaded',
	static function () use ( $gsfsh_plugin_dir ) {
		require $gsfsh_plugin_dir . '/flash-sale-header-block.php';
	}
);

require $gsfsh_tests_dir . '/includes/bootstrap.php';
