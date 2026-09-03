<?php
/**
 * Verifies the REST routes, their capability checks and nonce handling.
 *
 * @package GlobalStore\FlashSaleHeader\Tests
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader\Tests\Integration;

use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

/**
 * @group rest
 */
final class RestControllerTest extends WP_UnitTestCase {

	private const VALIDATE_ROUTE = '/global-store/v1/flash-sale/validate-expiry';
	private const TIME_ROUTE     = '/global-store/v1/flash-sale/time';

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	public function set_up(): void {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init', $this->server );
	}

	public function tear_down(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Build a validation request for the given user.
	 *
	 * @param string $expiry Expiry value.
	 * @param bool   $nonce  Whether to attach a valid nonce.
	 * @return WP_REST_Request
	 */
	private function validate_request( string $expiry, bool $nonce = true ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', self::VALIDATE_ROUTE );
		$request->set_param( 'expiryDate', $expiry );

		if ( $nonce ) {
			$request->add_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		}

		return $request;
	}

	public function test_routes_are_registered(): void {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( self::TIME_ROUTE, $routes );
		$this->assertArrayHasKey( self::VALIDATE_ROUTE, $routes );
	}

	public function test_time_route_is_public_and_uncached(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::TIME_ROUTE ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsInt( $response->get_data()['timestamp'] );
		$this->assertSame( 'no-store, max-age=0', $response->get_headers()['Cache-Control'] );
	}

	public function test_validation_route_rejects_anonymous_requests(): void {
		$response = $this->server->dispatch( $this->validate_request( '2030-01-01T00:00:00', false ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'gsfsh_invalid_nonce', $response->get_data()['code'] );
	}

	public function test_validation_route_rejects_a_missing_nonce_from_a_logged_in_editor(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->server->dispatch( $this->validate_request( '2030-01-01T00:00:00', false ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'gsfsh_invalid_nonce', $response->get_data()['code'] );
	}

	public function test_validation_route_rejects_users_without_edit_posts(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = $this->server->dispatch( $this->validate_request( '2030-01-01T00:00:00' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'gsfsh_forbidden', $response->get_data()['code'] );
	}

	public function test_validation_route_answers_editors(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->server->dispatch( $this->validate_request( '2030-01-01T00:00:00' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $data['isPast'] );
		$this->assertGreaterThan( 0, $data['secondsRemaining'] );
		$this->assertNotEmpty( $data['formatted'] );
	}

	public function test_validation_route_flags_dates_in_the_past(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->server->dispatch( $this->validate_request( '2001-01-01T00:00:00' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['isPast'] );
		$this->assertSame( 0, $response->get_data()['secondsRemaining'] );
	}

	public function test_validation_route_rejects_malformed_dates(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->server->dispatch( $this->validate_request( 'tomorrow-ish' ) );

		$this->assertSame( 400, $response->get_status() );
	}
}
