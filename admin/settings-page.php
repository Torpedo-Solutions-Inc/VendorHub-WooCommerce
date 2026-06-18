<?php

/**

 * VendorHub WooCommerce settings page.

 *

 * @package VendorHub_WooCommerce

 */



defined( 'ABSPATH' ) || exit;



$api_base = VendorHub_Settings::get_api_base();

$store_id = get_option( 'vendorhub_store_id', '' );

$is_connected = VendorHub_Connect::is_connected();

$supports_direct = VendorHub_Connect::supports_direct_connect();

$privacy_url = VendorHub_Privacy::PRIVACY_URL;

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

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vendorhub-inline-form">

		<?php wp_nonce_field( 'vendorhub_redirect_connect' ); ?>

		<input type="hidden" name="action" value="vendorhub_redirect_connect" />

		<?php submit_button( __( 'Connect to VendorHub', 'vendorhub-woocommerce' ), 'primary', 'submit', false ); ?>

	</form>



	<?php if ( $supports_direct ) : ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vendorhub-inline-form">

			<?php wp_nonce_field( 'vendorhub_connect' ); ?>

			<input type="hidden" name="action" value="vendorhub_connect" />

			<?php submit_button( __( 'Direct connect (dev)', 'vendorhub-woocommerce' ), 'secondary', 'submit', false ); ?>

		</form>

	<?php endif; ?>



	<?php if ( $is_connected ) : ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vendorhub-inline-form">

			<?php wp_nonce_field( 'vendorhub_disconnect' ); ?>

			<input type="hidden" name="action" value="vendorhub_disconnect" />

			<?php submit_button( __( 'Disconnect', 'vendorhub-woocommerce' ), 'secondary', 'submit', false ); ?>

		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vendorhub-inline-form">

			<?php wp_nonce_field( 'vendorhub_test_connection' ); ?>

			<input type="hidden" name="action" value="vendorhub_test_connection" />

			<?php submit_button( __( 'Test connection', 'vendorhub-woocommerce' ), 'secondary', 'submit', false ); ?>

		</form>

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



<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

	<?php wp_nonce_field( 'vendorhub_save_credentials' ); ?>

	<input type="hidden" name="action" value="vendorhub_save_credentials" />

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

	<?php submit_button( __( 'Save credentials', 'vendorhub-woocommerce' ) ); ?>

</form>



<hr />



<h3><?php esc_html_e( 'Settings', 'vendorhub-woocommerce' ); ?></h3>



<form method="post" action="">

	<?php wp_nonce_field( 'vendorhub_save_settings', 'vendorhub_settings_nonce' ); ?>

	<input type="hidden" name="action" value="woocommerce_update_options" />

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

	<?php submit_button( __( 'Save settings', 'vendorhub-woocommerce' ) ); ?>

</form>



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

	.vendorhub-inline-form { display: inline; margin: 0; }

</style>


