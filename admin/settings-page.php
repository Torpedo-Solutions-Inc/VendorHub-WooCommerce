<?php

/**
 * VendorHub WooCommerce settings page.
 *
 * Rendered inside WooCommerce's #mainform — do not nest <form> tags here.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$api_base        = VendorHub_Settings::get_api_base();
$store_id        = get_option( 'vendorhub_store_id', '' );
$is_connected    = VendorHub_Connect::is_connected();
$supports_direct = VendorHub_Connect::supports_direct_connect();
$privacy_url     = VendorHub_Privacy::PRIVACY_URL;
$admin_post_url  = admin_url( 'admin-post.php' );

?>

<h2><?php esc_html_e( 'VendorHub', 'vendorhub-woocommerce' ); ?></h2>

<p>
	<?php
	esc_html_e(
		'Connect your WooCommerce store to VendorHub for vendor order routing. Manage vendors and responses in the VendorHub web dashboard.',
		'vendorhub-woocommerce'
	);
	?>
</p>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'Connection status', 'vendorhub-woocommerce' ); ?></th>
			<td>
				<?php if ( $is_connected ) : ?>
					<span class="vendorhub-status vendorhub-status--connected"><?php esc_html_e( 'Connected', 'vendorhub-woocommerce' ); ?></span>
				<?php else : ?>
					<span class="vendorhub-status vendorhub-status--disconnected"><?php esc_html_e( 'Not connected', 'vendorhub-woocommerce' ); ?></span>
					<p class="description"><?php esc_html_e( 'The plugin works without a connection but will not forward orders until credentials are saved.', 'vendorhub-woocommerce' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="vendorhub_store_id"><?php esc_html_e( 'Store ID', 'vendorhub-woocommerce' ); ?></label>
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
	<a
		href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_redirect_connect', 'vendorhub_redirect_connect' ) ); ?>"
		class="button button-primary"
	>
		<?php esc_html_e( 'Connect to VendorHub', 'vendorhub-woocommerce' ); ?>
	</a>

	<?php if ( $supports_direct ) : ?>
		<a
			href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_connect', 'vendorhub_connect' ) ); ?>"
			class="button"
		>
			<?php esc_html_e( 'Direct connect (dev)', 'vendorhub-woocommerce' ); ?>
		</a>
	<?php endif; ?>

	<?php if ( $is_connected ) : ?>
		<a
			href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_disconnect', 'vendorhub_disconnect' ) ); ?>"
			class="button"
		>
			<?php esc_html_e( 'Disconnect', 'vendorhub-woocommerce' ); ?>
		</a>
		<a
			href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_test_connection', 'vendorhub_test_connection' ) ); ?>"
			class="button"
		>
			<?php esc_html_e( 'Test connection', 'vendorhub-woocommerce' ); ?>
		</a>
	<?php endif; ?>
</p>

<hr />

<h3><?php esc_html_e( 'Manual connection', 'vendorhub-woocommerce' ); ?></h3>

<p class="description">
	<?php
	esc_html_e(
		'Alternatively, copy your Store ID and API token from VendorHub → Settings → API access and paste them below.',
		'vendorhub-woocommerce'
	);
	?>
</p>

<?php wp_nonce_field( 'vendorhub_save_credentials', 'vendorhub_save_credentials_nonce', false ); ?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="vendorhub_manual_store_id"><?php esc_html_e( 'Store ID', 'vendorhub-woocommerce' ); ?></label>
			</th>
			<td>
				<input type="text" id="vendorhub_manual_store_id" name="vendorhub_manual_store_id" class="regular-text" value="" autocomplete="off" />
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="vendorhub_manual_api_token"><?php esc_html_e( 'API token', 'vendorhub-woocommerce' ); ?></label>
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
		class="button button-primary"
		formaction="<?php echo esc_url( $admin_post_url ); ?>"
		formmethod="post"
		name="action"
		value="vendorhub_save_credentials"
	>
		<?php esc_html_e( 'Save credentials', 'vendorhub-woocommerce' ); ?>
	</button>
</p>

<hr />

<h3><?php esc_html_e( 'Settings', 'vendorhub-woocommerce' ); ?></h3>

<?php wp_nonce_field( 'vendorhub_save_settings', 'vendorhub_settings_nonce', false ); ?>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="vendorhub_api_base"><?php esc_html_e( 'VendorHub API base URL', 'vendorhub-woocommerce' ); ?></label>
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
					<?php esc_html_e( 'VendorHub backend URL (e.g. https://api.vendorhub.app or your ngrok tunnel).', 'vendorhub-woocommerce' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Site URL', 'vendorhub-woocommerce' ); ?></th>
			<td>
				<code><?php echo esc_html( VendorHub_Connect::get_site_url() ); ?></code>
				<p class="description"><?php esc_html_e( 'Sent to VendorHub during connect.', 'vendorhub-woocommerce' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Callback endpoint', 'vendorhub-woocommerce' ); ?></th>
			<td>
				<code><?php echo esc_html( rest_url( 'vendorhub/v1/order/123' ) ); ?></code>
				<p class="description"><?php esc_html_e( 'VendorHub pushes order updates to this REST route.', 'vendorhub-woocommerce' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>

<p class="description">
	<?php esc_html_e( 'Use the Save changes button at the bottom of this page to save settings.', 'vendorhub-woocommerce' ); ?>
</p>

<hr />

<h3><?php esc_html_e( 'Privacy & external services', 'vendorhub-woocommerce' ); ?></h3>

<p>
	<?php
	printf(
		/* translators: %s: VendorHub privacy policy URL */
		esc_html__(
			'This plugin sends order data to VendorHub cloud servers. See the suggested privacy policy text under Settings → Privacy, or read %s.',
			'vendorhub-woocommerce'
		),
		'<a href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $privacy_url ) . '</a>'
	);
	?>
</p>

<style>
	.vendorhub-status { font-weight: 600; }
	.vendorhub-status--connected { color: #007017; }
	.vendorhub-status--disconnected { color: #b32d2e; }
	.vendorhub-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
</style>
