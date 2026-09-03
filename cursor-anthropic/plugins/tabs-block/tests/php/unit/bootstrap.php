<?php
/**
 * Bootstrap for the isolated PHP unit suite.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

$gstb_autoload = dirname( __DIR__, 3 ) . '/vendor/autoload.php';

if ( file_exists( $gstb_autoload ) ) {
	require_once $gstb_autoload;
}

require_once __DIR__ . '/wp-stubs.php';

$gstb_plugin_dir = dirname( __DIR__, 3 );

require_once $gstb_plugin_dir . '/includes/class-icons.php';
require_once $gstb_plugin_dir . '/includes/class-attributes.php';
require_once $gstb_plugin_dir . '/includes/class-renderer.php';
