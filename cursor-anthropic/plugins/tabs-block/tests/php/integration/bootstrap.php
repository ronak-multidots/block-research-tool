<?php
/**
 * Bootstrap for the WordPress integration suite.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

$gstb_plugin_dir = dirname( __DIR__, 3 );

if ( ! file_exists( $gstb_plugin_dir . '/build/tabs/block.json' ) ) {
	echo "Build the blocks before running the integration suite: npm run build\n";
	exit( 1 );
}

$gstb_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $gstb_tests_dir ) {
	$gstb_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $gstb_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test suite in {$gstb_tests_dir}.\n";
	echo "Set WP_TESTS_DIR, or run the suite inside wp-env:\n";
	echo "  npx wp-env run tests-cli --env-cwd=wp-content/plugins/tabs-block \\\n";
	echo "    vendor/bin/phpunit --configuration phpunit-integration.xml.dist\n";
	exit( 1 );
}

require_once $gstb_tests_dir . '/includes/functions.php';

/**
 * Load the plugin before WordPress finishes booting.
 */
tests_add_filter(
	'muplugins_loaded',
	static function () use ( $gstb_plugin_dir ) {
		require $gstb_plugin_dir . '/tabs-block.php';
	}
);

require $gstb_tests_dir . '/includes/bootstrap.php';
