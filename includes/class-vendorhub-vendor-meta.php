<?php
/**
 * Configurable product vendor meta key (VendorHub integration contract).
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vendor meta key option, server pull, and product meta key discovery.
 */
class VendorHub_Vendor_Meta {

	const OPTION_KEY              = 'vendorhub_vendor_meta_key';
	const DEFAULT_KEY             = '_vendor';
	const SETTINGS_SYNC_TRANSIENT = 'vendorhub_integration_settings_synced';
	const SETTINGS_SYNC_TTL       = 300;

	/**
	 * Register admin hooks for integration-settings pull.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_pull_on_admin_load' ) );
	}

	/**
	 * Pull integration settings when an admin with store access loads wp-admin.
	 */
	public static function maybe_pull_on_admin_load() {
		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		self::maybe_pull_integration_settings();
	}

	/**
	 * Resolved vendor meta key for order forwarding (default _vendor when unset).
	 *
	 * @return string
	 */
	public static function get_vendor_meta_key() {
		$stored = get_option( self::OPTION_KEY, '' );
		if ( '' === $stored ) {
			return self::DEFAULT_KEY;
		}

		return (string) $stored;
	}

	/**
	 * Validate a vendor meta key slug.
	 *
	 * @param string $key Meta key.
	 * @return bool
	 */
	public static function validate_vendor_meta_key( $key ) {
		$key = trim( (string) $key );

		if ( '' === $key || strlen( $key ) > 191 ) {
			return false;
		}

		return (bool) preg_match( '/^[a-zA-Z0-9_\-]+$/', $key );
	}

	/**
	 * Persist vendor meta key locally.
	 *
	 * @param string $key Meta key.
	 * @return bool
	 */
	public static function set_vendor_meta_key( $key ) {
		if ( ! self::validate_vendor_meta_key( $key ) ) {
			return false;
		}

		update_option( self::OPTION_KEY, $key, false );
		return true;
	}

	/**
	 * Fetch integration settings from VendorHub (throttled unless forced).
	 *
	 * @param bool $force Skip throttle (e.g. Test connection).
	 */
	public static function maybe_pull_integration_settings( $force = false ) {
		if ( ! VendorHub_Connect::is_connected() ) {
			return;
		}

		if ( ! $force && get_transient( self::SETTINGS_SYNC_TRANSIENT ) ) {
			return;
		}

		if ( self::pull_integration_settings() ) {
			set_transient( self::SETTINGS_SYNC_TRANSIENT, 1, self::SETTINGS_SYNC_TTL );
		}
	}

