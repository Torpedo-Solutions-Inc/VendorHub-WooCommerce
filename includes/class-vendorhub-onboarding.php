<?php
/**
 * Post-activation onboarding redirect helpers.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * One-time setup redirect after plugin activation.
 */
class VendorHub_Onboarding {

	const OPTION_SHOW = 'vendorhub_wc_show_onboarding';

	/**
	 * Bootstrap onboarding hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_after_activation' ), 5 );
	}

	/**
	 * Set onboarding flag on plugin activation (skip when already connected).
	 */
	public static function activate() {
		if ( VendorHub_Connect::is_connected() ) {
			return;
		}

		update_option( self::OPTION_SHOW, '1', false );
	}

	/**
	 * Redirect store admin once to VendorHub settings after activation.
	 */
	public static function maybe_redirect_after_activation() {
		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( VendorHub_Connect::is_connected() ) {
			delete_option( self::OPTION_SHOW );
			return;
		}

		if ( ! get_option( self::OPTION_SHOW ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'vendorhub' === $_GET['tab'] ) {
			delete_option( self::OPTION_SHOW );
			return;
		}

		delete_option( self::OPTION_SHOW );
		wp_safe_redirect( VendorHub_Connect::settings_url( 'welcome' ) );
		exit;
	}

	/**
	 * Scannable disclosure items shown before external connect redirect.
	 *
	 * @return array<int, array{title:string,description:string,has_privacy_link?:bool}>
	 */
	public static function get_disclosure_checklist() {
		return VendorHub_Privacy::get_disclosure_checklist();
	}
}
