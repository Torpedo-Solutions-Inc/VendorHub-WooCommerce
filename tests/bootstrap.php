<?php
/**
 * PHPUnit bootstrap for VendorHub plugin unit tests.
 *
 * @package VendorHub_WooCommerce
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'VENDORHUB_WC_DEFAULT_API_BASE' ) ) {
	define( 'VENDORHUB_WC_DEFAULT_API_BASE', 'https://www.myvendorhub.com' );
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mirror WordPress wp_json_encode for signing tests.
	 *
	 * @param mixed $data Data to encode.
	 * @return string|false
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Passthrough i18n stub.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	require_once __DIR__ . '/bootstrap-add-query-arg.php';
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * Append trailing slash.
	 *
	 * @param string $string Input string.
	 * @return string
	 */
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Passthrough filter stub.
	 *
	 * @param string $tag   Filter name.
	 * @param mixed  $value Value.
	 * @param mixed  ...$args Additional filter arguments (ignored).
	 * @return mixed
	 */
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-vendorhub-hmac.php';
require_once dirname( __DIR__ ) . '/includes/class-vendorhub-connect.php';
require_once dirname( __DIR__ ) . '/includes/class-vendorhub-launch.php';
