<?php
/**
 * Forward WooCommerce orders to VendorHub ingest API.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order sync hooks and normalization.
 */
class VendorHub_Order_Sync {

	const SYNCED_META_KEY  = '_vendorhub_synced';
	const SYNCING_META_KEY = '_vendorhub_syncing';

	/**
	 * Register WooCommerce hooks.
	 */
	public static function init() {
		// woocommerce_new_order fires before line items are persisted — do not sync there.
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'on_order_ready' ), 20, 1 );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_order_ready' ), 20, 1 );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'on_order_ready' ), 20, 2 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'on_order_ready' ), 20, 1 );
	}

	/**
	 * Forward order once line items and product meta are available.
	 *
	 * @param int|WC_Order $order_id Order ID or order object.
	 * @param WC_Order     $order    Order object (admin save hook).
	 */
	public static function on_order_ready( $order_id, $order = null ) {
		if ( $order_id instanceof WC_Order ) {
			$order = $order_id;
		} elseif ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( 'yes' === $order->get_meta( self::SYNCED_META_KEY ) ) {
			return;
		}

		self::forward_order( $order );
	}

	/**
	 * Normalize and POST order to VendorHub.
	 *
	 * @param WC_Order $order WooCommerce order.
	 */
	public static function forward_order( $order ) {
		if ( ! VendorHub_Connect::is_connected() ) {
			return;
		}

		if ( 'yes' === $order->get_meta( self::SYNCED_META_KEY ) ) {
			return;
		}

		if ( 'yes' === $order->get_meta( self::SYNCING_META_KEY ) ) {
			return;
		}

		if ( 0 === count( $order->get_items( 'line_item' ) ) ) {
			return;
		}

		$store_id  = get_option( 'vendorhub_store_id', '' );
		$api_token = get_option( 'vendorhub_api_token', '' );
		$api_base  = VendorHub_Settings::get_api_base();

		if ( empty( $store_id ) || empty( $api_token ) ) {
			return;
		}

		$order->update_meta_data( self::SYNCING_META_KEY, 'yes' );
		$order->save();

		VendorHub_Vendor_Meta::maybe_pull_integration_settings();

		$payload = self::normalize_order( $order );
		$body    = wp_json_encode( $payload );

		if ( false === $body ) {
			self::clear_syncing_meta( $order );
			self::log( 'Failed to encode order ' . $order->get_id() );
			return;
		}

		$url = trailingslashit( $api_base ) . 'api/stores/' . rawurlencode( $store_id ) . '/orders';

		$response = wp_safe_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			self::clear_syncing_meta( $order );
			self::log( 'Order ' . $order->get_id() . ' ingest failed: ' . $response->get_error_message() );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );

		if ( $code >= 200 && $code < 300 ) {
			$order->update_meta_data( self::SYNCED_META_KEY, 'yes' );
			$order->delete_meta_data( self::SYNCING_META_KEY );
			$order->save();

			$log_msg = 'Order ' . $order->get_id() . ' forwarded to VendorHub';
			$data    = json_decode( $raw, true );
			if ( is_array( $data ) && array_key_exists( 'vendorResponsesCreated', $data ) ) {
				$log_msg .= ' (vendorResponsesCreated: ' . (int) $data['vendorResponsesCreated'] . ')';
			}
			self::log( $log_msg );
			return;
		}

		self::clear_syncing_meta( $order );
		self::log( 'Order ' . $order->get_id() . ' ingest rejected (' . $code . '): ' . $raw );
	}

	/**
	 * Map WC order to VendorHub NormalizedOrder.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return array
	 */
	public static function normalize_order( $order ) {
		$created    = $order->get_date_created();
		$created_at = $created ? $created->date( 'c' ) : gmdate( 'c' );

		$line_items = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			$vendor  = self::resolve_line_item_vendor( $item, $product );

			$quantity  = max( 1, (int) $item->get_quantity() );
			$unit_price = (float) $item->get_total() / $quantity;

			$line_items[] = array(
				'externalId'        => (string) $item->get_id(),
				'productExternalId' => (string) $item->get_product_id(),
				'title'             => $item->get_name(),
				'name'              => $item->get_name(),
				'quantity'          => $item->get_quantity(),
				'sku'               => $product ? $product->get_sku() : '',
				'vendor'            => $vendor,
				'price'             => wc_format_decimal( $unit_price, 2 ),
				'variantTitle'      => $item->get_variation_id() ? $item->get_name() : null,
			);
		}

		$shipping = array(
			'first_name' => $order->get_shipping_first_name(),
			'last_name'  => $order->get_shipping_last_name(),
			'phone'      => $order->get_billing_phone(),
			'address_1'  => $order->get_shipping_address_1(),
			'address_2'  => $order->get_shipping_address_2(),
			'city'       => $order->get_shipping_city(),
			'state'      => $order->get_shipping_state(),
			'country'    => $order->get_shipping_country(),
			'postcode'   => $order->get_shipping_postcode(),
		);

		$shipping_name = trim( $shipping['first_name'] . ' ' . $shipping['last_name'] );

		$normalized = array(
			'externalId'  => (string) $order->get_id(),
			'platform'    => 'woocommerce',
			'orderNumber' => (string) $order->get_order_number(),
			'name'        => '#' . $order->get_order_number(),
			'createdAt'   => $created_at,
			'tags'        => array(),
			'currency'    => $order->get_currency(),
			'totalAmount' => wc_format_decimal( $order->get_total(), 2 ),
			'lineItems'   => $line_items,
		);

		$customer_email = $order->get_billing_email();
		if ( $customer_email ) {
			$normalized['customerEmail'] = $customer_email;
		}

		if ( ! empty( $shipping['address_1'] ) ) {
			$normalized['shippingAddress'] = array(
				'name'     => $shipping_name ? $shipping_name : __( 'Customer', 'vendorhub-woocommerce' ),
				'phone'    => $shipping['phone'] ? $shipping['phone'] : null,
				'address1' => $shipping['address_1'],
				'address2' => $shipping['address_2'] ? $shipping['address_2'] : null,
				'city'     => $shipping['city'],
				'province' => $shipping['state'] ? $shipping['state'] : null,
				'country'  => $shipping['country'],
				'zip'      => $shipping['postcode'],
			);
		}

		return $normalized;
	}

	/**
	 * Resolve vendor name from line item / product meta.
	 *
	 * @param WC_Order_Item_Product $item    Line item.
	 * @param WC_Product|false      $product Product.
	 * @return string|null
	 */
	private static function resolve_line_item_vendor( $item, $product ) {
		$key = VendorHub_Vendor_Meta::get_vendor_meta_key();

		$value = $item->get_meta( $key, true );
		if ( ! empty( $value ) ) {
			return self::format_vendor_value( $value );
		}

		if ( $product instanceof WC_Product ) {
			$value = $product->get_meta( $key, true );
			if ( ! empty( $value ) ) {
				return self::format_vendor_value( $value );
			}
		}

		$product_id   = (int) $item->get_product_id();
		$variation_id = (int) $item->get_variation_id();

		if ( $variation_id ) {
			$value = get_post_meta( $variation_id, $key, true );
			if ( ! empty( $value ) ) {
				return self::format_vendor_value( $value );
			}
		}

		if ( $product_id ) {
			$value = get_post_meta( $product_id, $key, true );
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
	private static function format_vendor_value( $value ) {
		return is_numeric( $value ) ? self::resolve_vendor_display_name( (int) $value ) : (string) $value;
	}

	/**
	 * Resolve vendor user ID to display name when possible.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string
	 */
	private static function resolve_vendor_display_name( $user_id ) {
		$user = get_userdata( $user_id );
		if ( $user ) {
			return $user->display_name ? $user->display_name : $user->user_login;
		}
		return (string) $user_id;
	}

	/**
	 * Clear in-flight sync lock so a later hook can retry.
	 *
	 * @param WC_Order $order WooCommerce order.
	 */
	private static function clear_syncing_meta( $order ) {
		$order->delete_meta_data( self::SYNCING_META_KEY );
		$order->save();
	}

	/**
	 * Log to WooCommerce logger.
	 *
	 * @param string $message Log message.
	 */
	private static function log( $message ) {
		VendorHub_Connect::log( $message );
	}
}
