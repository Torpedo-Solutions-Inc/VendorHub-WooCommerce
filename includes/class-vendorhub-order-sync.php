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

	const SYNCED_META_KEY    = '_vendorhub_synced';
	const SYNCING_META_KEY   = '_vendorhub_syncing';
	const RESPONSES_META_KEY = '_vendorhub_vendor_responses_created';

	/** @var string[] Order statuses that trigger forward when changed. */
	const FORWARDABLE_STATUSES = array( 'processing', 'completed' );

	/**
	 * Register WooCommerce hooks.
	 */
	public static function init() {
		// woocommerce_new_order fires before line items are persisted — do not sync there.
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'on_order_ready' ), 20, 1 );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_order_ready' ), 20, 1 );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'on_order_ready' ), 20, 2 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'on_order_ready' ), 20, 1 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_order_status_changed' ), 20, 4 );
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

		if ( self::is_order_fully_synced( $order ) ) {
			return;
		}

		self::forward_order( $order );
	}

	/**
	 * Forward when an order reaches a routable status (admin status changes).
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Previous status.
	 * @param string   $to       New status.
	 * @param WC_Order $order    Order object.
	 */
	public static function on_order_status_changed( $order_id, $from, $to, $order ) {
		unset( $from );

		if ( ! in_array( $to, self::FORWARDABLE_STATUSES, true ) ) {
			return;
		}

		self::on_order_ready( $order_id, $order );
	}

	/**
	 * Whether the order was accepted by VendorHub with vendor responses.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return bool
	 */
	private static function is_order_fully_synced( $order ) {
		if ( 'yes' !== $order->get_meta( self::SYNCED_META_KEY ) ) {
			return false;
		}

		if ( ! $order->meta_exists( self::RESPONSES_META_KEY ) ) {
			return true;
		}

		return (int) $order->get_meta( self::RESPONSES_META_KEY ) > 0;
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

		if ( self::is_order_fully_synced( $order ) ) {
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

		if ( ! self::payload_has_vendors( $payload ) ) {
			self::log(
				'Order ' . $order->get_id() . ' has no vendor on line items (meta key: ' . VendorHub_Vendor_Meta::get_vendor_meta_key() . ')'
			);
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
			$data              = json_decode( $raw, true );
			$responses_created = is_array( $data ) && array_key_exists( 'vendorResponsesCreated', $data )
				? (int) $data['vendorResponsesCreated']
				: 0;
			$mark_synced       = self::should_mark_synced( $payload, $responses_created );

			if ( $mark_synced ) {
				$order->update_meta_data( self::SYNCED_META_KEY, 'yes' );
			} else {
				$order->delete_meta_data( self::SYNCED_META_KEY );
			}

			$order->update_meta_data( self::RESPONSES_META_KEY, $responses_created );
			$order->delete_meta_data( self::SYNCING_META_KEY );
			$order->save();

			$log_msg = 'Order ' . $order->get_id() . ' forwarded to VendorHub';
			if ( is_array( $data ) && array_key_exists( 'vendorResponsesCreated', $data ) ) {
				$log_msg .= ' (vendorResponsesCreated: ' . $responses_created . ')';
			}
			if ( ! $mark_synced ) {
				$log_msg .= ' — will retry after vendors are registered in VendorHub';
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

			$quantity   = max( 1, (int) $item->get_quantity() );
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
				'name'     => $shipping_name ? $shipping_name : __( 'Customer', 'vendorhub-for-woocommerce' ),
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
			return VendorHub_Vendor_Meta::format_vendor_value( $value );
		}

		if ( $product instanceof WC_Product ) {
			$value = $product->get_meta( $key, true );
			if ( ! empty( $value ) ) {
				return VendorHub_Vendor_Meta::format_vendor_value( $value );
			}
		}

		return VendorHub_Vendor_Meta::resolve_product_vendor(
			(int) $item->get_product_id(),
			(int) $item->get_variation_id(),
			$key
		);
	}

	/**
	 * Whether any line item in the normalized payload has a vendor.
	 *
	 * @param array $payload Normalized order payload.
	 * @return bool
	 */
	private static function payload_has_vendors( $payload ) {
		if ( empty( $payload['lineItems'] ) || ! is_array( $payload['lineItems'] ) ) {
			return false;
		}

		foreach ( $payload['lineItems'] as $line_item ) {
			if ( ! empty( $line_item['vendor'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether to mark an order as fully synced after ingest.
	 *
	 * @param array $payload            Normalized order payload.
	 * @param int   $responses_created  VendorHub vendorResponsesCreated count.
	 * @return bool
	 */
	private static function should_mark_synced( $payload, $responses_created ) {
		if ( ! self::payload_has_vendors( $payload ) ) {
			return true;
		}

		return $responses_created > 0;
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
