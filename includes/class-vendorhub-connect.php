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
					'vendorhub-woocommerce'
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
				'message' => __( 'Failed to encode connect payload.', 'vendorhub-woocommerce' ),
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

				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-woocommerce' ),

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

				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-woocommerce' ),

			);

		}

		if ( empty( $data['storeId'] ) || empty( $data['apiToken'] ) ) {
			self::log( 'Connect response missing storeId or apiToken (' . $code . '): ' . $raw );
			return array(
				'success' => false,
				'message' => __( 'Connect request failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-woocommerce' ),
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

			return array(

				'success' => false,

				'message' => __( 'Store ID and API token are required.', 'vendorhub-woocommerce' ),

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

			'message'  => __( 'Successfully connected to VendorHub.', 'vendorhub-woocommerce' ),

			'store_id' => $store_id,

		);
	}



	/**

	 * Build redirect URL for VendorHub web connect flow (no shared secret in plugin).

	 *

	 * @return string

	 */

	public static function get_redirect_url() {

		$plugin_token = self::get_or_create_plugin_token();

		$api_base = VendorHub_Settings::get_api_base();

		$return_url = self::settings_url();

		return add_query_arg(
			array(
				'siteUrl'     => self::get_site_url(),
				'pluginToken' => $plugin_token,
				'returnUrl'   => $return_url,
			),
			trailingslashit( $api_base ) . 'connect/woocommerce'
		);
	}



	/**

	 * Handle OAuth-style return from VendorHub web connect.

	 */

	public static function maybe_handle_redirect_return() {

		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth return uses signed server-side redirect.

		if ( ! isset( $_GET['page'], $_GET['tab'], $_GET['vendorhub_store_id'], $_GET['vendorhub_api_token'] ) ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'wc-settings' !== $_GET['page'] || 'vendorhub' !== $_GET['tab'] ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$store_id = sanitize_text_field( wp_unslash( $_GET['vendorhub_store_id'] ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$api_token = sanitize_text_field( wp_unslash( $_GET['vendorhub_api_token'] ) );

		self::save_credentials( $store_id, $api_token );

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

			'message' => __( 'Disconnected from VendorHub.', 'vendorhub-woocommerce' ),

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

		if ( empty( $store_id ) || empty( $api_token ) ) {

			return array(

				'success' => false,

				'message' => __( 'Store is not connected.', 'vendorhub-woocommerce' ),

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

			return array(

				'success' => false,

				'message' => $response->get_error_message(),

			);

		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $code ) {

			return array(

				'success' => false,

				'message' => __( 'Authentication failed. Reconnect to VendorHub.', 'vendorhub-woocommerce' ),

			);

		}

		if ( 400 === $code || 200 === $code ) {

			return array(

				'success' => true,

				'message' => __( 'Connection test successful.', 'vendorhub-woocommerce' ),

			);

		}

		return array(

			'success' => false,

			'message' => sprintf(
				/* translators: %d: HTTP status code */

				__( 'Unexpected response from VendorHub (HTTP %d).', 'vendorhub-woocommerce' ),
				$code
			),

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

	 * Settings page URL with optional status query args.

	 *

	 * @param string $status  Status flag.

	 * @param string $message Optional message.

	 * @return string

	 */

	public static function settings_url( $status = '', $message = '' ) {

		$url = admin_url( 'admin.php?page=wc-settings&tab=vendorhub' );

		if ( $status ) {

			$url = add_query_arg( 'vendorhub_status', $status, $url );

		}

		if ( $message ) {
			$url = add_query_arg( 'vendorhub_message', $message, $url );
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
