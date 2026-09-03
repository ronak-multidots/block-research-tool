<?php
/**
 * Unit tests for the markup builder.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock\Tests\Unit;

use GlobalStore\TabsBlock\Renderer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GlobalStore\TabsBlock\Renderer
 */
final class RendererTest extends TestCase {

	/**
	 * Build a tab in the shape `Renderer::render()` expects.
	 *
	 * @param array<string, mixed> $overrides Values to change.
	 * @return array<string, mixed>
	 */
	private function tab( array $overrides = array() ): array {
		return array_merge(
			array(
				'label'   => 'Our Mission',
				'icon'    => 'target',
				'slug'    => '',
				'content' => '<p>Serving people.</p>',
			),
			$overrides
		);
	}

	public function test_a_block_without_tabs_renders_nothing(): void {
		$this->assertSame( '', Renderer::render( array(), array() ) );
	}

	public function test_non_array_tabs_are_ignored(): void {
		$this->assertSame( '', Renderer::render( array(), array( 'not-a-tab', 42 ) ) );
	}

	public function test_every_tab_gets_a_button_and_a_panel(): void {
		$markup = Renderer::render(
			array(),
			array( $this->tab(), $this->tab( array( 'label' => 'Our Superpowers' ) ) )
		);

		$this->assertSame( 2, substr_count( $markup, 'role="tab"' ) );
		$this->assertSame( 2, substr_count( $markup, 'role="tabpanel"' ) );
		$this->assertStringContainsString( 'role="tablist"', $markup );
		$this->assertStringContainsString( 'Our Superpowers', $markup );
	}

	public function test_the_first_tab_is_open_by_default(): void {
		$markup = Renderer::render( array(), array( $this->tab(), $this->tab() ) );

		$this->assertSame( 1, substr_count( $markup, 'aria-selected="true"' ) );
		$this->assertSame( 1, substr_count( $markup, 'aria-selected="false"' ) );
		// Only the closed panel carries the `hidden` attribute.
		$this->assertSame( 1, substr_count( $markup, ' hidden>' ) );
	}

	public function test_the_default_tab_attribute_chooses_which_panel_opens(): void {
		$markup = Renderer::render(
			array( 'defaultActiveTab' => 1 ),
			array( $this->tab( array( 'label' => 'First' ) ), $this->tab( array( 'label' => 'Second' ) ) )
		);

		$this->assertMatchesRegularExpression(
			'/aria-selected="true"[^>]*>.*?Second/s',
			$markup
		);
	}

	public function test_a_default_tab_beyond_the_list_falls_back_to_the_last_tab(): void {
		$markup = Renderer::render(
			array( 'defaultActiveTab' => 9 ),
			array( $this->tab( array( 'label' => 'First' ) ), $this->tab( array( 'label' => 'Second' ) ) )
		);

		$this->assertMatchesRegularExpression(
			'/aria-selected="true"[^>]*>.*?Second/s',
			$markup
		);
	}

	public function test_each_button_points_at_the_panel_that_points_back(): void {
		$markup = Renderer::render( array(), array( $this->tab(), $this->tab() ) );

		// The trailing space keeps `role="tabpanel"` out of the match.
		preg_match_all( '/id="([^"]+)" role="tab" /', $markup, $tab_ids );
		preg_match_all( '/aria-controls="([^"]+)"/', $markup, $panel_ids );
		preg_match_all( '/aria-labelledby="([^"]+)"/', $markup, $labelled_by );

		$this->assertCount( 2, $tab_ids[1] );
		$this->assertSame( $tab_ids[1], $labelled_by[1] );

		foreach ( $panel_ids[1] as $index => $panel_id ) {
			$this->assertStringContainsString( 'id="' . $panel_id . '" role="tabpanel"', $markup );
			$this->assertNotSame( $panel_id, $tab_ids[1][ $index ] );
		}
	}

	public function test_ids_do_not_collide_between_two_blocks_on_one_page(): void {
		$first  = Renderer::render( array(), array( $this->tab() ) );
		$second = Renderer::render( array(), array( $this->tab() ) );

		preg_match( '/id="([^"]+)" role="tab" /', $first, $first_id );
		preg_match( '/id="([^"]+)" role="tab" /', $second, $second_id );

		$this->assertNotSame( $first_id[1], $second_id[1] );
	}

	public function test_panel_content_is_passed_through_untouched(): void {
		$markup = Renderer::render(
			array(),
			array( $this->tab( array( 'content' => '<figure class="wp-block-image"><img src="a.png" alt="" /></figure>' ) ) )
		);

		$this->assertStringContainsString(
			'<figure class="wp-block-image"><img src="a.png" alt="" /></figure>',
			$markup
		);
	}

