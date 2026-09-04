<?php
/**
 * REST endpoints used by the Flash Sale Header block.
 *
 * @package GlobalStore\FlashSaleHeader
 */

declare( strict_types = 1 );

namespace GlobalStore\FlashSaleHeader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Registers the block's REST routes.
 *
 * Two routes exist and they have deliberately different security postures:
 *
 * - `/time` is an unauthenticated read of the server clock. It exposes nothing
 *   sensitive and exists so the front-end countdown can correct client clock skew
 *   on pages served from a full-page cache.
 * - `/validate-expiry` is an editor-only helper. It requires a valid REST nonce and
 *   the `edit_posts` capability because it is only ever called from the block editor.
 */
final class REST_Controller {

	/**
	 * REST namespace.
	 */
	public const ROUTE_NAMESPACE = 'global-store/v1';

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/flash-sale/time',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_server_time' ),
					'permission_callback' => '__return_true',
				),
				'schema' => array( $this, 'get_time_schema' ),
			)
		);

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/flash-sale/validate-expiry',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'validate_expiry' ),
					'permission_callback' => array( $this, 'check_editor_permission' ),
					'args'                => array(
						'expiryDate' => array(
							'type'              => 'string',
							'required'          => true,
							'description'       => __( 'Expiry date in the site timezone.', 'flash-sale-header-block' ),
							'sanitize_callback' => array( Attributes::class, 'sanitize_datetime' ),
							'validate_callback' => static function ( $value ) {
								return is_string( $value ) && null !== Attributes::to_timestamp( $value )
									? true
									: new WP_Error(
										'gsfsh_invalid_date',
										__( 'The expiry date is not a valid date.', 'flash-sale-header-block' ),
										array( 'status' => 400 )
									);
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Gate the editor-only route behind a valid nonce and the `edit_posts` capability.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return true|WP_Error
	 */
	public function check_editor_permission( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'gsfsh_invalid_nonce',
				__( 'The security token is missing or has expired. Please reload the editor.', 'flash-sale-header-block' ),
				array( 'status' => 403 )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'gsfsh_forbidden',
				__( 'You are not allowed to edit content on this site.', 'flash-sale-header-block' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Return the current server time so the front-end can correct clock skew.
	 *
	 * @return WP_REST_Response
	 */
	public function get_server_time(): WP_REST_Response {
		$response = new WP_REST_Response(
			array(
				'timestamp' => time(),
				'timezone'  => wp_timezone_string(),
			)
		);

		$response->header( 'Cache-Control', 'no-store, max-age=0' );

		return $response;
	}

	/**
	 * Validate an expiry date on behalf of the editor.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function validate_expiry( WP_REST_Request $request ) {
		$expiry    = (string) $request->get_param( 'expiryDate' );
		$timestamp = Attributes::to_timestamp( $expiry );

		if ( null === $timestamp ) {
			return new WP_Error(
				'gsfsh_invalid_date',
				__( 'The expiry date is not a valid date.', 'flash-sale-header-block' ),
				array( 'status' => 400 )
			);
		}

		$now = time();

		return new WP_REST_Response(
			array(
				'timestamp'        => $timestamp,
				'isPast'           => $timestamp <= $now,
				'secondsRemaining' => max( 0, $timestamp - $now ),
				'formatted'        => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ),
			)
		);
	}

	/**
	 * Schema for the `/time` route.
	 *
	 * @return array<string, mixed>
	 */
	public function get_time_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'gsfsh-server-time',
			'type'       => 'object',
			'properties' => array(
				'timestamp' => array(
					'type'        => 'integer',
					'description' => __( 'Current UTC timestamp on the server.', 'flash-sale-header-block' ),
					'readonly'    => true,
				),
				'timezone'  => array(
					'type'        => 'string',
					'description' => __( 'Site timezone string.', 'flash-sale-header-block' ),
					'readonly'    => true,
				),
			),
		);
	}
}
