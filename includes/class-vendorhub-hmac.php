<?php
/**
 * HMAC signing and verification (mirrors VendorHub app/utils/vendorhub-hmac.server.ts).
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * VendorHub HMAC helper.
 */
class VendorHub_HMAC {

	const MAX_SKEW_MS = 300000; // 5 minutes.

	/**
	 * Sign a payload: HMAC-SHA256 hex of "{timestamp}.{body}".
	 *
	 * @param string $secret    Shared secret or api token.
	 * @param string $timestamp Unix ms timestamp string.
	 * @param string $body      Raw request body.
	 * @return string Hex digest.
	 */
	public static function sign( $secret, $timestamp, $body ) {
		return hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );
	}

	/**
	 * Verify signature and timestamp skew.
	 *
	 * @param string $secret    Shared secret or api token.
	 * @param string $timestamp Unix ms timestamp string.
	 * @param string $body      Raw request body.
	 * @param string $signature Expected hex signature.
	 * @param int    $now       Optional current time in ms.
	 * @return bool
	 */
	public static function verify( $secret, $timestamp, $body, $signature, $now = null ) {
		if ( null === $now ) {
			$now = (int) round( microtime( true ) * 1000 );
		}

		$ts = (int) $timestamp;
		if ( $ts <= 0 ) {
			return false;
		}

		if ( abs( $now - $ts ) > self::MAX_SKEW_MS ) {
			return false;
		}

		$expected = self::sign( $secret, $timestamp, $body );
		if ( strlen( $expected ) !== strlen( $signature ) ) {
			return false;
		}

		return hash_equals( $expected, strtolower( $signature ) );
	}
}
