<?php
/**
 * Tests for VendorHub_Connect redirect connect helpers.
 *
 * @package VendorHub_WooCommerce
 */

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * VendorHub connect redirect tests.
 */
class VendorHub_Connect_Test extends PHPUnit\Framework\TestCase {

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
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
	}

	/**
	 * Tear down Brain Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Settings URL always targets the VendorHub WooCommerce settings tab.
	 */
	public function test_settings_url_includes_vendorhub_tab() {
		$url = VendorHub_Connect::settings_url();

		$this->assertStringContainsString( 'page=wc-settings', $url );
		$this->assertStringContainsString( 'tab=vendorhub', $url );
	}

	/**
	 * Connect return URL includes tab and optional state for VendorHub redirect.
	 */
	public function test_connect_return_url_includes_tab_and_state() {
		$url = VendorHub_Connect::connect_return_url( 'test-state-nonce' );

		$this->assertStringContainsString( 'tab=vendorhub', $url );
		$this->assertStringContainsString( 'state=test-state-nonce', $url );
	}

	/**
	 * Empty connect state is rejected (prevents CSRF credential injection).
	 */
	public function test_validate_connect_state_rejects_empty_state() {
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );

		$this->assertFalse( VendorHub_Connect::validate_connect_state( '' ) );
	}

	/**
	 * Redirect return handler accepts credentials without tab=vendorhub in the URL.
	 */
	public function test_maybe_handle_redirect_return_without_vendorhub_tab() {
		$_GET = array(
			'page'                => 'wc-settings',
			'vendorhub_store_id'  => 'wc-example.com',
			'vendorhub_api_token' => str_repeat( 'a', 64 ),
			'state'               => 'valid-state-nonce',
		);

		Functions\when( 'is_admin' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'delete_option' )->justReturn( true );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_connect_state_1' === $key ) {
					return array(
						'state'      => 'valid-state-nonce',
						'expires_at' => time() + 900,
					);
				}
				if ( 'vendorhub_plugin_token' === $key ) {
					return 'existing-plugin-token';
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
			VendorHub_Connect::maybe_handle_redirect_return();
			$this->fail( 'Expected redirect after saving credentials.' );
		} catch ( RedirectException $e ) {
			$this->assertStringContainsString( 'tab=vendorhub', $e->url );
			$this->assertStringContainsString( 'vendorhub_status=connected', $e->url );
			$this->assertStringNotContainsString( 'vendorhub_api_token', $e->url );
		}

		$_GET = array();
	}

	/**
	 * Redirect return with credentials but missing state redirects to connect_error.
	 */
	public function test_maybe_handle_redirect_return_rejects_missing_state() {
		$_GET = array(
			'page'                => 'wc-settings',
			'vendorhub_store_id'  => 'wc-example.com',
			'vendorhub_api_token' => str_repeat( 'a', 64 ),
		);

		Functions\when( 'is_admin' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				throw new RedirectException( $url );
			}
		);

		try {
			VendorHub_Connect::maybe_handle_redirect_return();
			$this->fail( 'Expected redirect to connect_error when state is missing.' );
		} catch ( RedirectException $e ) {
			$this->assertStringContainsString( 'vendorhub_status=connect_error', $e->url );
		}

		$_GET = array();
	}

	/**
	 * Redirect return handler rejects invalid connect state.
	 */
	public function test_maybe_handle_redirect_return_rejects_invalid_state() {
		$_GET = array(
			'page'                => 'wc-settings',
			'vendorhub_store_id'  => 'wc-example.com',
			'vendorhub_api_token' => str_repeat( 'a', 64 ),
			'state'               => 'wrong-state',
		);

		Functions\when( 'is_admin' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'delete_option' )->justReturn( true );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'vendorhub_connect_state_1' === $key ) {
					return array(
						'state'      => 'expected-state',
						'expires_at' => time() + 900,
					);
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
			VendorHub_Connect::maybe_handle_redirect_return();
			$this->fail( 'Expected redirect to connect_error for invalid state.' );
		} catch ( RedirectException $e ) {
			$this->assertStringContainsString( 'vendorhub_status=connect_error', $e->url );
		}

		$_GET = array();
	}
}

/**
 * Captures wp_safe_redirect target in tests.
 */
class RedirectException extends Exception {

	/** @var string */
	public $url;

	/**
	 * @param string $url Redirect URL.
	 */
	public function __construct( $url ) {
		$this->url = $url;
		parent::__construct( 'Redirect to ' . $url );
	}
}
