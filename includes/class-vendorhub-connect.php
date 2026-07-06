<?php

/**

 * VendorHub connect handshake.

 *

 * @package VendorHub_WooCommerce

 */



defined( 'ABSPATH' ) || exit;



/**

 * Handles store registration with VendorHub.

 */

class VendorHub_Connect {



	/**

	 * Perform connect handshake with VendorHub API (self-hosted / dev only).

	 *

	 * @return array{success:bool,message:string,store_id?:string}

	 */

	public static function connect() {

		$connect_secret = self::get_connect_secret();

		if ( empty( $connect_secret ) ) {

			return array(

				'success' => false,

				'message' => __(
					'Direct connect requires VENDORHUB_WC_CONNECT_SECRET in wp-config.php. Use the VendorHub redirect button or paste credentials from your VendorHub dashboard.',
					'vendorhub-for-woocommerce'
				),

			);

		}

		$site_url = self::get_site_url();

		$display_name = get_bloginfo( 'name' );

		$plugin_token = self::get_or_create_plugin_token();

		$timestamp = (string) (int) round( microtime( true ) * 1000 );

		$payload = array(
			'siteUrl'     => $site_url,
			'displayName' => $display_name,
			'pluginToken' => $plugin_token,
			'timestamp'   => $timestamp,
		);

		$signing_payload = wp_json_encode( $payload );

		if ( false === $signing_payload ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to encode connect payload.', 'vendorhub-for-woocommerce' ),
			);
		}

		$signature            = VendorHub_HMAC::sign( $connect_secret, $timestamp, $signing_payload );
		$payload['signature'] = $signature;
		$body                 = wp_json_encode( $payload );

		$api_base = VendorHub_Settings::get_api_base();

		$url = trailingslashit( $api_base ) . 'api/connect/woocommerce';

		$response = wp_safe_remote_post(
			$url,
			array(

				'timeout' => 30,

				'headers' => array(

					'Content-Type' => 'application/json',

				),

				'body'    => $body,

			)
		);

		if ( is_wp_error( $response ) ) {

			self::log( 'Connect failed: ' . $response->get_error_message() );

			return array(

				'success' => false,

				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			);

		}

		$code = wp_remote_retrieve_response_code( $response );

		$raw = wp_remote_retrieve_body( $response );

		$data = json_decode( $raw, true );

		if ( ! in_array( (int) $code, array( 200, 201 ), true ) || ! is_array( $data ) ) {

			$error = is_array( $data ) && isset( $data['error'] ) ? (string) $data['error'] : $raw;

			self::log( 'Connect rejected (' . $code . '): ' . $error );

			return array(

				'success' => false,

				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			);

		}

