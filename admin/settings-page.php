<?php

/**
 * VendorHub WooCommerce settings page.
 *
 * Rendered inside WooCommerce's #mainform — do not nest <form> tags here.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$api_base         = VendorHub_Settings::get_api_base();
$store_id         = get_option( 'vendorhub_store_id', '' );
$is_connected     = VendorHub_Connect::is_connected();
$supports_direct  = VendorHub_Connect::supports_direct_connect();
$privacy_url      = VendorHub_Privacy::PRIVACY_URL;
$admin_post_url   = admin_url( 'admin-post.php' );
$disclosure_items = VendorHub_Onboarding::get_disclosure_checklist();
$can_launch       = VendorHub_Launch::can_user_launch();

?>

<h2><?php esc_html_e( 'MyVendorHub', 'myvendorhub-for-woocommerce' ); ?></h2>

<?php if ( $is_connected ) : ?>

	<p>
		<?php
		esc_html_e(
			'Your store is connected to MyVendorHub. Orders are forwarded automatically; manage vendors and responses in the MyVendorHub dashboard.',
			'myvendorhub-for-woocommerce'
		);
		?>
	</p>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Connection status', 'myvendorhub-for-woocommerce' ); ?></th>
				<td>
					<span class="vendorhub-status vendorhub-status--connected"><?php esc_html_e( 'Connected', 'myvendorhub-for-woocommerce' ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="vendorhub_store_id"><?php esc_html_e( 'Store ID', 'myvendorhub-for-woocommerce' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="vendorhub_store_id"
						class="regular-text"
						value="<?php echo esc_attr( $store_id ); ?>"
						readonly
						disabled
					/>
				</td>
			</tr>
		</tbody>
	</table>

	<p class="submit vendorhub-actions">
		<?php if ( $can_launch ) : ?>
			<a
				href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_launch', 'vendorhub_launch' ) ); ?>"
				class="button button-primary"
			>
				<?php esc_html_e( 'Open MyVendorHub', 'myvendorhub-for-woocommerce' ); ?>
			</a>
		<?php endif; ?>
		<a
			href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_test_connection', 'vendorhub_test_connection' ) ); ?>"
			class="button"
		>
			<?php esc_html_e( 'Test connection', 'myvendorhub-for-woocommerce' ); ?>
		</a>
		<a
			href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_disconnect', 'vendorhub_disconnect' ) ); ?>"
			class="button"
		>
			<?php esc_html_e( 'Disconnect', 'myvendorhub-for-woocommerce' ); ?>
		</a>
	</p>

<?php else : ?>

	<p>
		<?php
		esc_html_e(
			'Connect your WooCommerce store to MyVendorHub for vendor order routing. Review what data is shared before you connect.',
			'myvendorhub-for-woocommerce'
		);
		?>
	</p>

	<div class="vendorhub-onboarding-card">
		<h3><?php esc_html_e( 'Permissions & data sharing', 'myvendorhub-for-woocommerce' ); ?></h3>
		<ul class="vendorhub-disclosure-list">
			<?php foreach ( $disclosure_items as $item ) : ?>
				<li>
					<strong><?php echo esc_html( $item['title'] ); ?></strong>
					<span>
						<?php echo esc_html( $item['description'] ); ?>
						<?php if ( ! empty( $item['has_privacy_link'] ) ) : ?>
							<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $privacy_url ); ?></a>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php wp_nonce_field( 'vendorhub_redirect_connect', 'vendorhub_redirect_connect_nonce', false ); ?>

		<p>
			<label for="vendorhub_accept_permissions">
				<input type="checkbox" id="vendorhub_accept_permissions" name="vendorhub_accept_permissions" value="1" required />
				<?php esc_html_e( 'I have reviewed the permissions above and agree to connect this store to MyVendorHub.', 'myvendorhub-for-woocommerce' ); ?>
			</label>
		</p>
		<p class="submit vendorhub-actions">
			<button
				type="submit"
				class="button button-primary button-hero"
				formaction="<?php echo esc_url( $admin_post_url ); ?>"
				formmethod="post"
				name="action"
				value="vendorhub_redirect_connect"
			>
				<?php esc_html_e( 'Connect to MyVendorHub', 'myvendorhub-for-woocommerce' ); ?>
			</button>
		</p>
	</div>

<?php endif; ?>

<details class="vendorhub-advanced">
	<summary><?php esc_html_e( 'Advanced', 'myvendorhub-for-woocommerce' ); ?></summary>

	<?php if ( ! $is_connected ) : ?>
		<h4><?php esc_html_e( 'Manual connection', 'myvendorhub-for-woocommerce' ); ?></h4>
		<p class="description">
			<?php
			esc_html_e(
				'Copy your Store ID and API token from MyVendorHub → Settings → API access and paste them below.',
				'myvendorhub-for-woocommerce'
			);
			?>
		</p>

		<?php wp_nonce_field( 'vendorhub_save_credentials', 'vendorhub_save_credentials_nonce', false ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="vendorhub_manual_store_id"><?php esc_html_e( 'Store ID', 'myvendorhub-for-woocommerce' ); ?></label>
					</th>
					<td>
						<input type="text" id="vendorhub_manual_store_id" name="vendorhub_manual_store_id" class="regular-text" value="" autocomplete="off" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="vendorhub_manual_api_token"><?php esc_html_e( 'API token', 'myvendorhub-for-woocommerce' ); ?></label>
					</th>
					<td>
						<input type="password" id="vendorhub_manual_api_token" name="vendorhub_manual_api_token" class="regular-text" value="" autocomplete="off" />
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button
				type="submit"
				class="button"
				formaction="<?php echo esc_url( $admin_post_url ); ?>"
				formmethod="post"
				name="action"
				value="vendorhub_save_credentials"
			>
				<?php esc_html_e( 'Save credentials', 'myvendorhub-for-woocommerce' ); ?>
			</button>
		</p>
	<?php endif; ?>

	<h4><?php esc_html_e( 'Developer settings', 'myvendorhub-for-woocommerce' ); ?></h4>

	<?php wp_nonce_field( 'vendorhub_save_settings', 'vendorhub_settings_nonce', false ); ?>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="vendorhub_api_base"><?php esc_html_e( 'MyVendorHub API base URL', 'myvendorhub-for-woocommerce' ); ?></label>
				</th>
				<td>
					<input
						type="url"
						id="vendorhub_api_base"
						name="vendorhub_api_base"
						class="regular-text"
						value="<?php echo esc_attr( $api_base ); ?>"
						placeholder="<?php echo esc_attr( VENDORHUB_WC_DEFAULT_API_BASE ); ?>"
					/>
					<p class="description">
						<?php
						esc_html_e(
							'MyVendorHub server origin only (no paths). Default: https://www.myvendorhub.com. Do not paste the Open MyVendorHub or admin-post link here.',
							'myvendorhub-for-woocommerce'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Site URL', 'myvendorhub-for-woocommerce' ); ?></th>
				<td>
					<code><?php echo esc_html( VendorHub_Connect::get_site_url() ); ?></code>
					<p class="description"><?php esc_html_e( 'Sent to MyVendorHub during connect.', 'myvendorhub-for-woocommerce' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Callback endpoint', 'myvendorhub-for-woocommerce' ); ?></th>
				<td>
					<code><?php echo esc_html( rest_url( 'vendorhub/v1/order/123' ) ); ?></code>
					<p class="description"><?php esc_html_e( 'MyVendorHub pushes order updates to this REST route.', 'myvendorhub-for-woocommerce' ); ?></p>
				</td>
			</tr>
			<?php if ( $is_connected ) : ?>
				<?php
				$vendor_meta_key    = VendorHub_Vendor_Meta::get_vendor_meta_key();
				$discovered_vendors = VendorHub_Vendor_Meta::get_product_vendor_values();
				?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Vendor meta key', 'myvendorhub-for-woocommerce' ); ?></th>
					<td>
						<code><?php echo esc_html( $vendor_meta_key ); ?></code>
						<p class="description"><?php esc_html_e( 'Must match your product vendor field name in WooCommerce.', 'myvendorhub-for-woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Discovered vendors', 'myvendorhub-for-woocommerce' ); ?></th>
					<td>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of vendors found on products */
								_n( '%d vendor on products', '%d vendors on products', count( $discovered_vendors ), 'myvendorhub-for-woocommerce' ),
								count( $discovered_vendors )
							)
						);
						if ( ! empty( $discovered_vendors ) ) {
							echo '<br /><code>' . esc_html( implode( ', ', $discovered_vendors ) ) . '</code>';
						}
						?>
						<p class="description"><?php esc_html_e( 'Local preview of what Sync Now should import into MyVendorHub.', 'myvendorhub-for-woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Vendor sync endpoint', 'myvendorhub-for-woocommerce' ); ?></th>
					<td>
						<code><?php echo esc_html( rest_url( 'vendorhub/v1/product-vendors' ) ); ?></code>
						<p class="description"><?php esc_html_e( 'MyVendorHub must reach this URL from the internet. Localhost stores need ngrok or a public tunnel.', 'myvendorhub-for-woocommerce' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<p class="description">
		<?php esc_html_e( 'Use the Save changes button at the bottom of this page to save the API base URL.', 'myvendorhub-for-woocommerce' ); ?>
	</p>

	<?php if ( $supports_direct ) : ?>
		<h4><?php esc_html_e( 'Direct connect (development)', 'myvendorhub-for-woocommerce' ); ?></h4>
		<p class="description">
			<?php esc_html_e( 'HMAC-signed registration using VENDORHUB_WC_CONNECT_SECRET in wp-config.php.', 'myvendorhub-for-woocommerce' ); ?>
		</p>
		<p class="submit vendorhub-actions">
			<a
				href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_connect', 'vendorhub_connect' ) ); ?>"
				class="button"
			>
				<?php esc_html_e( 'Direct connect (dev)', 'myvendorhub-for-woocommerce' ); ?>
			</a>
		</p>
	<?php endif; ?>

	<h4><?php esc_html_e( 'Privacy & external services', 'myvendorhub-for-woocommerce' ); ?></h4>
	<p>
		<?php
		printf(
			/* translators: %s: MyVendorHub privacy policy URL */
			esc_html__(
				'Full privacy disclosure is available under Settings → Privacy. MyVendorHub privacy policy: %s',
				'myvendorhub-for-woocommerce'
			),
			'<a href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $privacy_url ) . '</a>'
		);
		?>
	</p>
</details>
