<?php
/**
 * Verifies the block registers correctly against a real WordPress install.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader\Tests\Integration;

use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * @group registration
 */
final class BlockRegistrationTest extends WP_UnitTestCase {

	private const BLOCK_NAME = 'global-store/flash-sale-header';

	public function test_block_is_registered(): void {
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( self::BLOCK_NAME )
		);
	}

	public function test_block_uses_api_version_three_and_server_rendering(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK_NAME );

		$this->assertSame( 3, $block->api_version );
		$this->assertTrue( is_callable( $block->render_callback ) );
	}

	public function test_attribute_defaults_match_the_specification(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK_NAME );

		$this->assertSame( 'auto', $block->attributes['size']['default'] );
		$this->assertSame( array( 'auto', 'wide', 'medium', 'tall' ), $block->attributes['size']['enum'] );
		$this->assertSame( 'string', $block->attributes['expiryDate']['type'] );
		$this->assertSame( 'number', $block->attributes['imageId']['type'] );
		$this->assertFalse( $block->attributes['ctaOpensInNewTab']['default'] );
	}

	public function test_front_end_assets_are_registered(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK_NAME );

		$this->assertNotEmpty( $block->view_script_handles );
		$this->assertNotEmpty( $block->style_handles );
		$this->assertNotEmpty( $block->editor_script_handles );

		foreach ( $block->view_script_handles as $handle ) {
			$this->assertTrue( wp_script_is( $handle, 'registered' ) );
		}
	}

	public function test_block_supports_alignment_and_disallows_raw_html_editing(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::BLOCK_NAME );

		$this->assertSame( array( 'wide', 'full' ), $block->supports['align'] );
		$this->assertFalse( $block->supports['html'] );
	}
}