		if ( empty( $data['storeId'] ) || empty( $data['apiToken'] ) ) {
			self::log( 'Connect response missing storeId or apiToken (' . $code . '): ' . $raw );
			return array(
				'success' => false,
				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),
			);
		}

		return self::save_credentials( (string) $data['storeId'], (string) $data['apiToken'], $plugin_token );
	}



	/**

	 * Save credentials returned from connect or manual entry.

	 *

	 * @param string $store_id     VendorHub store ID.

	 * @param string $api_token    VendorHub API token.

	 * @param string $plugin_token Optional plugin callback token.

	 * @return array{success:bool,message:string,store_id?:string}

	 */

	public static function save_credentials( $store_id, $api_token, $plugin_token = '' ) {

		$store_id = sanitize_text_field( $store_id );

		$api_token = sanitize_text_field( $api_token );

		if ( empty( $store_id ) || empty( $api_token ) ) {

			self::log( 'Save credentials rejected: store ID and API token are required.' );

			return array(

				'success' => false,

				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			);

		}

		if ( empty( $plugin_token ) ) {

			$plugin_token = self::get_or_create_plugin_token();

		}

		update_option( 'vendorhub_store_id', $store_id, false );

		update_option( 'vendorhub_api_token', $api_token, false );

		update_option( 'vendorhub_plugin_token', sanitize_text_field( $plugin_token ), false );

		self::log( 'Connected store ' . $store_id );

		return array(

			'success'  => true,

			'message'  => __( 'Successfully connected to VendorHub.', 'vendorhub-for-woocommerce' ),

			'store_id' => $store_id,

		);
	}



	/**

	 * Build redirect URL for VendorHub web connect flow (no shared secret in plugin).

	 *

	 * @return string

	 */

	public static function get_redirect_url() {

		if ( self::uses_oauth_connect() ) {

			return self::get_oauth_authorize_url();

		}

		$plugin_token = self::get_or_create_plugin_token();

		$api_base = VendorHub_Settings::get_api_base();

		$state      = self::create_connect_state();
		$return_url = self::connect_return_url( $state );

		return add_query_arg(
			array(
				'siteUrl'     => self::get_site_url(),
				'pluginToken' => $plugin_token,
				'returnUrl'   => $return_url,
				'state'       => $state,
			),
			trailingslashit( $api_base ) . 'connect/woocommerce'
		);
	}



	/**
	 * Whether OAuth connect is enabled (Phase 2 — requires platform OAuth server).
	 *
	 * @return bool
	 */
	public static function uses_oauth_connect() {
		return (bool) apply_filters(
			'vendorhub_wc_use_oauth_connect',
			'' !== self::get_oauth_client_id()
		);
	}



	/**
	 * Public OAuth client ID (wp-config constant or filter; evaluated after plugins load).
	 *
	 * @return string
	 */
	public static function get_oauth_client_id() {
		if ( defined( 'VENDORHUB_WC_OAUTH_CLIENT_ID' ) && VENDORHUB_WC_OAUTH_CLIENT_ID ) {
			return (string) VENDORHUB_WC_OAUTH_CLIENT_ID;
		}

		return (string) apply_filters( 'vendorhub_wc_oauth_client_id', '' );
	}



	/**
	 * Build OAuth authorize URL (Phase 2).
	 *
	 * @return string
	 */
	public static function get_oauth_authorize_url() {
		$api_base      = VendorHub_Settings::get_api_base();
		$state         = self::create_connect_state();
		$code_verifier = self::create_pkce_verifier();
		$challenge     = self::pkce_challenge( $code_verifier );

		set_transient(
			self::connect_state_transient_key(),
			array(
				'state'         => $state,
				'code_verifier' => $code_verifier,
			),
			15 * MINUTE_IN_SECONDS
		);

		return add_query_arg(
			array(
				'client_id'             => self::get_oauth_client_id(),
				'redirect_uri'          => self::oauth_callback_url(),
				'response_type'         => 'code',
				'state'                 => $state,
				'site_url'              => self::get_site_url(),
				'plugin_token'          => self::get_or_create_plugin_token(),
				'scope'                 => 'orders:write callbacks:receive',
				'code_challenge'        => $challenge,
				'code_challenge_method' => 'S256',
			),
			trailingslashit( $api_base ) . 'oauth/authorize'
		);
	}



	/**
	 * Filterable OAuth callback URL for local dev / ngrok.
	 *
	 * @return string
	 */
	public static function oauth_callback_url() {
		return apply_filters(
			'vendorhub_wc_oauth_callback_url',
			admin_url( 'admin-post.php?action=vendorhub_oauth_callback' )
		);
	}



	/**
	 * Signed SSO launch URL for the connected store (opens VendorHub web dashboard).
	 *
	 * Prefer admin-post launch flow in UI; this helper is for filters and integrations.
	 *
	 * @return string|false Launch URL, or false when the store cannot launch.
	 */
	public static function get_dashboard_url() {
		if ( ! class_exists( 'VendorHub_Launch' ) || ! VendorHub_Launch::can_launch() ) {
			return false;
		}

		$url = VendorHub_Launch::build_launch_url();

		return $url ? $url : false;
	}



	/**
	 * Create and persist CSRF state for connect redirect.
	 *
	 * @return string
	 */
	public static function create_connect_state() {
		$state = wp_create_nonce( 'vendorhub_connect_state_' . get_current_user_id() );

		set_transient(
			self::connect_state_transient_key(),
			array( 'state' => $state ),
			15 * MINUTE_IN_SECONDS
		);

		return $state;
	}



	/**
	 * Validate connect state from redirect return.
	 *
	 * @param string $state State query param from VendorHub.
	 * @return bool
	 */
	public static function validate_connect_state( $state ) {
		$state = sanitize_text_field( $state );

		if ( empty( $state ) ) {
			return true;
		}

		$stored = get_transient( self::connect_state_transient_key() );

		if ( ! is_array( $stored ) || empty( $stored['state'] ) ) {
			return false;
		}

		if ( ! hash_equals( (string) $stored['state'], $state ) ) {
			return false;
		}

		delete_transient( self::connect_state_transient_key() );

		return true;
	}



	/**
	 * Read and clear stored connect state (OAuth token exchange).
	 *
	 * @param string $state State query param from VendorHub.
	 * @return array<string,string>|false
	 */
	public static function consume_connect_state( $state ) {
		$state = sanitize_text_field( $state );

		if ( empty( $state ) ) {
			return false;
		}

		$stored = get_transient( self::connect_state_transient_key() );

		if ( ! is_array( $stored ) || empty( $stored['state'] ) ) {
			return false;
		}

		if ( ! hash_equals( (string) $stored['state'], $state ) ) {
			return false;
		}

		delete_transient( self::connect_state_transient_key() );

		return $stored;
	}



	/**
	 * Exchange OAuth authorization code for store credentials (Phase 2).
	 *
	 * @param string $code  Authorization code from VendorHub.
	 * @param string $state CSRF state from redirect.
	 * @return array{success:bool,message:string,store_id?:string}
	 */
	public static function exchange_oauth_code( $code, $state ) {
		$stored = self::consume_connect_state( $state );

		if ( false === $stored ) {
			self::log( 'OAuth exchange rejected: invalid or expired state.' );
			return array(
				'success' => false,
				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),
			);
		}

		$body = array(
			'grant_type'    => 'authorization_code',
			'code'          => sanitize_text_field( $code ),
			'redirect_uri'  => self::oauth_callback_url(),
			'client_id'     => self::get_oauth_client_id(),
			'code_verifier' => isset( $stored['code_verifier'] ) ? $stored['code_verifier'] : '',
		);

		$api_base = VendorHub_Settings::get_api_base();
		$url      = trailingslashit( $api_base ) . 'oauth/token';

		$response = wp_safe_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			self::log( 'OAuth token exchange failed: ' . $response->get_error_message() );
			return array(
				'success' => false,
				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),
			);
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw       = wp_remote_retrieve_body( $response );
		$data      = json_decode( $raw, true );

		if ( ! in_array( (int) $http_code, array( 200, 201 ), true ) || ! is_array( $data ) ) {
			self::log( 'OAuth token exchange rejected (' . $http_code . '): ' . $raw );
			return array(
				'success' => false,
				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),
			);
		}

		$store_id  = isset( $data['storeId'] ) ? (string) $data['storeId'] : '';
		$api_token = isset( $data['apiToken'] ) ? (string) $data['apiToken'] : '';

		if ( empty( $store_id ) && ! empty( $data['store_id'] ) ) {
			$store_id = (string) $data['store_id'];
		}
		if ( empty( $api_token ) && ! empty( $data['access_token'] ) ) {
			$api_token = (string) $data['access_token'];
		}

		if ( empty( $store_id ) || empty( $api_token ) ) {
			self::log( 'OAuth token response missing storeId or apiToken.' );
			return array(
				'success' => false,
				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),
			);
		}

		return self::save_credentials( $store_id, $api_token );
	}



	/**
	 * Transient key for connect state tied to current user.
	 *
	 * @return string
	 */
	private static function connect_state_transient_key() {
		return 'vendorhub_connect_state_' . get_current_user_id();
	}



	/**
	 * Generate PKCE code verifier (Phase 2 OAuth).
	 *
	 * @return string
	 */
	private static function create_pkce_verifier() {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- RFC 7636 PKCE encoding.
		return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	}



	/**
	 * S256 PKCE challenge from verifier.
	 *
	 * @param string $verifier Code verifier.
	 * @return string
	 */
	private static function pkce_challenge( $verifier ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- RFC 7636 PKCE S256 challenge.
		return rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );
	}



	/**

	 * Handle OAuth-style return from VendorHub web connect.

	 */

	public static function maybe_handle_redirect_return() {

		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth return uses signed server-side redirect.

		if ( ! isset( $_GET['page'], $_GET['vendorhub_store_id'], $_GET['vendorhub_api_token'] ) ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'wc-settings' !== $_GET['page'] ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$store_id = sanitize_text_field( wp_unslash( $_GET['vendorhub_store_id'] ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$api_token = sanitize_text_field( wp_unslash( $_GET['vendorhub_api_token'] ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$plugin_token = isset( $_GET['vendorhub_plugin_token'] )
			? sanitize_text_field( wp_unslash( $_GET['vendorhub_plugin_token'] ) )
			: '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		if ( ! self::validate_connect_state( $state ) ) {

			self::log( 'Connect return rejected: invalid or expired state.' );

			wp_safe_redirect( self::settings_url( 'connect_error' ) );

			exit;

		}

		self::save_credentials( $store_id, $api_token, $plugin_token );

		wp_safe_redirect( self::settings_url( 'connected' ) );

		exit;
	}



	/**

	 * Disconnect from VendorHub (clears local credentials).

	 *

	 * @return array{success:bool,message:string}

	 */

	public static function disconnect() {

		delete_option( 'vendorhub_store_id' );

		delete_option( 'vendorhub_api_token' );

		delete_option( 'vendorhub_plugin_token' );

		self::log( 'Disconnected from VendorHub' );

		return array(

			'success' => true,

			'message' => __( 'Disconnected from VendorHub.', 'vendorhub-for-woocommerce' ),

		);
	}



	/**

	 * Test stored credentials against order ingest endpoint.

	 *

	 * @return array{success:bool,message:string}

	 */

	public static function test_connection() {

		$store_id = get_option( 'vendorhub_store_id', '' );

		$api_token = get_option( 'vendorhub_api_token', '' );

		VendorHub_Vendor_Meta::maybe_pull_integration_settings( true );

		if ( empty( $store_id ) || empty( $api_token ) ) {

			self::log( 'Connection test skipped: store is not connected.' );

			return array(

				'success' => false,

				'message' => __( 'Connection test failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			);

		}

		$api_base = VendorHub_Settings::get_api_base();

		$url = trailingslashit( $api_base ) . 'api/stores/' . rawurlencode( $store_id ) . '/orders';

		$response = wp_safe_remote_post(
			$url,
			array(

				'timeout' => 20,

				'headers' => array(

					'Authorization' => 'Bearer ' . $api_token,

					'Content-Type'  => 'application/json',

				),

				'body'    => '{}',

			)
		);

		if ( is_wp_error( $response ) ) {

			self::log( 'Connection test failed: ' . $response->get_error_message() );

			return array(

				'success' => false,

				'message' => __( 'Connection test failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			);

		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $code ) {

			self::log( 'Connection test authentication failed (401).' );

			return array(

				'success' => false,

				'message' => __( 'Connection test failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			);

		}

		if ( 400 === $code || 200 === $code ) {

			return array(

				'success' => true,

				'message' => __( 'Connection test successful.', 'vendorhub-for-woocommerce' ),

			);

		}

		$raw = wp_remote_retrieve_body( $response );

		self::log( 'Connection test unexpected response (' . $code . '): ' . $raw );

		return array(

			'success' => false,

			'message' => __( 'Connection test failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

		);
	}



	/**

	 * Whether the store has active credentials.

	 *

	 * @return bool

	 */

	public static function is_connected() {

		$store_id = get_option( 'vendorhub_store_id', '' );

		$api_token = get_option( 'vendorhub_api_token', '' );

		return ! empty( $store_id ) && ! empty( $api_token );
	}



	/**

	 * Whether direct connect (shared secret) is available.

	 *

	 * @return bool

	 */

	public static function supports_direct_connect() {

		return ! empty( self::get_connect_secret() );
	}



	/**

	 * Get connect secret from wp-config constant only (never shipped in plugin).

	 *

	 * @return string

	 */

	public static function get_connect_secret() {

		if ( defined( 'VENDORHUB_WC_CONNECT_SECRET' ) && VENDORHUB_WC_CONNECT_SECRET ) {

			return (string) VENDORHUB_WC_CONNECT_SECRET;

		}

		return '';
	}



	/**

	 * Get or create a persistent plugin token.

	 *

	 * @return string

	 */

	public static function get_or_create_plugin_token() {

		$existing = get_option( 'vendorhub_plugin_token', '' );

		if ( ! empty( $existing ) ) {

			return $existing;

		}

		$token = bin2hex( random_bytes( 32 ) );

		update_option( 'vendorhub_plugin_token', $token, false );

		return $token;
	}



	/**

	 * Public site URL for this store.

	 *

	 * @return string

	 */

	public static function get_site_url() {

		return untrailingslashit( home_url( '/' ) );
	}



	/**
	 * Return URL sent to VendorHub for the legacy redirect connect flow.
	 *
	 * Always includes tab=vendorhub; state is appended when CSRF protection is used.
	 *
	 * @param string $state Optional connect state nonce.
	 * @return string
	 */
	public static function connect_return_url( $state = '' ) {
		$url = self::settings_url();

		if ( $state ) {
			$url = add_query_arg( 'state', $state, $url );
		}

		return $url;
	}



	/**

	 * Settings page URL with optional status query args.

	 *

	 * @param string $status Status flag.

	 * @return string

	 */

	public static function settings_url( $status = '' ) {

		$url = admin_url( 'admin.php?page=wc-settings&tab=vendorhub' );

		if ( $status ) {

			$url = add_query_arg( 'vendorhub_status', $status, $url );

		}

		return $url;
	}



	/**

	 * Log to WooCommerce logger.

	 *

	 * @param string $message Log message.

	 */

	public static function log( $message ) {

		if ( function_exists( 'wc_get_logger' ) ) {

			wc_get_logger()->info( $message, array( 'source' => 'vendorhub' ) );

		}
	}
}
