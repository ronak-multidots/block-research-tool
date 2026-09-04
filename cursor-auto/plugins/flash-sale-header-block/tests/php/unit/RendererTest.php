<?php
/**
 * Tests for the server-side renderer.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader\Tests\Unit;

use GlobalStore\FlashSaleHeader\Renderer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GlobalStore\FlashSaleHeader\Renderer
 */
final class RendererTest extends TestCase {

	/**
	 * Frozen "now" used by every test: 2030-01-01 00:00:00 UTC.
	 */
	private const NOW = 1893456000;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['gsfsh_test_timezone'] = 'UTC';
		$GLOBALS['gsfsh_test_filters']  = array();

		add_filter( 'gsfsh_current_time', static fn() => self::NOW );
	}

	protected function tearDown(): void {
		$GLOBALS['gsfsh_test_filters'] = array();
		parent::tearDown();
	}

	/**
	 * Attributes for a typical, fully configured block.
	 *
	 * @param array<string, mixed> $overrides Attribute overrides.
	 * @return array<string, mixed>
	 */
	private function attributes( array $overrides = array() ): array {
		return array_merge(
			array(
				'size'           => 'wide',
				'title'          => 'The Flash Sale',
				'subtitle'       => '£1 a month for 12 months',
				'countdownLabel' => 'Offer ends in',
				// 3 days, 12 hours, 48 minutes and 56 seconds after the frozen "now".
				'expiryDate'     => '2030-01-04T12:48:56',
				'ctaText'        => 'Subscribe now',
				'ctaUrl'         => 'https://example.com/subscribe',
				'finePrint'      => 'New subscribers only.',
				'imageUrl'       => 'https://example.com/cutout.png',
				'imageAlt'       => 'Party leaders',
			),
			$overrides
		);
	}

	public function test_it_renders_every_content_region(): void {
		$html = Renderer::render( $this->attributes() );

		$this->assertStringContainsString( '__title">The Flash Sale</h2>', $html );
		$this->assertStringContainsString( '£1 a month for 12 months', $html );
		$this->assertStringContainsString( 'Offer ends in', $html );
		$this->assertStringContainsString( 'href="https://example.com/subscribe"', $html );
		$this->assertStringContainsString( 'New subscribers only.', $html );
		$this->assertStringContainsString( 'src="https://example.com/cutout.png"', $html );
	}

	public function test_countdown_is_pre_calculated_server_side(): void {
		$html = Renderer::render( $this->attributes() );

		$this->assertStringContainsString( 'data-gsfsh-unit="days">03<', $html );
		$this->assertStringContainsString( 'data-gsfsh-unit="hours">12<', $html );
		$this->assertStringContainsString( 'data-gsfsh-unit="minutes">48<', $html );
		$this->assertStringContainsString( 'data-gsfsh-unit="seconds">56<', $html );
	}

	public function test_it_exposes_the_expiry_timestamp_for_hydration(): void {
		$html = Renderer::render( $this->attributes() );

		$expected = ( new \DateTimeImmutable( '2030-01-04T12:48:56', new \DateTimeZone( 'UTC' ) ) )->getTimestamp();

		$this->assertStringContainsString( 'data-expiry="' . $expected . '"', $html );
		$this->assertStringContainsString( 'data-server-now="' . self::NOW . '"', $html );
	}

	public function test_expired_offers_show_the_expired_message(): void {
		$html = Renderer::render(
			$this->attributes(
				array(
					'expiryDate'     => '2029-12-31T23:00:00',
					'expiredMessage' => 'This offer has ended.',
				)
			)
		);

		$this->assertStringContainsString( 'data-gsfsh-units hidden', $html );
		$this->assertStringContainsString( 'data-gsfsh-expired>This offer has ended.', $html );
	}

	public function test_expired_offers_can_be_hidden_entirely(): void {
		$html = Renderer::render(
			$this->attributes(
				array(
					'expiryDate'      => '2029-12-31T23:00:00',
					'hideWhenExpired' => true,
				)
			)
		);

		$this->assertSame( '', $html );
	}

	public function test_countdown_is_omitted_when_no_expiry_is_set(): void {
		$html = Renderer::render( $this->attributes( array( 'expiryDate' => '' ) ) );

		$this->assertStringNotContainsString( 'data-gsfsh-countdown', $html );
		$this->assertStringContainsString( 'The Flash Sale', $html );
	}

	public function test_title_is_escaped(): void {
		$html = Renderer::render(
			$this->attributes( array( 'title' => 'Sale <script>alert("xss")</script>' ) )
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( 'alert("xss")', $html );
	}

	public function test_unsafe_cta_url_removes_the_button(): void {
		$html = Renderer::render(
			$this->attributes( array( 'ctaUrl' => 'javascript:alert(1)' ) )
		);

		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( '__cta"', $html );
	}

	public function test_image_alt_is_escaped_and_cannot_break_out_of_the_attribute(): void {
		$html = Renderer::render(
			$this->attributes( array( 'imageAlt' => '" onerror="alert(1)' ) )
		);

		$this->assertStringNotContainsString( 'onerror="alert(1)"', $html );
		$this->assertStringContainsString( '&quot; onerror=&quot;alert(1)', $html );
	}

	public function test_decorative_images_are_hidden_from_assistive_technology(): void {
		$html = Renderer::render( $this->attributes( array( 'imageAlt' => '' ) ) );

		$this->assertStringContainsString( 'aria-hidden="true"', $html );
	}

	public function test_wrapper_classes_reflect_the_chosen_size(): void {
		$attrs = \GlobalStore\FlashSaleHeader\Attributes::sanitize( $this->attributes( array( 'size' => 'tall' ) ) );

		$this->assertContains( 'is-size-tall', Renderer::wrapper_classes( $attrs ) );
		$this->assertContains( 'has-cutout-image', Renderer::wrapper_classes( $attrs ) );
	}

	public function test_wrapper_helpers_tolerate_a_partial_attribute_array(): void {
		$this->assertSame( array( 'is-size-auto' ), Renderer::wrapper_classes( array() ) );
		$this->assertSame( '', Renderer::wrapper_styles( array() ) );
		$this->assertContains(
			'has-cutout-image',
			Renderer::wrapper_classes( array( 'imageUrl' => 'https://example.com/a.png' ) )
		);
	}

	public function test_wrapper_classes_default_to_auto_for_invalid_sizes(): void {
		$attrs = \GlobalStore\FlashSaleHeader\Attributes::sanitize( $this->attributes( array( 'size' => 'enormous' ) ) );

		$this->assertContains( 'is-size-auto', Renderer::wrapper_classes( $attrs ) );
	}

	public function test_wrapper_styles_only_emit_validated_values(): void {
		$attrs = \GlobalStore\FlashSaleHeader\Attributes::sanitize(
			$this->attributes(
				array(
					'accentColor'   => '#ff0000',
					'imagePosition' => 'top',
				)
			)
		);

		$this->assertSame( '--gsfsh-accent:#ff0000;--gsfsh-image-position:top', Renderer::wrapper_styles( $attrs ) );

		$hostile = \GlobalStore\FlashSaleHeader\Attributes::sanitize(
			$this->attributes(
				array(
					'accentColor'   => 'red;}body{display:none',
					'imagePosition' => 'top;}body{display:none',
				)
			)
		);

		$this->assertSame( '', Renderer::wrapper_styles( $hostile ) );
	}

	public function test_wrapper_attributes_are_passed_through_untouched(): void {
		$html = Renderer::render( $this->attributes(), 'class="custom" id="promo"' );

		$this->assertStringStartsWith( '<div class="custom" id="promo">', $html );
	}

	/**
	 * @dataProvider provide_durations
	 */
	public function test_split_duration( int $seconds, array $expected ): void {
		$this->assertSame( $expected, Renderer::split_duration( $seconds ) );
	}

	/**
	 * @return array<string, array{0: int, 1: array<string, int>}>
	 */
	public static function provide_durations(): array {
		return array(
			'zero'     => array(
				0,
				array(
					'days'    => 0,
					'hours'   => 0,
					'minutes' => 0,
					'seconds' => 0,
				),
			),
			'mixed'    => array(
				( 3 * DAY_IN_SECONDS ) + ( 12 * HOUR_IN_SECONDS ) + ( 48 * MINUTE_IN_SECONDS ) + 56,
				array(
					'days'    => 3,
					'hours'   => 12,
					'minutes' => 48,
					'seconds' => 56,
				),
			),
			'negative' => array(
				-100,
				array(
					'days'    => 0,
					'hours'   => 0,
					'minutes' => 0,
					'seconds' => 0,
				),
			),
		);
	}

	public function test_screen_reader_summary_replaces_the_template_tokens(): void {
		$summary = Renderer::describe_remaining( ( 2 * DAY_IN_SECONDS ) + ( 3 * HOUR_IN_SECONDS ) + 240, false, 'Ended' );

		$this->assertSame( 'Offer ends in 2 days, 3 hours and 4 minutes.', $summary );
		$this->assertSame( 'Ended', Renderer::describe_remaining( 0, true, 'Ended' ) );
	}
}
