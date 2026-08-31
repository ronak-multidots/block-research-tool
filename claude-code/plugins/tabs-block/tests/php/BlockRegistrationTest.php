<?php
/**
 * Tests for block registration (both the Tabs and Tab block types).
 *
 * @package GlobalStore\Tabs
 */

/**
 * Verifies both block types are registered correctly, with the parent/child
 * and context relationship the Tabs <-> Tab UI depends on.
 *
 * @coversNothing
 */
class BlockRegistrationTest extends WP_UnitTestCase {

	/**
	 * The Tabs (parent) block type should be registered under its full name.
	 */
	public function test_tabs_block_is_registered() {
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'global-store/tabs' ) );
	}

	/**
	 * The Tab (child) block type should be registered under its full name.
	 */
	public function test_tab_block_is_registered() {
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'global-store/tab' ) );
	}

	/**
	 * Both blocks are dynamic (server-rendered): they must declare a
	 * render_callback rather than relying on static saved markup.
	 */
	public function test_both_blocks_are_dynamic() {
		$registry = WP_Block_Type_Registry::get_instance();

		$tabs_block = $registry->get_registered( 'global-store/tabs' );
		$tab_block  = $registry->get_registered( 'global-store/tab' );

		$this->assertIsCallable( $tabs_block->render_callback );
		$this->assertIsCallable( $tab_block->render_callback );
	}

	/**
	 * The Tab block declares Tabs as its only allowed parent, so it cannot
	 * be inserted anywhere else in the editor.
	 */
	public function test_tab_block_parent_is_restricted_to_tabs() {
		$tab_block = WP_Block_Type_Registry::get_instance()->get_registered( 'global-store/tab' );

		$this->assertSame( array( 'global-store/tabs' ), $tab_block->parent );
	}

	/**
	 * The Tabs block must provide the context the Tab block consumes to
	 * know which tab is currently active while editing.
	 */
	public function test_context_is_provided_and_consumed() {
		$tabs_block = WP_Block_Type_Registry::get_instance()->get_registered( 'global-store/tabs' );
		$tab_block  = WP_Block_Type_Registry::get_instance()->get_registered( 'global-store/tab' );

		$this->assertArrayHasKey( 'global-store/activeTabId', $tabs_block->provides_context );
		$this->assertContains( 'global-store/activeTabId', $tab_block->uses_context );
	}

	/**
	 * Sanity-check the declared attribute defaults survive registration.
	 */
	public function test_tab_attribute_defaults() {
		$tab_block = WP_Block_Type_Registry::get_instance()->get_registered( 'global-store/tab' );

		$this->assertSame( 'none', $tab_block->attributes['iconType']['default'] );
		$this->assertSame( '', $tab_block->attributes['tabId']['default'] );
	}
}