	public function test_an_untitled_tab_is_numbered(): void {
		$markup = Renderer::render(
			array(),
			array( $this->tab( array( 'label' => '' ) ), $this->tab( array( 'label' => '' ) ) )
		);

		$this->assertStringContainsString( 'Tab 1', $markup );
		$this->assertStringContainsString( 'Tab 2', $markup );
	}

	public function test_labels_are_escaped(): void {
		$markup = Renderer::render(
			array(),
			array( $this->tab( array( 'label' => 'Sale "&" more' ) ) )
		);

		$this->assertStringContainsString( 'Sale &quot;&amp;&quot; more', $markup );
	}

	public function test_icons_are_rendered_only_when_the_block_asks_for_them(): void {
		$with = Renderer::render( array( 'showIcons' => true ), array( $this->tab() ) );
		$without = Renderer::render( array( 'showIcons' => false ), array( $this->tab() ) );

		$this->assertStringContainsString( '<svg', $with );
		$this->assertStringNotContainsString( '<svg', $without );
	}

	public function test_a_tab_without_an_icon_renders_no_icon_wrapper(): void {
		$markup = Renderer::render( array(), array( $this->tab( array( 'icon' => 'none' ) ) ) );

		$this->assertStringNotContainsString( '__tab-icon', $markup );
	}

	public function test_slugs_are_exposed_for_deep_links(): void {
		$markup = Renderer::render(
			array(),
			array( $this->tab( array( 'slug' => 'mission' ) ) )
		);

		$this->assertStringContainsString( 'data-gstb-slug="mission"', $markup );
	}

	public function test_a_repeated_slug_is_dropped_so_deep_links_stay_unambiguous(): void {
		$markup = Renderer::render(
			array(),
			array(
				$this->tab( array( 'slug' => 'mission' ) ),
				$this->tab( array( 'slug' => 'mission' ) ),
			)
		);

		$this->assertSame( 1, substr_count( $markup, 'data-gstb-slug="mission"' ) );
	}

	public function test_the_tablist_is_labelled_and_reports_its_orientation(): void {
		$markup = Renderer::render(
			array(
				'orientation'     => 'vertical',
				'accessibleLabel' => 'Product details',
			),
			array( $this->tab() )
		);

		$this->assertStringContainsString( 'aria-label="Product details"', $markup );
		$this->assertStringContainsString( 'aria-orientation="vertical"', $markup );
	}

	public function test_the_tablist_falls_back_to_a_generic_label(): void {
		$markup = Renderer::render( array(), array( $this->tab() ) );

		$this->assertStringContainsString( 'aria-label="Tabs"', $markup );
	}

	public function test_wrapper_classes_describe_the_chosen_layout(): void {
		$classes = Renderer::wrapper_classes(
			array(
				'orientation' => 'vertical',
				'tabStyle'    => 'pills',
				'alignment'   => 'start',
				'showIcons'   => false,
				'accentColor' => '#ee6c2d',
			)
		);

		$this->assertContains( 'is-orientation-vertical', $classes );
		$this->assertContains( 'is-tab-style-pills', $classes );
		$this->assertContains( 'is-tabs-aligned-start', $classes );
		$this->assertContains( 'has-custom-accent', $classes );
		$this->assertNotContains( 'has-tab-icons', $classes );
	}

	public function test_the_accent_colour_becomes_a_custom_property(): void {
		$this->assertSame(
			'--gstb-accent:#ee6c2d',
			Renderer::wrapper_styles( array( 'accentColor' => '#ee6c2d' ) )
		);
	}

	public function test_no_inline_style_is_emitted_without_an_accent_colour(): void {
		$this->assertSame( '', Renderer::wrapper_styles( array() ) );
		$this->assertSame( '', Renderer::wrapper_styles( array( 'accentColor' => 'chartreuse' ) ) );
	}

	/**
	 * @dataProvider provide_active_indexes
	 */
	public function test_the_active_index_is_clamped_to_the_list( int $requested, int $total, int $expected ): void {
		$this->assertSame( $expected, Renderer::active_index( $requested, $total ) );
	}

	/**
	 * @return array<string, array{0: int, 1: int, 2: int}>
	 */
	public function provide_active_indexes(): array {
		return array(
			'inside the list' => array( 1, 3, 1 ),
			'past the end'    => array( 7, 3, 2 ),
			'negative'        => array( -2, 3, 0 ),
			'empty list'      => array( 2, 0, 0 ),
		);
	}
}
