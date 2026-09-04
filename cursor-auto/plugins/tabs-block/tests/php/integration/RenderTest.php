<?php
/**
 * Renders real block markup through WordPress.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock\Tests\Integration;

use WP_UnitTestCase;

/**
 * @group render
 */
final class RenderTest extends WP_UnitTestCase {

	/**
	 * Build serialised block content for a tabs block.
	 *
	 * @param string               $tabs       Serialised tab blocks.
	 * @param array<string, mixed> $attributes Container attributes.
	 * @return string
	 */
	private function tabs_block( string $tabs, array $attributes = array() ): string {
		$attrs = empty( $attributes ) ? '' : ' ' . wp_json_encode( $attributes );

		return "<!-- wp:global-store/tabs{$attrs} -->{$tabs}<!-- /wp:global-store/tabs -->";
	}

	/**
	 * Build a serialised tab block.
	 *
	 * @param array<string, mixed> $attributes Tab attributes.
	 * @param string               $inner      Serialised inner blocks.
	 * @return string
	 */
	private function tab_block( array $attributes, string $inner ): string {
		$attrs = empty( $attributes ) ? '' : ' ' . wp_json_encode( $attributes );

		return "<!-- wp:global-store/tab{$attrs} -->{$inner}<!-- /wp:global-store/tab -->";
	}

	public function test_authored_inner_blocks_end_up_in_their_panel(): void {
		$content = $this->tabs_block(
			$this->tab_block(
				array( 'label' => 'Our Mission' ),
				'<!-- wp:paragraph --><p>Serving people.</p><!-- /wp:paragraph -->'
			) .
			$this->tab_block(
				array( 'label' => 'Our Superpowers' ),
				'<!-- wp:list --><ul class="wp-block-list"><li>Speed</li></ul><!-- /wp:list -->'
			)
		);

		$output = do_blocks( $content );

		$this->assertStringContainsString( '<p>Serving people.</p>', $output );
		$this->assertStringContainsString( '<li>Speed</li>', $output );
		$this->assertSame( 2, substr_count( $output, 'role="tabpanel"' ) );
		$this->assertStringContainsString( 'Our Superpowers', $output );
	}

	public function test_the_wrapper_carries_the_block_class_and_layout_modifiers(): void {
		$output = do_blocks(
			$this->tabs_block(
				$this->tab_block( array( 'label' => 'One' ), '<!-- wp:paragraph --><p>A</p><!-- /wp:paragraph -->' ),
				array(
					'orientation' => 'vertical',
					'tabStyle'    => 'pills',
				)
			)
		);

		$this->assertStringContainsString( 'wp-block-global-store-tabs', $output );
		$this->assertStringContainsString( 'is-orientation-vertical', $output );
		$this->assertStringContainsString( 'is-tab-style-pills', $output );
		$this->assertStringContainsString( 'data-gstb-tabs', $output );
	}

	public function test_an_accent_colour_reaches_the_wrapper_as_a_custom_property(): void {
		$output = do_blocks(
			$this->tabs_block(
				$this->tab_block( array( 'label' => 'One' ), '<!-- wp:paragraph --><p>A</p><!-- /wp:paragraph -->' ),
				array( 'accentColor' => '#ee6c2d' )
			)
		);

		$this->assertStringContainsString( '--gstb-accent:#ee6c2d', $output );
	}

	public function test_an_empty_tabs_block_renders_nothing(): void {
		$this->assertSame( '', trim( do_blocks( $this->tabs_block( '' ) ) ) );
	}

	public function test_dynamic_inner_blocks_are_rendered_rather_than_echoed_raw(): void {
		$post_id = self::factory()->post->create( array( 'post_title' => 'A tabbed post' ) );

		$GLOBALS['post'] = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $GLOBALS['post'] );

		$output = do_blocks(
			$this->tabs_block(
				$this->tab_block(
					array( 'label' => 'Details' ),
					'<!-- wp:post-title /-->'
				)
			)
		);

		wp_reset_postdata();

		$this->assertStringContainsString( 'A tabbed post', $output );
		$this->assertStringNotContainsString( 'wp:post-title', $output );
	}

	public function test_a_tab_outside_its_container_still_shows_its_content(): void {
		$output = do_blocks(
			$this->tab_block(
				array( 'label' => 'Orphan' ),
				'<!-- wp:paragraph --><p>Still readable.</p><!-- /wp:paragraph -->'
			)
		);

		$this->assertStringContainsString( '<p>Still readable.</p>', $output );
	}
}
