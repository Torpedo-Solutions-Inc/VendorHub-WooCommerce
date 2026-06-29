<?php
/**
 * REST callback route for VendorHub outbound order updates.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers vendorhub/v1 REST routes.
 */
class VendorHub_REST {

	const TRACKING_META_KEY = '_vendorhub_tracking';

	/**
	 * Register REST routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register routes on rest_api_init.
	 */
	public static function register_routes() {
		register_rest_route(
			'vendorhub/v1',
			'/order/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'handle_order_update' ),
					'permission_callback' => array( __CLASS__, 'verify_request' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => function ( $value ) {
								return is_numeric( $value ) && (int) $value > 0;
							},
						),
					),
				),
			)
		);

		register_rest_route(
			'vendorhub/v1',
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'handle_settings_update' ),
					'permission_callback' => array( __CLASS__, 'verify_request' ),
				),
			)
		);

		register_rest_route(
			'vendorhub/v1',
			'/product-meta-keys',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'handle_product_meta_keys' ),
					'permission_callback' => array( __CLASS__, 'verify_request' ),
				),
			)
		);
	}

	/**
	 * Verify Bearer token and HMAC signature.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function verify_request( $request ) {
		$api_token = get_option( 'vendorhub_api_token', '' );
		if ( empty( $api_token ) ) {
			return new WP_Error( 'vendorhub_not_configured', __( 'VendorHub is not connected.', 'vendorhub-woocommerce' ), array( 'status' => 503 ) );
		}

		$auth = $request->get_header( 'authorization' );
		if ( empty( $auth ) || ! preg_match( '/^Bearer\s+(.+)$/i', $auth, $matches ) ) {
			return new WP_Error( 'vendorhub_unauthorized', __( 'Missing authorization.', 'vendorhub-woocommerce' ), array( 'status' => 401 ) );
		}

		if ( ! hash_equals( $api_token, trim( $matches[1] ) ) ) {
			return new WP_Error( 'vendorhub_unauthorized', __( 'Invalid API token.', 'vendorhub-woocommerce' ), array( 'status' => 401 ) );
		}

		$timestamp = $request->get_header( 'x-vendorhub-timestamp' );
		$signature = $request->get_header( 'x-vendorhub-signature' );
		$raw_body  = $request->get_body();

		if ( empty( $timestamp ) || empty( $signature ) ) {
			return new WP_Error( 'vendorhub_bad_request', __( 'Missing signature headers.', 'vendorhub-woocommerce' ), array( 'status' => 400 ) );
		}

		if ( ! VendorHub_HMAC::verify( $api_token, $timestamp, $raw_body, $signature ) ) {
			return new WP_Error( 'vendorhub_invalid_signature', __( 'Invalid signature or timestamp.', 'vendorhub-woocommerce' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Apply status, note, and tracking from VendorHub.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_order_update( $request ) {
		$order_id = (int) $request['id'];
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'vendorhub_order_not_found', __( 'Order not found.', 'vendorhub-woocommerce' ), array( 'status' => 404 ) );
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$status   = isset( $params['status'] ) ? sanitize_key( $params['status'] ) : '';
		$note     = isset( $params['note'] ) ? sanitize_textarea_field( $params['note'] ) : '';
		$tracking = isset( $params['tracking'] ) ? sanitize_text_field( $params['tracking'] ) : '';

		if ( $status && self::is_valid_wc_status( $status ) ) {
			$order->update_status( $status, __( 'Updated by VendorHub.', 'vendorhub-woocommerce' ), true );
		}

		if ( $note ) {
			$order->add_order_note( $note, false, true );
		}

		if ( $tracking ) {
			$order->update_meta_data( self::TRACKING_META_KEY, $tracking );
			$order->save();
		}

		VendorHub_Connect::log( 'Applied VendorHub callback to order ' . $order_id );

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'orderId' => $order_id,
			),
			200
		);
	}

	/**
	 * Validate WooCommerce order status slug.
	 *
	 * @param string $status Status slug.
	 * @return bool
	 */
	private static function is_valid_wc_status( $status ) {
		$allowed = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );
		$status  = str_replace( 'wc-', '', $status );
		return in_array( $status, $allowed, true );
	}

	/**
	 * Update plugin settings from VendorHub (vendor meta key).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_settings_update( $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) || ! isset( $params['vendorMetaKey'] ) ) {
			return new WP_Error(
				'vendorhub_bad_request',
				__( 'Missing vendorMetaKey.', 'vendorhub-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$key = sanitize_text_field( (string) $params['vendorMetaKey'] );
		if ( ! VendorHub_Vendor_Meta::set_vendor_meta_key( $key ) ) {
			return new WP_Error(
				'vendorhub_invalid_vendor_meta_key',
				__( 'Invalid vendorMetaKey.', 'vendorhub-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		VendorHub_Connect::log( 'Updated vendor meta key to ' . $key );

		return new WP_REST_Response(
			array(
				'ok'            => true,
				'vendorMetaKey' => $key,
			),
			200
		);
	}

	/**
	 * List distinct product meta keys for VendorHub vendor mapping UI.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle_product_meta_keys( $request ) {
		return new WP_REST_Response(
			array(
				'keys' => VendorHub_Vendor_Meta::get_product_meta_keys(),
			),
			200
		);
	}
}
