<?php
/**
 * Unit tests for attribute sanitisation.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock\Tests\Unit;

use GlobalStore\TabsBlock\Attributes;
use GlobalStore\TabsBlock\Icons;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GlobalStore\TabsBlock\Attributes
 */
final class AttributesTest extends TestCase {

	public function test_defaults_are_applied_to_an_empty_attribute_set(): void {
		$attrs = Attributes::sanitize( array() );

		$this->assertSame( 0, $attrs['defaultActiveTab'] );
		$this->assertSame( 'horizontal', $attrs['orientation'] );
		$this->assertSame( 'underline', $attrs['tabStyle'] );
		$this->assertSame( 'center', $attrs['alignment'] );
		$this->assertTrue( $attrs['showIcons'] );
		$this->assertSame( '', $attrs['accentColor'] );
		$this->assertSame( '', $attrs['accessibleLabel'] );
	}

	/**
	 * @dataProvider provide_unknown_enum_values
	 */
	public function test_unknown_enum_values_fall_back_to_the_default( string $key, $value, string $expected ): void {
		$attrs = Attributes::sanitize( array( $key => $value ) );

		$this->assertSame( $expected, $attrs[ $key ] );
	}

	/**
	 * @return array<string, array{0: string, 1: mixed, 2: string}>
	 */
	public function provide_unknown_enum_values(): array {
		return array(
			'unknown orientation' => array( 'orientation', 'diagonal', 'horizontal' ),
			'orientation casing'  => array( 'orientation', 'VERTICAL', 'vertical' ),
			'unknown tab style'   => array( 'tabStyle', 'tabs', 'underline' ),
			'unknown alignment'   => array( 'alignment', 'justify', 'center' ),
			'array alignment'     => array( 'alignment', array( 'center' ), 'center' ),
		);
	}

	public function test_core_style_alignment_aliases_are_normalised(): void {
		$this->assertSame( 'start', Attributes::sanitize( array( 'alignment' => 'left' ) )['alignment'] );
		$this->assertSame( 'end', Attributes::sanitize( array( 'alignment' => 'right' ) )['alignment'] );
	}

	public function test_a_negative_default_tab_is_clamped_to_the_first_tab(): void {
		$this->assertSame( 0, Attributes::sanitize( array( 'defaultActiveTab' => -4 ) )['defaultActiveTab'] );
	}

	public function test_the_accessible_label_is_reduced_to_plain_text(): void {
		$attrs = Attributes::sanitize(
			array( 'accessibleLabel' => '<script>alert(1)</script>Product <b>tabs</b>' )
		);

		$this->assertSame( 'Product tabs', $attrs['accessibleLabel'] );
	}

	/**
	 * @dataProvider provide_colors
	 */
	public function test_only_hex_colors_and_custom_properties_are_accepted( $input, string $expected ): void {
		$this->assertSame( $expected, Attributes::sanitize( array( 'accentColor' => $input ) )['accentColor'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function provide_colors(): array {
		return array(
			'short hex'       => array( '#f60', '#f60' ),
			'long hex'        => array( '#ee6c2d', '#ee6c2d' ),
			'custom property' => array( 'var(--wp--preset--color--primary)', 'var(--wp--preset--color--primary)' ),
			'named colour'    => array( 'rebeccapurple', '' ),
			'javascript url'  => array( 'url(javascript:alert(1))', '' ),
			'not a string'    => array( array( '#fff' ), '' ),
		);
	}

	public function test_tab_defaults_are_applied_to_an_empty_attribute_set(): void {
		$tab = Attributes::sanitize_tab( array() );

		$this->assertSame( '', $tab['label'] );
		$this->assertSame( Icons::NONE, $tab['icon'] );
		$this->assertSame( '', $tab['slug'] );
	}

	public function test_an_unknown_icon_falls_back_to_none(): void {
		$this->assertSame( Icons::NONE, Attributes::sanitize_tab( array( 'icon' => 'unicorn' ) )['icon'] );
	}

	public function test_a_known_icon_survives_surrounding_whitespace(): void {
		$this->assertSame( 'target', Attributes::sanitize_tab( array( 'icon' => ' target ' ) )['icon'] );
	}

	public function test_the_tab_label_is_reduced_to_plain_text(): void {
		$tab = Attributes::sanitize_tab( array( 'label' => "Our  <em>Mission</em>\n" ) );

		$this->assertSame( 'Our Mission', $tab['label'] );
	}

	/**
	 * @dataProvider provide_slugs
	 */
	public function test_slugs_are_reduced_to_url_safe_fragments( $input, string $expected ): void {
		$this->assertSame( $expected, Attributes::sanitize_tab( array( 'slug' => $input ) )['slug'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function provide_slugs(): array {
		return array(
			'spaces'       => array( 'Our Mission', 'our-mission' ),
			'punctuation'  => array( 'What We Stand For!', 'what-we-stand-for' ),
			'markup'       => array( '<b>pricing</b>', 'pricing' ),
			'nothing left' => array( '///', '' ),
			'not a string' => array( null, '' ),
		);
	}
}
