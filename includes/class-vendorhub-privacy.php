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
			__( 'MyVendorHub for WooCommerce', 'myvendorhub-for-woocommerce' ),
			wp_kses_post( $content )
		);
	}

	/**
	 * Scannable disclosure items for onboarding (summarizes privacy policy content).
	 *
	 * @return array<int, array{title:string,description:string,has_privacy_link?:bool}>
	 */
	public static function get_disclosure_checklist() {
		return array(
			array(
				'title'       => __( 'What data is sent to MyVendorHub', 'myvendorhub-for-woocommerce' ),
				'description' => __(
					'When your store is connected, this plugin transmits order and product data to MyVendorHub cloud servers so vendors can respond to orders. Data may include order numbers, line items, SKUs, vendor names, shipping addresses, and customer contact details required for fulfillment.',
					'myvendorhub-for-woocommerce'
				),
			),
			array(
				'title'       => __( 'What is not sent', 'myvendorhub-for-woocommerce' ),
				'description' => __(
					'During connection, the plugin sends your public site URL and store display name. No WordPress admin passwords are transmitted.',
					'myvendorhub-for-woocommerce'
				),
			),
			array(
				'title'       => __( 'Inbound callbacks', 'myvendorhub-for-woocommerce' ),
				'description' => __(
					'MyVendorHub POSTs status, notes, and tracking to your WordPress REST API.',
					'myvendorhub-for-woocommerce'
				),
			),
			array(
				'title'            => __( 'External services', 'myvendorhub-for-woocommerce' ),
				'description'      => __(
					'This plugin communicates with MyVendorHub API hosts over HTTPS. MyVendorHub processes data according to its privacy policy.',
					'myvendorhub-for-woocommerce'
				),
				'has_privacy_link' => true,
			),
		);
	}

	/**
	 * Privacy policy text shown in Settings → Privacy.
	 *
	 * @return string
	 */
	public static function get_privacy_policy_text() {
		$checklist = self::get_disclosure_checklist();

		$sections = array(
			'<h2>' . esc_html( $checklist[0]['title'] ) . '</h2>',
			'<p>' . esc_html( $checklist[0]['description'] ) . '</p>',
			'<p>' . esc_html( $checklist[1]['description'] ) . '</p>',
			'<h2>' . esc_html( $checklist[3]['title'] ) . '</h2>',
			'<p>' . esc_html__(
				'This plugin communicates with MyVendorHub API hosts over HTTPS. Default production host: https://www.myvendorhub.com. Self-hosted MyVendorHub installations may use a custom API base URL configured in WooCommerce → Settings → MyVendorHub.',
				'myvendorhub-for-woocommerce'
			) . '</p>',
			'<ul>',
			'<li><strong>' . esc_html__( 'Connect', 'myvendorhub-for-woocommerce' ) . '</strong> — ' . esc_html__( 'Registers the store and returns per-site credentials (redirect flow or manual token).', 'myvendorhub-for-woocommerce' ) . '</li>',
			'<li><strong>' . esc_html__( 'Order sync', 'myvendorhub-for-woocommerce' ) . '</strong> — ' . esc_html__( 'POST normalized order JSON when a new WooCommerce order is created.', 'myvendorhub-for-woocommerce' ) . '</li>',
			'<li><strong>' . esc_html( $checklist[2]['title'] ) . '</strong> — ' . esc_html( $checklist[2]['description'] ) . '</li>',
			'</ul>',
			'<h2>' . esc_html__( 'Data retention and sale of personal data', 'myvendorhub-for-woocommerce' ) . '</h2>',
			'<p>' . sprintf(
				/* translators: %s: VendorHub privacy policy URL */
				esc_html__(
					'MyVendorHub processes data according to its privacy policy at %s. Torpedo Solutions Inc does not sell personal data collected through this plugin.',
					'myvendorhub-for-woocommerce'
				),
				'<a href="' . esc_url( self::PRIVACY_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( self::PRIVACY_URL ) . '</a>'
			) . '</p>',
			'<h2>' . esc_html__( 'Local storage', 'myvendorhub-for-woocommerce' ) . '</h2>',
			'<p>' . esc_html__(
				'The plugin stores connection credentials (store ID, API token, plugin token) in WordPress options. These are removed on uninstall unless VENDORHUB_WC_KEEP_DATA is defined as true in wp-config.php.',
				'myvendorhub-for-woocommerce'
			) . '</p>',
		);

		return implode( "\n", $sections );
	}
}
