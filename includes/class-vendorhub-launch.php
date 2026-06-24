<?php
/**
 * SSO launch URL builder for returning to VendorHub without re-login.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds signed /launch URLs for wp-admin "Open VendorHub" actions.
 */
class VendorHub_Launch {

	/**
	 * Whether the store can build a launch URL (connected + plugin token).
	 *
	 * @return bool
	 */
	public static function can_launch() {
		if ( ! VendorHub_Connect::is_connected() ) {
			return false;
		}

		$plugin_token = get_option( 'vendorhub_plugin_token', '' );

		return ! empty( $plugin_token );
	}

	/**
	 * Whether the current user may launch VendorHub.
	 *
	 * @return bool
	 */
	public static function can_user_launch() {
		return current_user_can( 'manage_woocommerce' ) && self::can_launch();
	}

	/**
	 * Build a signed launch URL using stored credentials and current user.
	 *
	 * @param int|null    $wp_user_id   WordPress user ID; null uses current user (omit when 0).
	 * @param string|null $timestamp_ms Unix ms timestamp string; null generates fresh value.
	 * @return string|false Launch URL or false when credentials are missing.
	 */
	public static function build_launch_url( $wp_user_id = null, $timestamp_ms = null ) {
		if ( ! self::can_launch() ) {
			return false;
		}

		$store_id     = get_option( 'vendorhub_store_id', '' );
		$plugin_token = get_option( 'vendorhub_plugin_token', '' );

		if ( null === $wp_user_id ) {
			$wp_user_id = get_current_user_id();
		}

		$api_base = VendorHub_Settings::get_api_base();

		return self::build_signed_launch_url(
			$api_base,
			$store_id,
			$plugin_token,
			$wp_user_id ? (int) $wp_user_id : null,
			$timestamp_ms
		);
	}

	/**
	 * Build a signed launch URL from explicit values (testable without WordPress options).
	 *
	 * @param string      $api_base     VendorHub base URL.
	 * @param string      $store_id     VendorHub store ID.
	 * @param string      $plugin_token Per-site callback token (HMAC secret).
	 * @param int|null    $wp_user_id   WordPress user ID; null omits user param and wpUserId from body.
	 * @param string|null $timestamp_ms Unix ms timestamp string; null generates fresh value.
	 * @return string Signed launch URL.
	 */
	public static function build_signed_launch_url( $api_base, $store_id, $plugin_token, $wp_user_id = null, $timestamp_ms = null ) {
		if ( null === $timestamp_ms ) {
			$timestamp_ms = (string) (int) round( microtime( true ) * 1000 );
		} else {
			$timestamp_ms = (string) $timestamp_ms;
		}

		$body_data = array(
			'storeId' => $store_id,
		);

		$include_user = null !== $wp_user_id && (int) $wp_user_id > 0;

		if ( $include_user ) {
			$body_data['wpUserId'] = (string) $wp_user_id;
		}

		$body = wp_json_encode( $body_data );

		if ( false === $body ) {
			return '';
		}

		$sig = VendorHub_HMAC::sign( $plugin_token, $timestamp_ms, $body );

		$query_args = array(
			'store' => $store_id,
			'ts'    => $timestamp_ms,
			'sig'   => $sig,
		);

		if ( $include_user ) {
			$query_args['user'] = (string) $wp_user_id;
		}

		$path = apply_filters( 'vendorhub_wc_launch_path', 'launch' );
		$url  = add_query_arg( $query_args, trailingslashit( $api_base ) . ltrim( $path, '/' ) );

		/**
		 * Filter the final SSO launch URL.
		 *
		 * @param string               $url        Full launch URL.
		 * @param array<string,string> $query_args Query parameters used for signing.
		 * @param string               $body       Compact JSON body used for signing.
		 */
		return apply_filters( 'vendorhub_wc_launch_url', $url, $query_args, $body );
	}
}
