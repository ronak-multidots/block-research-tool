<?php
/**
 * Tests for block registration.
 *
 * @package GlobalStore\FlashSaleHeader
 */

/**
 * Verifies the block registers correctly and declares the attributes the
 * editor UI and render.php both depend on.
 *
 * @coversNothing
 */
class BlockRegistrationTest extends WP_UnitTestCase {

	/**
	 * Attributes that must be declared on the block for the editor
	 * controls (size, content fields, CTA, image) to work.
	 *
	 * @var string[]
	 */
	const EXPECTED_ATTRIBUTES = array(
		'size',
		'title',
		'subtitle',
		'expiryDateTime',
		'ctaText',
		'ctaUrl',
		'legalText',
		'imageId',
		'imageUrl',
		'imageAlt',
	);

	/**
	 * The block should be registered under its full namespaced name.
	 */
	public function test_block_is_registered() {
		$registry = WP_Block_Type_Registry::get_instance();

		$this->assertTrue( $registry->is_registered( 'global-store/flash-sale-header' ) );
	}

	/**
	 * The block must render via render.php rather than a saved static markup.
	 */
	public function test_block_is_dynamic() {
		$registry = WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'global-store/flash-sale-header' );

		$this->assertNotNull( $block );
		$this->assertTrue( $block->is_dynamic() );
	}

	/**
	 * Every attribute the editor UI reads/writes must be declared in block.json.
	 */
	public function test_block_has_expected_attributes() {
		$registry = WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'global-store/flash-sale-header' );

		foreach ( self::EXPECTED_ATTRIBUTES as $attribute ) {
			$this->assertArrayHasKey( $attribute, $block->attributes );
		}
	}

	/**
	 * A block inserted without touching the sidebar should default to the wide layout.
	 */
	public function test_size_attribute_default_is_wide() {
		$registry = WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'global-store/flash-sale-header' );

		$this->assertSame( 'wide', $block->attributes['size']['default'] );
	}

	/**
	 * The size attribute must be constrained to the three supported layouts.
	 */
	public function test_size_attribute_only_allows_known_layouts() {
		$registry = WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'global-store/flash-sale-header' );

		$this->assertSame( array( 'wide', 'medium', 'tall' ), $block->attributes['size']['enum'] );
	}
}
