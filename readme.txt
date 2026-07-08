=== VendorHub for WooCommerce ===

Contributors: myvendorhub

Tags: woocommerce, vendor, dropshipping, fulfillment, orders

Requires at least: 5.8

Tested up to: 7.0

Requires PHP: 7.4

Stable tag: 1.1.1

License: GPLv2 or later

License URI: https://www.gnu.org/licenses/gpl-2.0.html

WC requires at least: 6.0

WC tested up to: 9.6



Connect WooCommerce to VendorHub for multi-vendor order routing and fulfillment updates.



== Description ==



VendorHub for WooCommerce forwards new orders to [VendorHub](https://www.myvendorhub.com) and receives vendor responses (status, notes, tracking) back on your store.



**Features:**



* Redirect connect to VendorHub (no embedded secrets — WordPress.org ready)

* Open VendorHub from wp-admin after connect (signed SSO launch — no re-login)

* Permissions disclosure onboarding before external redirect

* Manual Store ID + API token paste (Advanced)

* Automatic order forwarding on checkout

* Secure REST callback for VendorHub outbound updates

* HMAC signature verification on all callbacks

* WooCommerce → Settings → VendorHub admin page

* HPOS (High-Performance Order Storage) compatible



Merchants manage vendors, rules, and responses in the **VendorHub web dashboard** — not inside WordPress.



= External services =



This plugin connects to VendorHub API hosts over HTTPS (default: `https://www.myvendorhub.com`). Data transmitted:



* **Connect** — site URL, store display name, per-site plugin token

* **Order sync** — order number, line items, SKUs, vendor names, shipping address, customer email

* **Inbound callbacks** — VendorHub POSTs status, notes, and tracking to your WordPress site



No VendorHub admin passwords are stored. See [VendorHub Privacy Policy](https://www.myvendorhub.com/privacy).



== Installation ==



1. Install from **Plugins → Add New** and search for "VendorHub for WooCommerce", **or** upload the plugin ZIP.

2. Activate **VendorHub for WooCommerce** (requires WooCommerce 6.0+).

3. You are redirected once to **WooCommerce → Settings → VendorHub** to review permissions.

4. Check the permissions disclosure, then click **Connect to VendorHub** and sign in on VendorHub (redirect flow), **or** use **Advanced → Manual connection** to paste Store ID + API token from VendorHub → Settings.

5. After connecting, click **Open VendorHub** on the settings page to return to your dashboard without signing in again, and place a test order — it should appear in VendorHub within seconds.

Integrators: see `docs/PLATFORM_INTEGRATION.md` → **SSO launch (return visits — plugin v2+)** for the signing contract.



== Connect flows ==



**Redirect flow (recommended — WordPress.org / SaaS)**



No VendorHub secrets are embedded in the plugin. Review the permissions checklist, accept the disclosure, then click **Connect to VendorHub** to open VendorHub with your `siteUrl`, a per-site `pluginToken`, and a CSRF `state` parameter. After you sign in, VendorHub returns `storeId` and `apiToken` to this settings page.



**Manual token paste**



Create or open your WooCommerce store in VendorHub, then copy **Store ID** and **API token** from VendorHub → Settings → API access. Paste them under **Advanced → Manual connection** in WooCommerce → Settings → VendorHub.



**Direct connect (self-hosted development only)**



Define `VENDORHUB_WC_CONNECT_SECRET` in `wp-config.php` matching your VendorHub server env. A **Direct connect (dev)** button appears for HMAC-signed registration.



== Frequently Asked Questions ==



= Where do I manage vendors? =



In the VendorHub web dashboard at https://www.myvendorhub.com (or your self-hosted URL). This plugin only forwards orders and applies vendor responses.



= Does this plugin require a VendorHub account? =



Yes, to forward orders. Without credentials the plugin shows "Not connected" and does not transmit data.



= Are WooCommerce REST API keys required? =



No. REST keys are optional and configured in the VendorHub web dashboard for advanced dashboard reads. Order ingest works through this plugin alone.



= Does this plugin store VendorHub admin passwords? =



No. Only per-store `storeId` and `apiToken` from the connect handshake are stored locally.



= What WooCommerce vendor plugins are supported? =



Line-item vendor is read from product/line meta keys: `_vendor`, `vendor`, `_wcv_vendor`, `_dokan_vendor_id`.



= What personal data is sent to VendorHub? =



Order and fulfillment data including customer name, shipping address, and phone when present on the order. VendorHub does not sell personal data. See https://www.myvendorhub.com/privacy.



== Screenshots ==



1. WooCommerce → Settings → VendorHub onboarding (permissions disclosure)

2. Connected state with Open VendorHub (SSO launch)

3. Advanced manual Store ID and API token entry

4. Order note applied after vendor accept in VendorHub



== Changelog ==



= 1.1.1 =

* Align text domain with WordPress.org slug (`vendorhub-for-woocommerce`).
* Fix Plugin URI / Author URI headers for directory submission.



= 1.1.0 =

* SSO launch — Open VendorHub from wp-admin without re-login.
* Configurable vendor meta key for multi-vendor plugins.
* Onboarding UX and permissions disclosure improvements.
* WordPress 7.0 compatibility.
* PHPCS, Plugin Check CI, and E2E verification script.



= 1.0.0 =

* Initial release: redirect connect, manual token paste, order ingest, REST callback, settings UI, privacy disclosures, WordPress.org readiness (Phase 7F).



== Upgrade Notice ==



= 1.1.1 =

Fixes WordPress.org submission headers and text domain alignment.



= 1.1.0 =

Adds SSO launch, vendor meta configuration, and WordPress 7.0 support.



= 1.0.0 =

Initial public release for WordPress.org submission.
