<?php
/**
 * Tests for VendorHub_Settings::handle_launch admin-post handler.
 *
 * @package VendorHub_WooCommerce
 */

use Brain\Monkey;
use Brain\Monkey\Functions;

require_once __DIR__ . '/test-vendorhub-connect.php';

if ( ! class_exists( 'VendorHub_Settings' ) ) {
	require_once dirname( __DIR__ ) . '/includes/class-vendorhub-settings.php';
}

/**
 * VendorHub launch handler tests.
 */
class VendorHub_Settings_Launch_Test extends PHPUnit\Framework\TestCase {

	/**
	 * Set up Brain Monkey.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'admin_url' )->alias(
			function ( $path ) {
				return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
			}
		);
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'check_admin_referer' )->justReturn( true );
	}

	/**
	 * Tear down Brain Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * handle_launch redirects to signed /launch, never /auth/login.
	 */
	public function test_handle_launch_redirects_to_signed_launch_url() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_store_id' === $key ) {
					return 'wc-localhost';
				}
				if ( 'vendorhub_plugin_token' === $key ) {
					return 'test-plugin-token-secret';
				}
				if ( 'vendorhub_api_base' === $key ) {
					return 'https://www.myvendorhub.com';
				}
				return '';
			}
		);
		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				throw new RedirectException( $url );
			}
		);

		try {
			VendorHub_Settings::handle_launch();
			$this->fail( 'Expected redirect to signed launch URL.' );
		} catch ( RedirectException $e ) {
			$this->assertStringStartsWith( 'https://www.myvendorhub.com/launch?', $e->url );
			$this->assertStringNotContainsString( '/auth/login', $e->url );
			$this->assertStringNotContainsString( '/app', strtok( $e->url, '?' ) );

			$parsed = wp_parse_url( $e->url );
			parse_str( $parsed['query'], $query );

			$this->assertSame( 'wc-localhost', $query['store'] );
			$this->assertArrayNotHasKey( 'user', $query );
			$this->assertNotEmpty( $query['ts'] );
			$this->assertNotEmpty( $query['sig'] );

			$body         = '{"storeId":"wc-localhost"}';
			$expected_sig = hash_hmac( 'sha256', $query['ts'] . '.' . $body, 'test-plugin-token-secret' );
			$this->assertSame( $expected_sig, $query['sig'] );
		}
	}

	/**
	 * Missing credentials redirect to settings with launch_error.
	 */
	public function test_handle_launch_missing_credentials_redirects_to_settings() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				throw new RedirectException( $url );
			}
		);

		try {
			VendorHub_Settings::handle_launch();
			$this->fail( 'Expected redirect when launch credentials are missing.' );
		} catch ( RedirectException $e ) {
			$this->assertStringContainsString( 'tab=vendorhub', $e->url );
			$this->assertStringContainsString( 'vendorhub_status=launch_error', $e->url );
		}
	}

	/**
	 * normalize_api_base rejects pasted wp-admin / admin-post URLs.
	 */
	public function test_normalize_api_base_rejects_admin_post_url() {
		$this->assertSame(
			'',
			VendorHub_Settings::normalize_api_base(
				'http://localhost:8881/wp-admin/admin-post.php?action=vendorhub_launch&_wpnonce=abc'
			)
		);
		$this->assertSame(
			'https://www.myvendorhub.com',
			VendorHub_Settings::normalize_api_base( 'https://www.myvendorhub.com/connect/woocommerce' )
		);
	}
}
