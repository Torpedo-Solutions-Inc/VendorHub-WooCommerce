<?php
/**
 * Uninstall MyVendorHub for WooCommerce.
 *
 * @package VendorHub_WooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$keep_data = ( defined( 'VENDORHUB_WC_KEEP_DATA' ) && VENDORHUB_WC_KEEP_DATA )
	|| 'yes' === get_option( 'vendorhub_keep_data', '' );

if ( $keep_data ) {
	return;
}

$options = array(
	'vendorhub_store_id',
	'vendorhub_api_token',
	'vendorhub_plugin_token',
	'vendorhub_api_base',
	'vendorhub_vendor_meta_key',
);

foreach ( $options as $option ) {
	delete_option( $option );
}
