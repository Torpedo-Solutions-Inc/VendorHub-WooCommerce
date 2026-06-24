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

		VendorHub_Onboarding::init();

		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );

		add_action( 'woocommerce_settings_tabs_vendorhub', array( __CLASS__, 'render_settings_page' ) );

		add_action( 'woocommerce_update_options_vendorhub', array( __CLASS__, 'save_settings' ) );

		add_action( 'admin_post_vendorhub_connect', array( __CLASS__, 'handle_connect' ) );

		add_action( 'admin_post_vendorhub_disconnect', array( __CLASS__, 'handle_disconnect' ) );

		add_action( 'admin_post_vendorhub_test_connection', array( __CLASS__, 'handle_test_connection' ) );

		add_action( 'admin_post_vendorhub_redirect_connect', array( __CLASS__, 'handle_redirect_connect' ) );

		add_action( 'admin_post_vendorhub_oauth_callback', array( __CLASS__, 'handle_oauth_callback' ) );

		add_action( 'admin_post_vendorhub_save_credentials', array( __CLASS__, 'handle_save_credentials' ) );

		add_action( 'admin_init', array( 'VendorHub_Connect', 'maybe_handle_redirect_return' ) );

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		add_filter( 'allowed_redirect_hosts', array( __CLASS__, 'allow_vendorhub_redirect_hosts' ) );

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

		check_admin_referer( 'vendorhub_redirect_connect', 'vendorhub_redirect_connect_nonce' );

		$accepted = isset( $_POST['vendorhub_accept_permissions'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['vendorhub_accept_permissions'] ) );

		if ( ! $accepted ) {

			wp_safe_redirect( VendorHub_Connect::settings_url( 'permissions_required' ) );

			exit;

		}

		wp_safe_redirect( VendorHub_Connect::get_redirect_url() );

		exit;
	}



	/**
	 * OAuth callback handler (Phase 2 — requires platform /oauth/token).
	 */
	public static function handle_oauth_callback() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback uses state validation.
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

		if ( empty( $code ) ) {
			wp_safe_redirect( VendorHub_Connect::settings_url( 'connect_error' ) );
			exit;
		}

		$result = VendorHub_Connect::exchange_oauth_code( $code, $state );

		wp_safe_redirect( VendorHub_Connect::settings_url( $result['success'] ? 'connected' : 'connect_error' ) );
		exit;
	}



	/**

	 * Manual credentials paste handler.

	 */

	public static function handle_save_credentials() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-woocommerce' ) );

		}

		check_admin_referer( 'vendorhub_save_credentials', 'vendorhub_save_credentials_nonce' );

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

		$is_error = in_array( $status, array( 'connect_error', 'test_error', 'permissions_required' ), true );

		$class = $is_error ? 'notice-error' : 'notice-success';

		$messages = array(

			'saved'                => __( 'Settings saved.', 'vendorhub-woocommerce' ),

			'welcome'              => __( 'Welcome! Review the permissions below, then connect your store to VendorHub.', 'vendorhub-woocommerce' ),

			'connected'            => __( 'Successfully connected to VendorHub.', 'vendorhub-woocommerce' ),

			'disconnected'         => __( 'Disconnected from VendorHub.', 'vendorhub-woocommerce' ),

			'test_ok'              => __( 'Connection test successful.', 'vendorhub-woocommerce' ),

			'connect_error'        => __( 'Could not connect to VendorHub. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-woocommerce' ),

			'test_error'           => __( 'Connection test failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-woocommerce' ),

			'permissions_required' => __( 'Please review and accept the permissions disclosure before connecting.', 'vendorhub-woocommerce' ),

		);

		$msg = isset( $messages[ $status ] ) ? $messages[ $status ] : '';

		if ( $msg ) {

			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $msg );

			if ( 'connected' === $status && VendorHub_Connect::is_connected() ) {

				echo ' <a href="' . esc_url( VendorHub_Connect::get_dashboard_url() ) . '" class="button button-primary" target="_blank" rel="noopener noreferrer" style="margin-left:8px;vertical-align:middle;">';

				esc_html_e( 'Open VendorHub dashboard', 'vendorhub-woocommerce' );

				echo '</a>';

			}

			echo '</p></div>';

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



	/**

	 * Nonce-protected admin-post.php URL for settings actions.

	 *

	 * @param string $action       admin-post action name.

	 * @param string $nonce_action Nonce action name.

	 * @return string

	 */

	public static function admin_post_url( $action, $nonce_action ) {

		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . $action ),
			$nonce_action
		);
	}



	/**

	 * Allow redirects to the configured VendorHub API host.

	 *

	 * @param string[] $hosts Allowed redirect hosts.

	 * @return string[]

	 */

	public static function allow_vendorhub_redirect_hosts( $hosts ) {

		$host = wp_parse_url( self::get_api_base(), PHP_URL_HOST );

		if ( $host && ! in_array( $host, $hosts, true ) ) {

			$hosts[] = $host;

		}

		return $hosts;
	}
}
