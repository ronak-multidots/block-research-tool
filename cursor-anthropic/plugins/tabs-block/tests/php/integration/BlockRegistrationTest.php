<?php
/**
 * Verifies the blocks register correctly against a real WordPress install.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock\Tests\Integration;

use GlobalStore\TabsBlock\Icons;
use WP_Block_Type_Registry;
use WP_UnitTestCase;

/**
 * @group registration
 */
final class BlockRegistrationTest extends WP_UnitTestCase {

	private const CONTAINER_BLOCK = 'global-store/tabs';

	private const TAB_BLOCK = 'global-store/tab';

	public function test_both_blocks_are_registered(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertTrue( $registry->is_registered( self::CONTAINER_BLOCK ) );
		$this->assertTrue( $registry->is_registered( self::TAB_BLOCK ) );
	}

	public function test_blocks_use_api_version_three_and_server_rendering(): void {
		$registry = WP_Block_Type_Registry::get_instance();

		foreach ( array( self::CONTAINER_BLOCK, self::TAB_BLOCK ) as $name ) {
			$block = $registry->get_registered( $name );

			$this->assertSame( 3, $block->api_version, $name );
			$this->assertTrue( is_callable( $block->render_callback ), $name );
		}
	}

	public function test_a_tab_can_only_live_inside_the_container(): void {
		$tab       = WP_Block_Type_Registry::get_instance()->get_registered( self::TAB_BLOCK );
		$container = WP_Block_Type_Registry::get_instance()->get_registered( self::CONTAINER_BLOCK );

		$this->assertSame( array( self::CONTAINER_BLOCK ), $tab->parent );
		$this->assertSame( array( self::TAB_BLOCK ), $container->allowed_blocks );
	}

	public function test_container_attribute_defaults_match_the_specification(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::CONTAINER_BLOCK );

		$this->assertSame( 0, $block->attributes['defaultActiveTab']['default'] );
		$this->assertSame( 'horizontal', $block->attributes['orientation']['default'] );
		$this->assertSame( array( 'horizontal', 'vertical' ), $block->attributes['orientation']['enum'] );
		$this->assertSame( 'underline', $block->attributes['tabStyle']['default'] );
		$this->assertSame( 'center', $block->attributes['alignment']['default'] );
		$this->assertTrue( $block->attributes['showIcons']['default'] );
	}

	public function test_the_tab_icon_enum_matches_the_php_icon_library(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::TAB_BLOCK );

		$this->assertSame( Icons::names(), $block->attributes['icon']['enum'] );
	}

	public function test_front_end_assets_are_registered(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::CONTAINER_BLOCK );

		$this->assertNotEmpty( $block->view_script_handles );
		$this->assertNotEmpty( $block->style_handles );
		$this->assertNotEmpty( $block->editor_script_handles );

		foreach ( $block->view_script_handles as $handle ) {
			$this->assertTrue( wp_script_is( $handle, 'registered' ) );
		}
	}

	public function test_the_container_supports_alignment_and_disallows_raw_html_editing(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( self::CONTAINER_BLOCK );

		$this->assertSame( array( 'wide', 'full' ), $block->supports['align'] );
		$this->assertFalse( $block->supports['html'] );
	}
}
