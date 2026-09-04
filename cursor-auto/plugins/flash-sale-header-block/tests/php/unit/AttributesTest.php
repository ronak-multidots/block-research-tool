<?php
/**
 * Tests for attribute sanitisation.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader\Tests\Unit;

use GlobalStore\FlashSaleHeader\Attributes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GlobalStore\FlashSaleHeader\Attributes
 */
final class AttributesTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['gsfsh_test_timezone'] = 'UTC';
	}

	public function test_defaults_are_applied_to_an_empty_attribute_set(): void {
		$attrs = Attributes::sanitize( array() );

		$this->assertSame( 'auto', $attrs['size'] );
		$this->assertSame( '', $attrs['title'] );
		$this->assertSame( 0, $attrs['imageId'] );
		$this->assertFalse( $attrs['ctaOpensInNewTab'] );
		$this->assertSame( 'center', $attrs['imagePosition'] );
	}

	/**
	 * @dataProvider provide_sizes
	 */
	public function test_size_falls_back_to_auto_for_unknown_values( $input, string $expected ): void {
		$this->assertSame( $expected, Attributes::sanitize( array( 'size' => $input ) )['size'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function provide_sizes(): array {
		return array(
			'wide'            => array( 'wide', 'wide' ),
			'medium'          => array( 'medium', 'medium' ),
			'tall'            => array( 'tall', 'tall' ),
			'uppercase input' => array( 'WIDE', 'wide' ),
			'padded input'    => array( '  tall ', 'tall' ),
			'unknown string'  => array( 'gigantic', 'auto' ),
			'injection'       => array( 'wide" onload="alert(1)', 'auto' ),
			'array'           => array( array( 'wide' ), 'auto' ),
			'null'            => array( null, 'auto' ),
		);
	}

	public function test_title_keeps_inline_formatting_but_drops_scripts(): void {
		$attrs = Attributes::sanitize(
			array( 'title' => 'The <strong>Flash</strong> Sale<script>alert(1)</script>' )
		);

		$this->assertSame( 'The <strong>Flash</strong> Sale', $attrs['title'] );
	}

	public function test_inline_html_strips_event_handlers(): void {
		$attrs = Attributes::sanitize(
			array( 'finePrint' => '<span onclick="steal()">Terms</span> apply' )
		);

		$this->assertStringNotContainsString( 'onclick', $attrs['finePrint'] );
		$this->assertStringContainsString( 'Terms', $attrs['finePrint'] );
	}

	public function test_plain_text_fields_have_all_markup_removed(): void {
		$attrs = Attributes::sanitize(
			array( 'ctaText' => '<b>Subscribe</b> <script>alert(1)</script>now' )
		);

		$this->assertSame( 'Subscribe now', $attrs['ctaText'] );
	}

	/**
	 * @dataProvider provide_urls
	 */
	public function test_cta_url_only_accepts_safe_protocols( string $input, string $expected ): void {
		$this->assertSame( $expected, Attributes::sanitize( array( 'ctaUrl' => $input ) )['ctaUrl'] );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_urls(): array {
		return array(
			'https'      => array( 'https://example.com/offer', 'https://example.com/offer' ),
			'http'       => array( 'http://example.com', 'http://example.com' ),
			'mailto'     => array( 'mailto:sales@example.com', 'mailto:sales@example.com' ),
			'relative'   => array( '/subscribe', '/subscribe' ),
			'javascript' => array( 'javascript:alert(1)', '' ),
			'data uri'   => array( 'data:text/html;base64,PHNjcmlwdD4=', '' ),
			'empty'      => array( '', '' ),
		);
	}

	/**
	 * @dataProvider provide_colors
	 */
	public function test_accent_colour_accepts_hex_and_custom_properties( string $input, string $expected ): void {
		$this->assertSame( $expected, Attributes::sanitize( array( 'accentColor' => $input ) )['accentColor'] );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_colors(): array {
		return array(
			'six digit hex'   => array( '#f8b800', '#f8b800' ),
			'three digit hex' => array( '#fb0', '#fb0' ),
			'custom property' => array( 'var(--wp--preset--color--primary)', 'var(--wp--preset--color--primary)' ),
			'css injection'   => array( 'red;background:url(evil)', '' ),
			'expression'      => array( 'expression(alert(1))', '' ),
			'empty'           => array( '', '' ),
		);
	}

	public function test_image_id_is_coerced_to_a_positive_integer(): void {
		$this->assertSame( 42, Attributes::sanitize( array( 'imageId' => '42' ) )['imageId'] );
		$this->assertSame( 0, Attributes::sanitize( array( 'imageId' => '-1' ) )['imageId'] );
		$this->assertSame( 0, Attributes::sanitize( array( 'imageId' => 'abc' ) )['imageId'] );
	}

	public function test_expiry_date_is_interpreted_in_the_site_timezone(): void {
		$GLOBALS['gsfsh_test_timezone'] = 'Europe/London';

		// 2030-06-01 is BST (UTC+1), so noon local is 11:00 UTC.
		$this->assertSame(
			( new \DateTimeImmutable( '2030-06-01T11:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp(),
			Attributes::to_timestamp( '2030-06-01T12:00:00' )
		);
	}

	public function test_expiry_date_honours_an_explicit_offset(): void {
		$GLOBALS['gsfsh_test_timezone'] = 'Europe/London';

		$this->assertSame(
			( new \DateTimeImmutable( '2030-06-01T12:00:00', new \DateTimeZone( 'UTC' ) ) )->getTimestamp(),
			Attributes::to_timestamp( '2030-06-01T12:00:00Z' )
		);
	}

	/**
	 * @dataProvider provide_invalid_dates
	 */
	public function test_invalid_expiry_dates_are_discarded( string $input ): void {
		$this->assertNull( Attributes::to_timestamp( $input ) );
		$this->assertSame( '', Attributes::sanitize( array( 'expiryDate' => $input ) )['expiryDate'] );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function provide_invalid_dates(): array {
		return array(
			'empty'        => array( '' ),
			'prose'        => array( 'next tuesday' ),
			'sql fragment' => array( "2030-01-01' OR 1=1--" ),
			'month 13'     => array( '2030-13-01T00:00:00' ),
			'partial'      => array( '2030-01' ),
		);
	}

	public function test_valid_expiry_date_is_preserved_verbatim(): void {
		$this->assertSame(
			'2030-01-01T09:30:00',
			Attributes::sanitize( array( 'expiryDate' => '2030-01-01T09:30:00' ) )['expiryDate']
		);
	}
}
