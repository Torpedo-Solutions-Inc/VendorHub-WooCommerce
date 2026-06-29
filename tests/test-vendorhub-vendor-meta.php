<?php
/**
 * Tests for VendorHub_Vendor_Meta validation and filtering.
 *
 * @package VendorHub_WooCommerce
 */

require_once dirname( __DIR__ ) . '/includes/class-vendorhub-vendor-meta.php';

/**
 * Vendor meta key tests.
 */
class VendorHub_Vendor_Meta_Test extends PHPUnit\Framework\TestCase {

	/**
	 * Accepts common meta key shapes.
	 */
	public function test_validate_vendor_meta_key_accepts_valid_keys() {
		$this->assertTrue( VendorHub_Vendor_Meta::validate_vendor_meta_key( '_vendor' ) );
		$this->assertTrue( VendorHub_Vendor_Meta::validate_vendor_meta_key( 'vendor' ) );
		$this->assertTrue( VendorHub_Vendor_Meta::validate_vendor_meta_key( 'custom_vendor_key' ) );
		$this->assertTrue( VendorHub_Vendor_Meta::validate_vendor_meta_key( 'vendor-meta' ) );
	}

	/**
	 * Rejects empty or invalid meta keys.
	 */
	public function test_validate_vendor_meta_key_rejects_invalid_keys() {
		$this->assertFalse( VendorHub_Vendor_Meta::validate_vendor_meta_key( '' ) );
		$this->assertFalse( VendorHub_Vendor_Meta::validate_vendor_meta_key( '   ' ) );
		$this->assertFalse( VendorHub_Vendor_Meta::validate_vendor_meta_key( 'vendor key' ) );
		$this->assertFalse( VendorHub_Vendor_Meta::validate_vendor_meta_key( 'vendor.meta' ) );
		$this->assertFalse( VendorHub_Vendor_Meta::validate_vendor_meta_key( str_repeat( 'a', 192 ) ) );
	}

	/**
	 * Filters WooCommerce internal product meta keys.
	 */
	public function test_is_internal_product_meta_key() {
		$this->assertTrue( VendorHub_Vendor_Meta::is_internal_product_meta_key( '_price' ) );
		$this->assertTrue( VendorHub_Vendor_Meta::is_internal_product_meta_key( '_sku' ) );
		$this->assertTrue( VendorHub_Vendor_Meta::is_internal_product_meta_key( '_wc_foo' ) );
		$this->assertFalse( VendorHub_Vendor_Meta::is_internal_product_meta_key( '_vendor' ) );
		$this->assertFalse( VendorHub_Vendor_Meta::is_internal_product_meta_key( 'vendor' ) );
	}

	/**
	 * Formats scalar and numeric vendor meta values.
	 */
	public function test_format_vendor_value() {
		$this->assertSame( 'Acme Vendor', VendorHub_Vendor_Meta::format_vendor_value( 'Acme Vendor' ) );
		$this->assertSame( 'vendor a', VendorHub_Vendor_Meta::format_vendor_value( ' vendor a ' ) );
		$this->assertSame( '', VendorHub_Vendor_Meta::format_vendor_value( array() ) );
	}

	/**
	 * Splits comma- and pipe-separated attribute values.
	 */
	public function test_split_attribute_values() {
		$this->assertSame(
			array( 'Vendor A', 'Vendor B' ),
			VendorHub_Vendor_Meta::split_attribute_values( 'Vendor A, Vendor B' )
		);
		$this->assertSame(
			array( 'Vendor A', 'Vendor B' ),
			VendorHub_Vendor_Meta::split_attribute_values( 'Vendor A | Vendor B' )
		);
	}

	/**
	 * Builds taxonomy attribute lookup keys from a configured field name.
	 */
	public function test_get_attribute_lookup_keys_without_wc_taxonomy_helper() {
		$this->assertSame(
			array( 'vendor' ),
			VendorHub_Vendor_Meta::get_attribute_lookup_keys( 'vendor' )
		);
	}
}
