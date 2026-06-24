<?php
/**
 * Test double for VendorHub_Connect::is_connected().
 *
 * @package VendorHub_WooCommerce
 */

/**
 * Minimal connect helper for launch gating tests.
 */
class VendorHub_Test_Connect {

	/** @var bool */
	public static $connected = false;

	/**
	 * Whether the store has active credentials.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		return self::$connected;
	}
}

if ( ! class_exists( 'VendorHub_Connect' ) ) {
	class_alias( 'VendorHub_Test_Connect', 'VendorHub_Connect' );
}
