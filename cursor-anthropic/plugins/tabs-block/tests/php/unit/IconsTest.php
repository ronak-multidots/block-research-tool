<?php
/**
 * Unit tests for the icon library.
 *
 * @package GlobalStore\TabsBlock\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\TabsBlock\Tests\Unit;

use GlobalStore\TabsBlock\Icons;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GlobalStore\TabsBlock\Icons
 */
final class IconsTest extends TestCase {

	public function test_none_is_always_an_accepted_value(): void {
		$this->assertContains( Icons::NONE, Icons::names() );
		$this->assertTrue( Icons::is_valid( Icons::NONE ) );
	}

	public function test_an_unknown_name_is_rejected(): void {
		$this->assertFalse( Icons::is_valid( 'unicorn' ) );
	}

	public function test_none_and_unknown_names_draw_nothing(): void {
		$this->assertSame( '', Icons::markup( Icons::NONE ) );
		$this->assertSame( '', Icons::markup( 'unicorn' ) );
	}

	public function test_an_icon_is_drawn_as_a_decorative_svg(): void {
		$markup = Icons::markup( 'target', 'my-icon' );

		$this->assertStringContainsString( '<svg class="my-icon"', $markup );
		$this->assertStringContainsString( 'viewBox="0 0 24 24"', $markup );
		$this->assertStringContainsString( 'aria-hidden="true"', $markup );
		$this->assertStringContainsString( 'focusable="false"', $markup );
		$this->assertStringContainsString( '<path d="', $markup );
		$this->assertStringEndsWith( '</svg>', $markup );
	}

	public function test_every_icon_draws_at_least_one_path(): void {
		foreach ( Icons::names() as $name ) {
			if ( Icons::NONE === $name ) {
				continue;
			}

			$this->assertGreaterThan(
				0,
				substr_count( Icons::markup( $name ), '<path d="' ),
				"The {$name} icon has no path data."
			);
		}
	}

	/**
	 * The editor draws the same icons from `src/tabs/icons.js`, and the block metadata is
	 * the contract both sides answer to.
	 */
	public function test_the_icon_names_match_the_block_metadata(): void {
		$metadata = json_decode(
			(string) file_get_contents( dirname( __DIR__, 3 ) . '/src/tab/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions
			true
		);

		$this->assertSame( $metadata['attributes']['icon']['enum'], Icons::names() );
		$this->assertSame( $metadata['attributes']['icon']['default'], Icons::NONE );
	}
}
