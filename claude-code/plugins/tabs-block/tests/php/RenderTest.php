<?php
/**
 * Tests for the Tabs/Tab blocks' server-side render output (sanitization,
 * escaping, and the nav-from-child-attributes assembly).
 *
 * @package GlobalStore\Tabs
 */

/**
 * Verifies render.php (for both blocks) sanitizes/escapes untrusted
 * attribute values and outputs correct, accessible markup.
 *
 * @coversNothing
 */
class RenderTest extends WP_UnitTestCase {

	/**
	 * Render a Tabs block with the given child Tab specs, each holding its
	 * own attributes and a single paragraph of inner content.
	 *
	 * @param array $tab_specs List of `[ 'attrs' => array, 'content' => string ]`.
	 * @return string Rendered HTML.
	 */
	private function render_tabs_block( array $tab_specs ) {
		$inner_blocks = array();

		foreach ( $tab_specs as $spec ) {
			$paragraph_html = '<p>' . $spec['content'] . '</p>';

			$inner_blocks[] = array(
				'blockName'    => 'global-store/tab',
				'attrs'        => $spec['attrs'],
				'innerBlocks'  => array(
					array(
						'blockName'    => 'core/paragraph',
						'attrs'        => array(),
						'innerBlocks'  => array(),
						'innerHTML'    => $paragraph_html,
						'innerContent' => array( $paragraph_html ),
					),
				),
				'innerHTML'    => '',
				'innerContent' => array( null ),
			);
		}

		return render_block(
			array(
				'blockName'    => 'global-store/tabs',
				'attrs'        => array(),
				'innerBlocks'  => $inner_blocks,
				'innerHTML'    => '',
				'innerContent' => array_fill( 0, count( $inner_blocks ), null ),
			)
		);
	}

	/**
	 * The outer wrapper should always carry the block's base class.
	 */
	public function test_wrapper_has_tabs_block_class() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId' => 'a',
						'title' => 'Tab A',
					),
					'content' => 'Content A',
				),
			)
		);

		$this->assertStringContainsString( 'tabs-block', $html );
	}

	/**
	 * The nav is built from each child Tab's own attributes, not from
	 * anything stored on the parent block.
	 */
	public function test_nav_contains_each_tab_title() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId' => 'a',
						'title' => 'Tab A',
					),
					'content' => 'Content A',
				),
				array(
					'attrs'   => array(
						'tabId' => 'b',
						'title' => 'Tab B',
					),
					'content' => 'Content B',
				),
			)
		);

		$this->assertStringContainsString( 'Tab A', $html );
		$this->assertStringContainsString( 'Tab B', $html );
	}

	/**
	 * The first tab is selected/active by default; the rest are not.
	 */
	public function test_first_tab_is_selected_by_default() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId' => 'a',
						'title' => 'Tab A',
					),
					'content' => 'Content A',
				),
				array(
					'attrs'   => array(
						'tabId' => 'b',
						'title' => 'Tab B',
					),
					'content' => 'Content B',
				),
			)
		);

		$this->assertMatchesRegularExpression( '/id="tabs-block-tab-a"[^>]*aria-selected="true"/', $html );
		$this->assertMatchesRegularExpression( '/id="tabs-block-tab-b"[^>]*aria-selected="false"/', $html );
	}

	/**
	 * Each nav button must reference its panel via aria-controls, and each
	 * panel must reference its button via aria-labelledby, using the same
	 * tab id on both sides.
	 */
	public function test_nav_button_and_panel_are_linked_by_aria_attributes() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId' => 'my-tab',
						'title' => 'My Tab',
					),
					'content' => 'Body',
				),
			)
		);

		$this->assertStringContainsString( 'aria-controls="tabs-block-panel-my-tab"', $html );
		$this->assertStringContainsString( 'id="tabs-block-panel-my-tab"', $html );
		$this->assertStringContainsString( 'aria-labelledby="tabs-block-tab-my-tab"', $html );
	}

	/**
	 * A tab's arbitrary inner blocks must be rendered inside its panel.
	 */
	public function test_tab_inner_content_is_rendered_in_panel() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId' => 'a',
						'title' => 'Tab A',
					),
					'content' => 'Distinctive paragraph text',
				),
			)
		);

		$this->assertStringContainsString( 'Distinctive paragraph text', $html );
	}

	/**
	 * Title content is stripped of tags before being escaped and echoed.
	 */
	public function test_script_tags_are_stripped_from_title() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId' => 'a',
						'title' => 'Tab <script>alert(1)</script>Title',
					),
					'content' => 'Body',
				),
			)
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'Tab', $html );
		$this->assertStringContainsString( 'Title', $html );
	}

	/**
	 * A Dashicon-type tab renders a span with the sanitized dashicon class,
	 * and enqueues the Dashicons stylesheet so the glyph actually shows.
	 */
	public function test_dashicon_icon_renders_and_enqueues_style() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId'    => 'a',
						'title'    => 'Tab A',
						'iconType' => 'dashicon',
						'dashicon' => 'dashicons-star-filled',
					),
					'content' => 'Body',
				),
			)
		);

		$this->assertStringContainsString( 'dashicons dashicons-star-filled', $html );
		$this->assertTrue( wp_style_is( 'dashicons', 'enqueued' ) );
	}

	/**
	 * An image-type tab renders an <img> whose src/alt are escaped, and the
	 * javascript: protocol is not allowed to survive esc_url_raw()/esc_url().
	 */
	public function test_image_icon_strips_disallowed_protocol() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId'    => 'a',
						'title'    => 'Tab A',
						'iconType' => 'image',
						'imageUrl' => 'javascript:alert(1)',
					),
					'content' => 'Body',
				),
			)
		);

		$this->assertStringNotContainsString( 'javascript:alert', $html );
	}

	/**
	 * The image alt attribute must be escaped to prevent attribute-breakout
	 * XSS via a crafted alt value.
	 */
	public function test_image_alt_attribute_is_escaped() {
		$html = $this->render_tabs_block(
			array(
				array(
					'attrs'   => array(
						'tabId'    => 'a',
						'title'    => 'Tab A',
						'iconType' => 'image',
						'imageUrl' => 'https://example.com/icon.png',
						'imageAlt' => 'Icon" onmouseover="alert(1)',
					),
					'content' => 'Body',
				),
			)
		);

		// esc_attr() encodes the quote rather than stripping the attacker's
		// text, so "onmouseover=" is expected to survive as inert text; what
		// matters is that the quote can no longer break out of the alt
		// attribute to start a real onmouseover="" attribute.
		$this->assertStringContainsString( '&quot;', $html );
		$this->assertStringNotContainsString( 'onmouseover="alert(1)"', $html );
	}

	/**
	 * With no tabs at all, no nav (and no Dashicons enqueue) should render.
	 */
	public function test_no_nav_when_there_are_no_tabs() {
		$html = $this->render_tabs_block( array() );

		$this->assertStringNotContainsString( 'tabs-block__nav', $html );
	}

	/**
	 * A Tab block rendered directly (without a saved tabId) must still
	 * produce a stable, unique id rather than an empty/broken attribute.
	 */
	public function test_tab_panel_falls_back_to_generated_id_when_missing() {
		$html = render_block(
			array(
				'blockName'    => 'global-store/tab',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);

		$this->assertMatchesRegularExpression( '/id="tabs-block-panel-tab-[a-zA-Z0-9]+"/', $html );
		$this->assertStringContainsString( 'role="tabpanel"', $html );
	}
}
