<?php

/**
 * VendorHub WooCommerce settings page.
 *
 * Rendered inside WooCommerce's #mainform — do not nest <form> tags here.
 *
 * @package VendorHub_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$api_base             = VendorHub_Settings::get_api_base();
$store_id             = get_option( 'vendorhub_store_id', '' );
$is_connected         = VendorHub_Connect::is_connected();
$supports_direct      = VendorHub_Connect::supports_direct_connect();
$privacy_url          = VendorHub_Privacy::PRIVACY_URL;
$admin_post_url       = admin_url( 'admin-post.php' );
$disclosure_items     = VendorHub_Onboarding::get_disclosure_checklist();
$dashboard_url        = $is_connected ? VendorHub_Connect::get_dashboard_url() : '';

?>

<h2><?php esc_html_e( 'VendorHub', 'vendorhub-woocommerce' ); ?></h2>

<?php if ( $is_connected ) : ?>

	<p>
		<?php
		esc_html_e(
			'Your store is connected to VendorHub. Orders are forwarded automatically; manage vendors and responses in the VendorHub dashboard.',
			'vendorhub-woocommerce'
		);
		?>
	</p>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Connection status', 'vendorhub-woocommerce' ); ?></th>
				<td>
					<span class="vendorhub-status vendorhub-status--connected"><?php esc_html_e( 'Connected', 'vendorhub-woocommerce' ); ?></span>
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
			href="<?php echo esc_url( $dashboard_url ); ?>"
			class="button button-primary"
			target="_blank"
			rel="noopener noreferrer"
		>
			<?php esc_html_e( 'Open VendorHub dashboard', 'vendorhub-woocommerce' ); ?>
		</a>
		<a
			href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_test_connection', 'vendorhub_test_connection' ) ); ?>"
			class="button"
		>
			<?php esc_html_e( 'Test connection', 'vendorhub-woocommerce' ); ?>
		</a>
		<a
			href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_disconnect', 'vendorhub_disconnect' ) ); ?>"
			class="button"
		>
			<?php esc_html_e( 'Disconnect', 'vendorhub-woocommerce' ); ?>
		</a>
	</p>

<?php else : ?>

	<p>
		<?php
		esc_html_e(
			'Connect your WooCommerce store to VendorHub for vendor order routing. Review what data is shared before you connect.',
			'vendorhub-woocommerce'
		);
		?>
	</p>

	<div class="vendorhub-onboarding-card">
		<h3><?php esc_html_e( 'Permissions & data sharing', 'vendorhub-woocommerce' ); ?></h3>
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

		<?php wp_nonce_field( 'vendorhub_redirect_connect' ); ?>

		<p>
			<label for="vendorhub_accept_permissions">
				<input type="checkbox" id="vendorhub_accept_permissions" name="vendorhub_accept_permissions" value="1" required />
				<?php esc_html_e( 'I have reviewed the permissions above and agree to connect this store to VendorHub.', 'vendorhub-woocommerce' ); ?>
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
				<?php esc_html_e( 'Connect to VendorHub', 'vendorhub-woocommerce' ); ?>
			</button>
		</p>
	</div>

<?php endif; ?>

<details class="vendorhub-advanced">
	<summary><?php esc_html_e( 'Advanced', 'vendorhub-woocommerce' ); ?></summary>

	<?php if ( ! $is_connected ) : ?>
		<h4><?php esc_html_e( 'Manual connection', 'vendorhub-woocommerce' ); ?></h4>
		<p class="description">
			<?php
			esc_html_e(
				'Copy your Store ID and API token from VendorHub → Settings → API access and paste them below.',
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
				class="button"
				formaction="<?php echo esc_url( $admin_post_url ); ?>"
				formmethod="post"
				name="action"
				value="vendorhub_save_credentials"
			>
				<?php esc_html_e( 'Save credentials', 'vendorhub-woocommerce' ); ?>
			</button>
		</p>
	<?php endif; ?>

	<h4><?php esc_html_e( 'Developer settings', 'vendorhub-woocommerce' ); ?></h4>

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
						<?php esc_html_e( 'Default: https://www.myvendorhub.com. Change for self-hosted VendorHub or ngrok during development.', 'vendorhub-woocommerce' ); ?>
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
		<?php esc_html_e( 'Use the Save changes button at the bottom of this page to save the API base URL.', 'vendorhub-woocommerce' ); ?>
	</p>

	<?php if ( $supports_direct ) : ?>
		<h4><?php esc_html_e( 'Direct connect (development)', 'vendorhub-woocommerce' ); ?></h4>
		<p class="description">
			<?php esc_html_e( 'HMAC-signed registration using VENDORHUB_WC_CONNECT_SECRET in wp-config.php.', 'vendorhub-woocommerce' ); ?>
		</p>
		<p class="submit vendorhub-actions">
			<a
				href="<?php echo esc_url( VendorHub_Settings::admin_post_url( 'vendorhub_connect', 'vendorhub_connect' ) ); ?>"
				class="button"
			>
				<?php esc_html_e( 'Direct connect (dev)', 'vendorhub-woocommerce' ); ?>
			</a>
		</p>
	<?php endif; ?>

	<h4><?php esc_html_e( 'Privacy & external services', 'vendorhub-woocommerce' ); ?></h4>
	<p>
		<?php
		printf(
			/* translators: %s: VendorHub privacy policy URL */
			esc_html__(
				'Full privacy disclosure is available under Settings → Privacy. VendorHub privacy policy: %s',
				'vendorhub-woocommerce'
			),
			'<a href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $privacy_url ) . '</a>'
		);
		?>
	</p>
</details>

<style>
	.vendorhub-status { font-weight: 600; }
	.vendorhub-status--connected { color: #007017; }
	.vendorhub-status--disconnected { color: #b32d2e; }
	.vendorhub-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
	.vendorhub-onboarding-card {
		background: #fff;
		border: 1px solid #c3c4c7;
		border-left: 4px solid #2271b1;
		padding: 16px 20px;
		margin: 16px 0;
		max-width: 720px;
	}
	.vendorhub-disclosure-list { margin: 12px 0 20px; padding-left: 1.2em; list-style: disc; }
	.vendorhub-disclosure-list li { margin-bottom: 12px; }
	.vendorhub-disclosure-list li strong { display: block; margin-bottom: 2px; }
	.vendorhub-advanced { margin-top: 24px; max-width: 720px; }
	.vendorhub-advanced summary { cursor: pointer; font-weight: 600; padding: 8px 0; }
	.vendorhub-advanced[open] summary { margin-bottom: 12px; }
</style>
