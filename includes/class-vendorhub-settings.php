<?php

/**

 * WooCommerce settings tab and admin handlers.

 *

 * @package VendorHub_WooCommerce

 */



defined( 'ABSPATH' ) || exit;



/**

 * Admin settings integration under WooCommerce.

 */

class VendorHub_Settings {



	/**

	 * Bootstrap settings hooks.

	 */

	public static function init() {

		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );

		add_action( 'woocommerce_settings_tabs_vendorhub', array( __CLASS__, 'render_settings_page' ) );

		add_action( 'woocommerce_update_options_vendorhub', array( __CLASS__, 'save_settings' ) );

		add_action( 'admin_post_vendorhub_connect', array( __CLASS__, 'handle_connect' ) );

		add_action( 'admin_post_vendorhub_disconnect', array( __CLASS__, 'handle_disconnect' ) );

		add_action( 'admin_post_vendorhub_test_connection', array( __CLASS__, 'handle_test_connection' ) );

		add_action( 'admin_post_vendorhub_redirect_connect', array( __CLASS__, 'handle_redirect_connect' ) );

		add_action( 'admin_post_vendorhub_save_credentials', array( __CLASS__, 'handle_save_credentials' ) );

		add_action( 'admin_init', array( 'VendorHub_Connect', 'maybe_handle_redirect_return' ) );

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		VendorHub_Privacy::init();
	}



	/**

	 * Add VendorHub tab to WooCommerce settings.

	 *

	 * @param array $tabs Existing tabs.

	 * @return array

	 */

	public static function add_settings_tab( $tabs ) {

		$tabs['vendorhub'] = __( 'VendorHub', 'vendorhub-woocommerce' );

		return $tabs;
	}



	/**

	 * Render settings page template.

	 */

	public static function render_settings_page() {

		require VENDORHUB_WC_PLUGIN_DIR . 'admin/settings-page.php';
	}



	/**

	 * Save API base URL.

	 */

	public static function save_settings() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			return;

		}

		check_admin_referer( 'vendorhub_save_settings', 'vendorhub_settings_nonce' );

		$api_base = isset( $_POST['vendorhub_api_base'] ) ? esc_url_raw( wp_unslash( $_POST['vendorhub_api_base'] ) ) : '';

		if ( $api_base ) {

			update_option( 'vendorhub_api_base', untrailingslashit( $api_base ), false );

		}

		wp_safe_redirect( VendorHub_Connect::settings_url( 'saved' ) );

		exit;
	}



	/**

	 * Direct connect handler (self-hosted dev only).

	 */

	public static function handle_connect() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-woocommerce' ) );

		}

		check_admin_referer( 'vendorhub_connect' );

		$result = VendorHub_Connect::connect();

		wp_safe_redirect( VendorHub_Connect::settings_url( $result['success'] ? 'connected' : 'connect_error' ) );

		exit;
	}



	/**

	 * Redirect connect handler (recommended for WordPress.org / SaaS).

	 */

	public static function handle_redirect_connect() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-woocommerce' ) );

		}

		check_admin_referer( 'vendorhub_redirect_connect' );

		wp_safe_redirect( VendorHub_Connect::get_redirect_url() );

		exit;
	}



	/**

	 * Manual credentials paste handler.

	 */

	public static function handle_save_credentials() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-woocommerce' ) );

		}

		check_admin_referer( 'vendorhub_save_credentials' );

		$store_id = isset( $_POST['vendorhub_manual_store_id'] ) ? sanitize_text_field( wp_unslash( $_POST['vendorhub_manual_store_id'] ) ) : '';

		$api_token = isset( $_POST['vendorhub_manual_api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['vendorhub_manual_api_token'] ) ) : '';

		$result = VendorHub_Connect::save_credentials( $store_id, $api_token );

		wp_safe_redirect( VendorHub_Connect::settings_url( $result['success'] ? 'connected' : 'connect_error' ) );

		exit;
	}



	/**

	 * Disconnect handler.

	 */

	public static function handle_disconnect() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-woocommerce' ) );

		}

		check_admin_referer( 'vendorhub_disconnect' );

		VendorHub_Connect::disconnect();

		wp_safe_redirect( VendorHub_Connect::settings_url( 'disconnected' ) );

		exit;
	}



	/**

	 * Test connection handler.

	 */

	public static function handle_test_connection() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-woocommerce' ) );

		}

		check_admin_referer( 'vendorhub_test_connection' );

		$result = VendorHub_Connect::test_connection();

		wp_safe_redirect( VendorHub_Connect::settings_url( $result['success'] ? 'test_ok' : 'test_error' ) );

		exit;
	}



	/**

	 * Show admin notices from query args.

	 */

	public static function admin_notices() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $_GET['page'], $_GET['tab'] ) || 'wc-settings' !== $_GET['page'] || 'vendorhub' !== $_GET['tab'] ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$status = isset( $_GET['vendorhub_status'] ) ? sanitize_key( wp_unslash( $_GET['vendorhub_status'] ) ) : '';

		if ( empty( $status ) ) {

			return;

		}

		$is_error = in_array( $status, array( 'connect_error', 'test_error' ), true );

		$class = $is_error ? 'notice-error' : 'notice-success';

		$messages = array(

			'saved'          => __( 'Settings saved.', 'vendorhub-woocommerce' ),

			'connected'      => __( 'Successfully connected to VendorHub.', 'vendorhub-woocommerce' ),

			'disconnected'   => __( 'Disconnected from VendorHub.', 'vendorhub-woocommerce' ),

			'test_ok'        => __( 'Connection test successful.', 'vendorhub-woocommerce' ),

			'connect_error'  => __( 'Could not connect to VendorHub. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-woocommerce' ),

			'test_error'     => __( 'Connection test failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-woocommerce' ),

		);

		$msg = isset( $messages[ $status ] ) ? $messages[ $status ] : '';

		if ( $msg ) {

			printf(
				'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $class ),
				esc_html( $msg )
			);

		}
	}



	/**

	 * Resolved API base URL.

	 *

	 * @return string

	 */

	public static function get_api_base() {

		$stored = get_option( 'vendorhub_api_base', '' );

		if ( $stored ) {

			return untrailingslashit( $stored );

		}

		if ( defined( 'VENDORHUB_API_BASE' ) && VENDORHUB_API_BASE ) {

			return untrailingslashit( VENDORHUB_API_BASE );

		}

		return VENDORHUB_WC_DEFAULT_API_BASE;
	}
}
