<?php
/**
 * Bootstrap for the isolated PHP unit suite.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

$gsfsh_autoload = dirname( __DIR__, 3 ) . '/vendor/autoload.php';

if ( file_exists( $gsfsh_autoload ) ) {
	require_once $gsfsh_autoload;
}

require_once __DIR__ . '/wp-stubs.php';

$gsfsh_plugin_dir = dirname( __DIR__, 3 );

require_once $gsfsh_plugin_dir . '/includes/class-attributes.php';
require_once $gsfsh_plugin_dir . '/includes/class-rest-controller.php';
require_once $gsfsh_plugin_dir . '/includes/class-renderer.php';
