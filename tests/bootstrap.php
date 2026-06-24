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

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Minimal add_query_arg for launch URL tests.
	 *
	 * @param array<string,string> $args Query args.
	 * @param string               $url  Base URL.
	 * @return string
	 */
	function add_query_arg( $args, $url ) {
		$parsed = parse_url( $url );
		$query  = array();

		if ( ! empty( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $query );
		}

		$query = array_merge( $query, $args );

		$scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : '';
		$host   = isset( $parsed['host'] ) ? $parsed['host'] : '';
		$port   = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';
		$path   = isset( $parsed['path'] ) ? $parsed['path'] : '';

		return $scheme . $host . $port . $path . '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
	}
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

require_once __DIR__ . '/helpers/class-vendorhub-test-connect.php';

require_once dirname( __DIR__ ) . '/includes/class-vendorhub-hmac.php';
require_once dirname( __DIR__ ) . '/includes/class-vendorhub-launch.php';
