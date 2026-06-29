<?php
/**
 * Plugin Name:       VendorHub for WooCommerce
 * Plugin URI:        https://www.myvendorhub.com
 * Description:       Connect your WooCommerce store to VendorHub for vendor order routing and fulfillment updates.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * Author:            Torpedo Solutions Inc
 * Author URI:        https://www.myvendorhub.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vendorhub-woocommerce
 * WC requires at least: 6.0
 * WC tested up to:   9.6
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'VENDORHUB_WC_VERSION', '1.0.0' );
define( 'VENDORHUB_WC_PLUGIN_FILE', __FILE__ );
define( 'VENDORHUB_WC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VENDORHUB_WC_DEFAULT_API_BASE', 'https://www.myvendorhub.com' );

require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-hmac.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-connect.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-launch.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-vendor-meta.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-order-sync.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-rest.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-privacy.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-onboarding.php';
require_once VENDORHUB_WC_PLUGIN_DIR . 'includes/class-vendorhub-settings.php';

register_activation_hook( VENDORHUB_WC_PLUGIN_FILE, array( 'VendorHub_Onboarding', 'activate' ) );

/**
 * Load plugin text domain for translations.
 */
function vendorhub_wc_load_textdomain() {
	load_plugin_textdomain(
		'vendorhub-woocommerce',
		false,
		dirname( plugin_basename( VENDORHUB_WC_PLUGIN_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'vendorhub_wc_load_textdomain' );

/**
 * Bootstrap plugin after WooCommerce loads.
 */
function vendorhub_wc_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'vendorhub_wc_missing_wc_notice' );
		return;
	}

	VendorHub_Settings::init();
	VendorHub_Vendor_Meta::init();
	VendorHub_Order_Sync::init();
	VendorHub_REST::init();
}
add_action( 'plugins_loaded', 'vendorhub_wc_init' );

/**
 * Admin notice when WooCommerce is inactive.
 */
function vendorhub_wc_missing_wc_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__(
		'VendorHub for WooCommerce requires WooCommerce to be installed and active.',
		'vendorhub-woocommerce'
	);
	echo '</p></div>';
}

/**
 * Declare HPOS compatibility.
 */
function vendorhub_wc_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			VENDORHUB_WC_PLUGIN_FILE,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'vendorhub_wc_declare_hpos_compatibility' );