	/**
	 * GET integration-settings from VendorHub and cache vendorMetaKey.
	 *
	 * @return bool True when a valid key was cached.
	 */
	public static function pull_integration_settings() {
		$store_id  = get_option( 'vendorhub_store_id', '' );
		$api_token = get_option( 'vendorhub_api_token', '' );

		if ( empty( $store_id ) || empty( $api_token ) ) {
			return false;
		}

		$api_base = VendorHub_Settings::get_api_base();
		$url      = trailingslashit( $api_base ) . 'api/stores/' . rawurlencode( $store_id ) . '/integration-settings';

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			VendorHub_Connect::log( 'Integration settings pull failed: ' . $response->get_error_message() );
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			VendorHub_Connect::log( 'Integration settings pull rejected (' . $code . '): ' . $raw );
			return false;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['vendorMetaKey'] ) ) {
			return false;
		}

		$key      = sanitize_text_field( (string) $data['vendorMetaKey'] );
		$previous = get_option( self::OPTION_KEY, '' );

		if ( ! self::set_vendor_meta_key( $key ) ) {
			VendorHub_Connect::log( 'Integration settings returned invalid vendorMetaKey: ' . $key );
			return false;
		}

		if ( $key !== $previous ) {
			VendorHub_Connect::log( 'Updated vendor meta key from VendorHub: ' . $key );
		}

		return true;
	}

	/**
	 * Distinct non-empty product postmeta keys, excluding WooCommerce internals.
	 *
	 * @return string[]
	 */
	public static function get_product_meta_keys() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_key
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type IN (%s, %s)
				AND pm.meta_value IS NOT NULL
				AND pm.meta_value != ''
				ORDER BY pm.meta_key ASC",
				'product',
				'product_variation'
			)
		);

		if ( ! is_array( $keys ) ) {
			return array();
		}

		$filtered = array();
		foreach ( $keys as $key ) {
			if ( self::is_internal_product_meta_key( $key ) ) {
				continue;
			}
			$filtered[] = (string) $key;
		}

		return $filtered;
	}

	/**
	 * Distinct non-empty vendor values for a product meta key.
	 *
	 * @param string|null $meta_key Meta key; defaults to configured vendor meta key.
	 * @return string[]
	 */
	public static function get_product_vendor_values( $meta_key = null ) {
		global $wpdb;

		$key = null === $meta_key ? self::get_vendor_meta_key() : sanitize_text_field( (string) $meta_key );
		if ( ! self::validate_vendor_meta_key( $key ) ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type IN (%s, %s)
				AND pm.meta_key = %s
				AND pm.meta_value IS NOT NULL
				AND pm.meta_value != ''
				ORDER BY pm.meta_value ASC",
				'product',
				'product_variation',
				$key
			)
		);

		if ( ! is_array( $values ) ) {
			return array();
		}

		$vendors = array();
		foreach ( $values as $value ) {
			$formatted = self::format_vendor_value( $value );
			if ( '' !== $formatted ) {
				$vendors[] = $formatted;
			}
		}

		return array_values( array_unique( $vendors ) );
	}

	/**
	 * Resolve vendor name from product or variation post meta.
	 *
	 * @param int         $product_id   Product ID.
	 * @param int         $variation_id Variation ID.
	 * @param string|null $meta_key     Meta key; defaults to configured vendor meta key.
	 * @return string|null
	 */
	public static function resolve_product_vendor( $product_id, $variation_id = 0, $meta_key = null ) {
		$key = null === $meta_key ? self::get_vendor_meta_key() : sanitize_text_field( (string) $meta_key );

		if ( $variation_id ) {
			$value = get_post_meta( (int) $variation_id, $key, true );
			if ( ! empty( $value ) ) {
				return self::format_vendor_value( $value );
			}
		}

		if ( $product_id ) {
			$value = get_post_meta( (int) $product_id, $key, true );
			if ( ! empty( $value ) ) {
				return self::format_vendor_value( $value );
			}
		}

		return null;
	}

	/**
	 * Normalize a raw vendor meta value to a display string.
	 *
	 * @param mixed $value Meta value.
	 * @return string
	 */
	public static function format_vendor_value( $value ) {
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( is_scalar( $value ) && is_numeric( $value ) ) {
			return self::resolve_vendor_display_name( (int) $value );
		}

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Resolve vendor user ID to display name when possible.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string
	 */
	public static function resolve_vendor_display_name( $user_id ) {
		$user = get_userdata( $user_id );
		if ( $user ) {
			return $user->display_name ? $user->display_name : $user->user_login;
		}
		return (string) $user_id;
	}

	/**
	 * Whether a meta key is a WooCommerce internal product field.
	 *
	 * @param string $key Meta key.
	 * @return bool
	 */
	public static function is_internal_product_meta_key( $key ) {
		static $exact = null;

		if ( null === $exact ) {
			$exact = array(
				'_price',
				'_regular_price',
				'_sale_price',
				'_sku',
				'_stock',
				'_stock_status',
				'_manage_stock',
				'_weight',
				'_length',
				'_width',
				'_height',
				'_virtual',
				'_downloadable',
				'_product_version',
				'_tax_status',
				'_tax_class',
				'_backorders',
				'_sold_individually',
				'_purchase_note',
				'_featured',
				'_visibility',
				'_download_limit',
				'_download_expiry',
				'_wc_average_rating',
				'_wc_rating_count',
				'_wc_review_count',
				'_edit_lock',
				'_edit_last',
				'_thumbnail_id',
				'_product_attributes',
				'_default_attributes',
				'_product_image_gallery',
				'_children',
				'_crosssell_ids',
				'_upsell_ids',
				'_variation_description',
				'_min_variation_price',
				'_max_variation_price',
				'_min_variation_regular_price',
				'_max_variation_regular_price',
				'_min_variation_sale_price',
				'_max_variation_sale_price',
				'_min_price_variation_id',
				'_max_price_variation_id',
				'_min_regular_price_variation_id',
				'_max_regular_price_variation_id',
				'_min_sale_price_variation_id',
				'_max_sale_price_variation_id',
			);
		}

		if ( in_array( $key, $exact, true ) ) {
			return true;
		}

		if ( 0 === strpos( $key, '_wc_' ) || 0 === strpos( $key, '_wp_' ) ) {
			return true;
		}

		return false;
	}
}
