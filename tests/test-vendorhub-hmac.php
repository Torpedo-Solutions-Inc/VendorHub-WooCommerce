<?php
/**
 * Tests for VendorHub_HMAC signing and verification.
 *
 * @package VendorHub_WooCommerce
 */

/**
 * VendorHub HMAC tests.
 */
class VendorHub_HMAC_Test extends PHPUnit\Framework\TestCase {

	/**
	 * Valid signature verifies within skew window.
	 */
	public function test_verify_accepts_valid_signature() {
		$secret    = 'test-plugin-token';
		$timestamp = '1710000000000';
		$body      = '{"storeId":"wc-example.com","wpUserId":"42"}';
		$sig       = VendorHub_HMAC::sign( $secret, $timestamp, $body );

		$this->assertTrue(
			VendorHub_HMAC::verify( $secret, $timestamp, $body, $sig, (int) $timestamp )
		);
	}

	/**
	 * Tampered signature is rejected.
	 */
	public function test_verify_rejects_tampered_signature() {
		$secret    = 'test-plugin-token';
		$timestamp = '1710000000000';
		$body      = '{"storeId":"wc-example.com","wpUserId":"42"}';
		$sig       = VendorHub_HMAC::sign( $secret, $timestamp, $body );

		$tampered = substr( $sig, 0, -1 ) . ( substr( $sig, -1 ) === 'a' ? 'b' : 'a' );

		$this->assertFalse(
			VendorHub_HMAC::verify( $secret, $timestamp, $body, $tampered, (int) $timestamp )
		);
	}

	/**
	 * Stale timestamp outside skew window is rejected.
	 */
	public function test_verify_rejects_stale_timestamp() {
		$secret    = 'test-plugin-token';
		$timestamp = '1710000000000';
		$body      = '{"storeId":"wc-example.com"}';
		$sig       = VendorHub_HMAC::sign( $secret, $timestamp, $body );
		$stale_now = (int) $timestamp + VendorHub_HMAC::MAX_SKEW_MS + 1;

		$this->assertFalse(
			VendorHub_HMAC::verify( $secret, $timestamp, $body, $sig, $stale_now )
		);
	}
}
