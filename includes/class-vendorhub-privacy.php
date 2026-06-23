<?php
/**
 * Privacy policy and external services disclosure.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers VendorHub privacy disclosures for WordPress.org compliance.
 */
class VendorHub_Privacy {

	const PRIVACY_URL = 'https://www.myvendorhub.com/privacy';

	/**
	 * Bootstrap privacy hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_privacy_content' ) );
	}

	/**
	 * Register suggested privacy policy text for site admins.
	 */
	public static function register_privacy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = self::get_privacy_policy_text();
		wp_add_privacy_policy_content(
			__( 'VendorHub for WooCommerce', 'vendorhub-woocommerce' ),
			wp_kses_post( $content )
		);
	}

	/**
	 * Privacy policy text shown in Settings → Privacy.
	 *
	 * @return string
	 */
	public static function get_privacy_policy_text() {
		$sections = array(
			'<h2>' . esc_html__( 'What data is sent to VendorHub', 'vendorhub-woocommerce' ) . '</h2>',
			'<p>' . esc_html__(
				'When your store is connected, this plugin transmits order and product data to VendorHub cloud servers so vendors can respond to orders. Data may include order numbers, line items, SKUs, vendor names, shipping addresses, and customer contact details required for fulfillment.',
				'vendorhub-woocommerce'
			) . '</p>',
			'<p>' . esc_html__(
				'During connection, the plugin sends your public site URL and store display name. No WordPress admin passwords are transmitted.',
				'vendorhub-woocommerce'
			) . '</p>',
			'<h2>' . esc_html__( 'External services', 'vendorhub-woocommerce' ) . '</h2>',
			'<p>' . esc_html__(
				'This plugin communicates with VendorHub API hosts over HTTPS. Default production host: https://www.myvendorhub.com. Self-hosted VendorHub installations may use a custom API base URL configured in WooCommerce → Settings → VendorHub.',
				'vendorhub-woocommerce'
			) . '</p>',
			'<ul>',
			'<li><strong>' . esc_html__( 'Connect', 'vendorhub-woocommerce' ) . '</strong> — ' . esc_html__( 'Registers the store and returns per-site credentials (redirect flow or manual token).', 'vendorhub-woocommerce' ) . '</li>',
			'<li><strong>' . esc_html__( 'Order sync', 'vendorhub-woocommerce' ) . '</strong> — ' . esc_html__( 'POST normalized order JSON when a new WooCommerce order is created.', 'vendorhub-woocommerce' ) . '</li>',
			'<li><strong>' . esc_html__( 'Inbound callbacks', 'vendorhub-woocommerce' ) . '</strong> — ' . esc_html__( 'VendorHub POSTs status, notes, and tracking to your WordPress REST API.', 'vendorhub-woocommerce' ) . '</li>',
			'</ul>',
			'<h2>' . esc_html__( 'Data retention and sale of personal data', 'vendorhub-woocommerce' ) . '</h2>',
			'<p>' . sprintf(
				/* translators: %s: VendorHub privacy policy URL */
				esc_html__(
					'VendorHub processes data according to its privacy policy at %s. Torpedo Solutions Inc does not sell personal data collected through this plugin.',
					'vendorhub-woocommerce'
				),
				'<a href="' . esc_url( self::PRIVACY_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( self::PRIVACY_URL ) . '</a>'
			) . '</p>',
			'<h2>' . esc_html__( 'Local storage', 'vendorhub-woocommerce' ) . '</h2>',
			'<p>' . esc_html__(
				'The plugin stores connection credentials (store ID, API token, plugin token) in WordPress options. These are removed on uninstall unless VENDORHUB_WC_KEEP_DATA is defined as true in wp-config.php.',
				'vendorhub-woocommerce'
			) . '</p>',
		);

		return implode( "\n", $sections );
	}
}
