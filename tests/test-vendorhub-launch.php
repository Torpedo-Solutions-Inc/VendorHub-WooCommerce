<?php
/**
 * Tests for VendorHub_Launch SSO URL builder.
 *
 * @package VendorHub_WooCommerce
 */

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * VendorHub launch URL tests.
 */
class VendorHub_Launch_Test extends PHPUnit\Framework\TestCase {

	/**
	 * Set up Brain Monkey.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		VendorHub_Test_Connect::$connected = false;
	}

	/**
	 * Tear down Brain Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Launch URL includes required query parameters.
	 */
	public function test_launch_url_contains_required_query_params() {
		$url = VendorHub_Launch::build_signed_launch_url(
			'https://www.myvendorhub.com',
			'wc-example.com',
			'test-plugin-token',
			42,
			'1710000000000'
		);

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'], $query );

		$this->assertSame( 'wc-example.com', $query['store'] );
		$this->assertSame( '1710000000000', $query['ts'] );
		$this->assertSame( '42', $query['user'] );
		$this->assertNotEmpty( $query['sig'] );
		$this->assertStringStartsWith( 'https://www.myvendorhub.com/launch?', $url );
	}

	/**
	 * Signature matches expected HMAC for fixed inputs.
	 */
	public function test_signature_matches_expected_hmac() {
		$store_id     = 'wc-example.com';
		$plugin_token = 'test-plugin-token-secret';
		$wp_user_id   = 42;
		$timestamp    = '1710000000000';
		$body         = '{"storeId":"wc-example.com","wpUserId":"42"}';
		$expected_sig = hash_hmac( 'sha256', $timestamp . '.' . $body, $plugin_token );

		$url = VendorHub_Launch::build_signed_launch_url(
			'https://www.myvendorhub.com',
			$store_id,
			$plugin_token,
			$wp_user_id,
			$timestamp
		);

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'], $query );

		$this->assertSame( $expected_sig, $query['sig'] );
	}

	/**
	 * Body JSON omits wpUserId when user param is omitted.
	 */
	public function test_body_omits_wp_user_id_when_user_param_omitted() {
		$store_id     = 'wc-example.com';
		$plugin_token = 'test-plugin-token-secret';
		$timestamp    = '1710000000000';
		$body         = '{"storeId":"wc-example.com"}';
		$expected_sig = hash_hmac( 'sha256', $timestamp . '.' . $body, $plugin_token );

		$url = VendorHub_Launch::build_signed_launch_url(
			'https://www.myvendorhub.com',
			$store_id,
			$plugin_token,
			null,
			$timestamp
		);

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'], $query );

		$this->assertArrayNotHasKey( 'user', $query );
		$this->assertSame( $expected_sig, $query['sig'] );
	}

	/**
	 * Open action unavailable when the store is not connected.
	 */
	public function test_can_user_launch_false_when_not_connected() {
		VendorHub_Test_Connect::$connected = false;

		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_plugin_token' === $key ) {
					return 'token';
				}
				return '';
			}
		);
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->assertFalse( VendorHub_Launch::can_user_launch() );
	}

	/**
	 * Launch is unavailable when the store is not connected.
	 */
	public function test_can_launch_false_when_not_connected() {
		VendorHub_Test_Connect::$connected = false;

		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_store_id' === $key ) {
					return 'wc-example.com';
				}
				if ( 'vendorhub_plugin_token' === $key ) {
					return 'token';
				}
				return '';
			}
		);

		$this->assertFalse( VendorHub_Launch::can_launch() );
	}

	/**
	 * Launch is unavailable without a plugin token even when store ID exists.
	 */
	public function test_can_launch_false_without_plugin_token() {
		VendorHub_Test_Connect::$connected = true;

		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_store_id' === $key ) {
					return 'wc-example.com';
				}
				if ( 'vendorhub_plugin_token' === $key ) {
					return '';
				}
				return '';
			}
		);

		$this->assertFalse( VendorHub_Launch::can_launch() );
	}

	/**
	 * Unauthorized users cannot launch VendorHub.
	 */
	public function test_can_user_launch_false_without_capability() {
		VendorHub_Test_Connect::$connected = true;

		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_store_id' === $key ) {
					return 'wc-example.com';
				}
				if ( 'vendorhub_plugin_token' === $key ) {
					return 'token';
				}
				return '';
			}
		);
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->assertFalse( VendorHub_Launch::can_user_launch() );
	}

	/**
	 * Authorized connected users can launch VendorHub.
	 */
	public function test_can_user_launch_true_when_connected_and_authorized() {
		VendorHub_Test_Connect::$connected = true;

		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_store_id' === $key ) {
					return 'wc-example.com';
				}
				if ( 'vendorhub_plugin_token' === $key ) {
					return 'token';
				}
				return '';
			}
		);
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->assertTrue( VendorHub_Launch::can_launch() );
		$this->assertTrue( VendorHub_Launch::can_user_launch() );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * WordPress-compatible parse_url wrapper for tests.
	 *
	 * @param string $url URL to parse.
	 * @return array<string,mixed>|false
	 */
	function wp_parse_url( $url ) {
		return parse_url( $url );
	}
}
