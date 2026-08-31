<?php
/**
 * Tests for the block's server-side render output (sanitization + escaping).
 *
 * @package GlobalStore\FlashSaleHeader
 */

/**
 * Verifies render.php sanitizes/escapes untrusted attribute values and
 * outputs the correct markup per layout.
 *
 * @coversNothing
 */
class RenderTest extends WP_UnitTestCase {

	/**
	 * Render the block with the given attributes, merged over the block's
	 * registered defaults.
	 *
	 * @param array $attributes Attributes to override.
	 * @return string Rendered HTML.
	 */
	private function render_block( array $attributes ) {
		return render_block(
			array(
				'blockName'    => 'global-store/flash-sale-header',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * With no attributes set, the block should render using its default (wide) layout.
	 */
	public function test_default_render_contains_wide_wrapper_class() {
		$html = $this->render_block( array() );

		$this->assertStringContainsString( 'is-size-wide', $html );
	}

	/**
	 * An attribute tampered with outside the declared enum must fall back to a safe default.
	 */
	public function test_invalid_size_falls_back_to_wide() {
		$html = $this->render_block( array( 'size' => 'huge' ) );

		$this->assertStringContainsString( 'is-size-wide', $html );
		$this->assertStringNotContainsString( 'is-size-huge', $html );
	}

	/**
	 * Title content is passed through wp_kses_post() and must strip <script> tags.
	 */
	public function test_script_tags_are_stripped_from_title() {
		$html = $this->render_block(
			array(
				'title' => 'Sale <script>alert(1)</script>',
			)
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'Sale', $html );
	}

	/**
	 * The CTA URL is passed through esc_url_raw()/esc_url() and must not allow the javascript: protocol.
	 */
	public function test_cta_url_strips_disallowed_protocol() {
		$html = $this->render_block(
			array(
				'size'    => 'medium',
				'ctaText' => 'Shop',
				'ctaUrl'  => 'javascript:alert(1)',
			)
		);

		$this->assertStringNotContainsString( 'javascript:alert', $html );
	}

	/**
	 * Per the reference design, the CTA button is shown on every layout.
	 */
	public function test_cta_is_shown_on_the_wide_layout() {
		$html = $this->render_block(
			array(
				'size'    => 'wide',
				'ctaText' => 'Shop',
				'ctaUrl'  => 'https://example.com',
			)
		);

		$this->assertStringContainsString( 'flash-sale-header__cta', $html );
		$this->assertStringContainsString( 'https://example.com', $html );
	}

	/**
	 * Per the reference design, the medium layout includes a CTA button.
	 */
	public function test_cta_is_shown_on_the_medium_layout() {
		$html = $this->render_block(
			array(
				'size'    => 'medium',
				'ctaText' => 'Shop',
				'ctaUrl'  => 'https://example.com',
			)
		);

		$this->assertStringContainsString( 'flash-sale-header__cta', $html );
		$this->assertStringContainsString( 'https://example.com', $html );
	}

	/**
	 * With no CTA text set, no CTA button should be rendered on any layout.
	 */
	public function test_cta_is_hidden_when_no_cta_text_is_set() {
		$html = $this->render_block(
			array(
				'size'    => 'wide',
				'ctaText' => '',
			)
		);

		$this->assertStringNotContainsString( 'flash-sale-header__cta', $html );
	}

	/**
	 * A valid expiry date/time should produce the data attributes the frontend script hydrates from.
	 */
	public function test_countdown_data_attribute_present_for_valid_expiry() {
		$html = $this->render_block(
			array(
				'expiryDateTime' => '2030-01-01T00:00:00',
			)
		);

		$this->assertStringContainsString( 'data-flash-sale-countdown', $html );
		$this->assertStringContainsString( 'data-expiry=', $html );
	}

	/**
	 * With no expiry date set, no countdown hydration markup should be output.
	 */
	public function test_countdown_markup_omitted_for_empty_expiry() {
		$html = $this->render_block( array() );

		$this->assertStringNotContainsString( 'data-flash-sale-countdown', $html );
	}

	/**
	 * The image alt attribute must be escaped to prevent attribute-breakout XSS.
	 */
	public function test_image_alt_attribute_is_escaped() {
		$html = $this->render_block(
			array(
				'imageUrl' => 'https://example.com/cutout.png',
				'imageAlt' => 'Trainers" onmouseover="alert(1)',
			)
		);

		// esc_attr() encodes the quote rather than stripping the attacker's
		// text, so "onmouseover=" is expected to survive as inert text; what
		// matters is that the quote can no longer break out of the alt
		// attribute to start a real onmouseover="" attribute.
		$this->assertStringContainsString( '&quot;', $html );
		$this->assertStringNotContainsString( 'onmouseover="alert(1)"', $html );
	}
}
