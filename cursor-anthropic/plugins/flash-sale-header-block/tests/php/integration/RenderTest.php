<?php
/**
 * Verifies render.php output against a real WordPress install.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader\Tests\Integration;

use WP_UnitTestCase;

/**
 * @group render
 */
final class RenderTest extends WP_UnitTestCase {

	private const BLOCK_NAME = 'global-store/flash-sale-header';

	/**
	 * Frozen "now": 2030-01-01 00:00:00 UTC.
	 */
	private const NOW = 1893456000;

	public function set_up(): void {
		parent::set_up();

		update_option( 'timezone_string', 'Europe/London' );
		add_filter( 'gsfsh_current_time', static fn() => self::NOW );
	}

	/**
	 * Render the block through the core block renderer.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	private function render( array $attributes ): string {
		return render_block(
			array(
				'blockName'    => self::BLOCK_NAME,
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	public function test_it_renders_the_wrapper_with_the_size_class(): void {
		$html = $this->render(
			array(
				'size'  => 'wide',
				'title' => 'The Flash Sale',
			)
		);

		$this->assertStringContainsString( 'wp-block-global-store-flash-sale-header', $html );
		$this->assertStringContainsString( 'is-size-wide', $html );
	}

	public function test_invalid_size_falls_back_to_auto(): void {
		$html = $this->render( array( 'size' => 'wide" onmouseover="alert(1)' ) );

		$this->assertStringContainsString( 'is-size-auto', $html );
		$this->assertStringNotContainsString( 'onmouseover', $html );
	}

	public function test_rich_text_is_filtered_through_kses(): void {
		$html = $this->render(
			array(
				'title'     => 'Flash <strong>Sale</strong><img src=x onerror=alert(1)>',
				'finePrint' => '<a href="javascript:alert(1)">terms</a>',
			)
		);

		$this->assertStringContainsString( '<strong>Sale</strong>', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
	}

	public function test_cta_link_is_escaped_and_gains_safe_rel_attributes(): void {
		$html = $this->render(
			array(
				'ctaText'          => 'Subscribe now',
				'ctaUrl'           => 'https://example.com/offer?a=1&b=2',
				'ctaOpensInNewTab' => true,
			)
		);

		$this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
		$this->assertStringContainsString( 'target="_blank"', $html );
		$this->assertStringContainsString( 'a=1&#038;b=2', $html );
	}

	public function test_expiry_is_converted_from_the_site_timezone_to_utc(): void {
		// 2030-06-01 is BST, so 12:00 local is 11:00 UTC.
		$html = $this->render( array( 'expiryDate' => '2030-06-01T12:00:00' ) );

		$expected = ( new \DateTimeImmutable( '2030-06-01T11:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();

		$this->assertStringContainsString( 'data-expiry="' . $expected . '"', $html );
	}

	public function test_attachment_images_are_rendered_with_srcset(): void {
		$attachment_id = $this->factory()->attachment->create_upload_object(
			DIR_TESTDATA . '/images/canola.jpg'
		);

		$html = $this->render(
			array(
				'imageId'  => $attachment_id,
				'imageAlt' => 'Party leaders',
			)
		);

		$this->assertStringContainsString( 'alt="Party leaders"', $html );
		$this->assertStringContainsString( 'wp-block-global-store-flash-sale-header__image', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );

		wp_delete_attachment( $attachment_id, true );
	}

	public function test_countdown_markup_is_complete_without_javascript(): void {
		$html = $this->render( array( 'expiryDate' => '2030-01-04T12:48:56' ) );

		foreach ( array( 'days', 'hours', 'minutes', 'seconds' ) as $unit ) {
			$this->assertStringContainsString( 'data-gsfsh-unit="' . $unit . '"', $html );
		}

		$this->assertStringContainsString( 'aria-live="polite"', $html );
		$this->assertStringContainsString( 'data-sync-url="' . esc_url( rest_url( 'global-store/v1/flash-sale/time' ) ) . '"', $html );
	}

	public function test_serialized_post_content_renders_end_to_end(): void {
		$content = '<!-- wp:global-store/flash-sale-header {"size":"medium","title":"The Flash Sale","ctaText":"Subscribe now","ctaUrl":"https://example.com"} /-->';

		$html = do_blocks( $content );

		$this->assertStringContainsString( 'is-size-medium', $html );
		$this->assertStringContainsString( 'The Flash Sale', $html );
		$this->assertStringContainsString( 'href="https://example.com"', $html );
	}

	public function test_expired_offer_can_remove_itself_from_the_page(): void {
		$html = $this->render(
			array(
				'expiryDate'      => '2029-01-01T00:00:00',
				'hideWhenExpired' => true,
				'title'           => 'The Flash Sale',
			)
		);

		$this->assertSame( '', trim( $html ) );
	}
}
