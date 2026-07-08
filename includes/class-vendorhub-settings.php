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

		add_action( 'admin_post_vendorhub_launch', array( __CLASS__, 'handle_launch' ) );

		add_action( 'admin_init', array( 'VendorHub_Connect', 'maybe_handle_redirect_return' ) );

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles' ) );

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

		$tabs['vendorhub'] = __( 'VendorHub', 'vendorhub-for-woocommerce' );

		return $tabs;
	}



	/**

	 * Render settings page template.

	 */

	public static function render_settings_page() {

		require VENDORHUB_WC_PLUGIN_DIR . 'admin/settings-page.php';
	}



	/**
	 * Enqueue admin styles on the VendorHub WooCommerce settings tab.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public static function enqueue_admin_styles( $hook_suffix ) {
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab routing only; no mutation.
		if ( ! isset( $_GET['tab'] ) || 'vendorhub' !== $_GET['tab'] ) {
			return;
		}

		wp_enqueue_style(
			'vendorhub-admin-settings',
			plugins_url( 'admin/admin-settings.css', VENDORHUB_WC_PLUGIN_FILE ),
			array(),
			VENDORHUB_WC_VERSION
		);
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

		$api_base = self::normalize_api_base( $api_base );

		if ( $api_base ) {

			update_option( 'vendorhub_api_base', $api_base, false );

		} else {

			delete_option( 'vendorhub_api_base' );

		}

		wp_safe_redirect( VendorHub_Connect::settings_url( 'saved' ) );

		exit;
	}



	/**

	 * Direct connect handler (self-hosted dev only).

	 */

	public static function handle_connect() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-for-woocommerce' ) );

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

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-for-woocommerce' ) );

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
			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback uses state validation.
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

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

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-for-woocommerce' ) );

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

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-for-woocommerce' ) );

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

			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-for-woocommerce' ) );

		}

		check_admin_referer( 'vendorhub_test_connection' );

		$result = VendorHub_Connect::test_connection();

		wp_safe_redirect( VendorHub_Connect::settings_url( $result['success'] ? 'test_ok' : 'test_error' ) );

		exit;
	}



	/**
	 * SSO launch handler — redirects to signed VendorHub /launch URL.
	 */
	public static function handle_launch() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'vendorhub-for-woocommerce' ) );
		}

		check_admin_referer( 'vendorhub_launch' );

		$store_id     = (string) get_option( 'vendorhub_store_id', '' );
		$plugin_token = (string) get_option( 'vendorhub_plugin_token', '' );
		$api_base     = self::get_api_base();

		if ( empty( $store_id ) || empty( $plugin_token ) ) {
			wp_safe_redirect( VendorHub_Connect::settings_url( 'launch_error' ) );
			exit;
		}

		/**
		 * WordPress user ID for SSO launch body/query (`user` / `wpUserId`).
		 * Default null omits user — required when VendorHub has no mapping for the WP user.
		 *
		 * @param int|null $wp_user_id Launch user ID, or null to omit.
		 */
		$launch_user_id = apply_filters( 'vendorhub_wc_launch_wp_user_id', null );
		if ( null !== $launch_user_id && (int) $launch_user_id <= 0 ) {
			$launch_user_id = null;
		} elseif ( null !== $launch_user_id ) {
			$launch_user_id = (int) $launch_user_id;
		}

		$url = VendorHub_Launch::build_signed_launch_url(
			$api_base,
			$store_id,
			$plugin_token,
			$launch_user_id,
			null
		);

		if ( empty( $url ) ) {
			wp_safe_redirect( VendorHub_Connect::settings_url( 'launch_error' ) );
			exit;
		}

		VendorHub_Connect::log( 'SSO launch redirect to ' . $url );

		wp_safe_redirect( $url );
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

		$is_error = in_array( $status, array( 'connect_error', 'test_error', 'permissions_required', 'launch_error' ), true );

		$class = $is_error ? 'notice-error' : 'notice-success';

		$messages = array(

			'saved'                => __( 'Settings saved.', 'vendorhub-for-woocommerce' ),

			'welcome'              => __( 'Welcome! Review the permissions below, then connect your store to VendorHub.', 'vendorhub-for-woocommerce' ),

			'connected'            => __( 'Successfully connected to VendorHub.', 'vendorhub-for-woocommerce' ),

			'disconnected'         => __( 'Disconnected from VendorHub.', 'vendorhub-for-woocommerce' ),

			'test_ok'              => __( 'Connection test successful.', 'vendorhub-for-woocommerce' ),

			'connect_error'        => __( 'Could not connect to VendorHub. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			'test_error'           => __( 'Connection test failed. See WooCommerce → Status → Logs (source: vendorhub).', 'vendorhub-for-woocommerce' ),

			'permissions_required' => __( 'Please review and accept the permissions disclosure before connecting.', 'vendorhub-for-woocommerce' ),

			'launch_error'         => __( 'Could not open VendorHub. Disconnect, reconnect via Connect to VendorHub (not manual API paste), then try again. See WooCommerce → Status → Logs (source: vendorhub) for the launch URL.', 'vendorhub-for-woocommerce' ),

		);

		$msg = isset( $messages[ $status ] ) ? $messages[ $status ] : '';

		if ( $msg ) {

			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $msg );

			if ( 'connected' === $status && VendorHub_Launch::can_user_launch() ) {

				echo ' <a href="' . esc_url( self::admin_post_url( 'vendorhub_launch', 'vendorhub_launch' ) ) . '" class="button button-primary vendorhub-notice-launch">';

				esc_html_e( 'Open VendorHub', 'vendorhub-for-woocommerce' );

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

			$normalized = self::normalize_api_base( $stored );

			if ( $normalized ) {

				return $normalized;

			}
		}

		if ( defined( 'VENDORHUB_API_BASE' ) && VENDORHUB_API_BASE ) {

			return self::normalize_api_base( (string) VENDORHUB_API_BASE );

		}

		return VENDORHUB_WC_DEFAULT_API_BASE;
	}



	/**
	 * Normalize API base to scheme + host (+ port). Reject wp-admin / admin-post URLs.
	 *
	 * @param string $url Raw URL from settings or wp-config.
	 * @return string Normalized origin, or empty when invalid.
	 */
	public static function normalize_api_base( $url ) {

		$url = trim( (string) $url );

		if ( '' === $url ) {

			return '';

		}

		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {

			return '';

		}

		$path = isset( $parts['path'] ) ? strtolower( $parts['path'] ) : '';

		if ( false !== strpos( $path, 'wp-admin' ) || false !== strpos( $path, 'admin-post.php' ) ) {

			return '';

		}

		$base = strtolower( $parts['scheme'] ) . '://' . $parts['host'];

		if ( ! empty( $parts['port'] ) ) {

			$base .= ':' . $parts['port'];

		}

		return $base;
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
