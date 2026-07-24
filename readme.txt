=== MyVendorHub for WooCommerce ===

Contributors: myvendorhub
Tags: woocommerce, dropshipping, fulfillment, vendors, suppliers
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automate supplier notifications, Accept/Reject tracking, and multi-vendor fulfillment for your WooCommerce store.

== Description ==

**Selling products from suppliers?** [MyVendorHub](https://www.myvendorhub.com) organizes your inventory, pricing, and orders — automatically.

This free plugin connects your WooCommerce store to MyVendorHub. When a customer orders a supplier’s products, MyVendorHub notifies the right vendor by email or WhatsApp, tracks their Accept / Reject / Other response, and writes status updates back to your order notes — so nothing falls through the cracks.

Less spreadsheets. Less WhatsApp chaos. Fewer mistakes. More control.

= What MyVendorHub does for your store =

* **Automatic supplier notifications** — New orders are routed to the right vendor with product details, quantities, and shipping address
* **Email & WhatsApp** — Reach vendors on the channels they already use (WhatsApp on Growth+ plans)
* **One-click Accept / Reject / Other** — Vendors respond from a secure link; you get alerts when something needs attention
* **Smart reminders** — Configurable follow-ups for vendors who haven’t replied, optionally limited to working hours
* **Supplier dashboard** — Orders grouped by vendor with revenue, product details, and fulfillment status at a glance
* **Vendor sync from your catalog** — Pull vendor names from product meta and manage contacts in one place
* **Product Scanner** — Track supplier product page URLs for price, availability, and catalog changes
* **Event Hub** — Activity feed for notifications sent, vendor responses, reminders, and failures
* **Multi-language messaging** — Notify vendors in 12 languages (including Hebrew and Arabic)
* **Multi-store ready** — Manage multiple WooCommerce stores from one MyVendorHub account
* **HPOS compatible** — Works with WooCommerce High-Performance Order Storage

Merchants manage vendors, reminders, scanner, billing, and responses in the **[MyVendorHub web dashboard](https://www.myvendorhub.com)** — this plugin keeps your store connected securely.

**Free Starter plan** available after connect (limits apply). Paid plans unlock more vendors, reminders, WhatsApp, and higher volume. See [pricing on myvendorhub.com](https://www.myvendorhub.com).

= How it works =

1. Install and activate this plugin (WooCommerce 6.0+ required).
2. Connect your store to MyVendorHub in **WooCommerce → Settings → MyVendorHub**.
3. Add vendor contacts and enable notifications in the MyVendorHub dashboard.
4. Place a test order — vendors are notified within seconds, and responses appear on the order in WooCommerce.

= Connect your store =

**Recommended: one-click Connect**

Review the permissions checklist on the settings page, then click **Connect to MyVendorHub**. Sign in (or create an account), and your store credentials are returned securely — no secrets are embedded in the plugin.

After connecting, use **Open MyVendorHub** in wp-admin to return to your dashboard without signing in again (signed SSO launch).

**Manual connection (Advanced)**

Already have a store in MyVendorHub? Copy **Store ID** and **API token** from MyVendorHub → Settings → API access, then paste them under **Advanced → Manual connection** in WooCommerce → Settings → MyVendorHub.

**Developers (self-hosted only)**

For local / self-hosted MyVendorHub environments, define `VENDORHUB_WC_CONNECT_SECRET` in `wp-config.php` to enable a **Direct connect (dev)** HMAC registration button. Not used for the public SaaS.

= External services =

This plugin connects your store to **MyVendorHub** cloud services over HTTPS (default host: `https://www.myvendorhub.com`). A MyVendorHub account is required to forward orders.

Data that may be transmitted after you connect:

* **Connect** — your site URL, store display name, and a per-site plugin token used to complete the handshake
* **Order sync** — order number, line items, SKUs, vendor names, shipping address, and customer contact details needed for fulfillment
* **Inbound updates** — MyVendorHub securely POSTs vendor status, notes, and tracking back to your WordPress site (HMAC-verified)

No MyVendorHub admin passwords are stored in WordPress. Only the per-store `storeId` and `apiToken` from connect are saved locally.

Privacy policy: [https://www.myvendorhub.com/privacy](https://www.myvendorhub.com/privacy)

Learn more or try the live demo: [https://www.myvendorhub.com](https://www.myvendorhub.com)

== Installation ==

1. In WordPress, go to **Plugins → Add New**, search for **MyVendorHub for WooCommerce**, and click **Install Now** — or upload the plugin ZIP.
2. Activate the plugin (requires **WooCommerce 6.0+**).
3. You are guided to **WooCommerce → Settings → MyVendorHub** to review permissions.
4. Click **Connect to MyVendorHub** and sign in, **or** paste Store ID + API token under **Advanced → Manual connection**.
5. In the MyVendorHub dashboard, set your admin email, sync or add vendors, and enable notifications.
6. Place a test order — it should appear in MyVendorHub within seconds. Vendor responses write back to WooCommerce order notes.

== Frequently Asked Questions ==

= What is MyVendorHub? =

MyVendorHub is a vendor order management platform for online stores. It automatically notifies suppliers when their products are ordered, tracks Accept / Reject / Other responses, sends reminders, and gives you a dashboard of vendor performance — so you spend less time on manual emails and WhatsApp threads.

= Do I need a MyVendorHub account? =

Yes. Without a connection the plugin shows “Not connected” and does not transmit order data. A free Starter plan is available after you connect.

= Where do I manage vendors and settings? =

In the MyVendorHub web dashboard at [https://www.myvendorhub.com](https://www.myvendorhub.com). This plugin connects your WooCommerce store; day-to-day vendor management happens in MyVendorHub. After connect, use **Open MyVendorHub** from the plugin settings for one-click return (no re-login).

= Are WooCommerce REST API keys required? =

No. Order forwarding works through this plugin alone. Optional WooCommerce REST keys can be added later in MyVendorHub for richer dashboard reads and advanced features.

= How do vendors get notified? =

When an order includes a vendor’s products, MyVendorHub emails them (and optionally WhatsApp on eligible plans) with order details and one-click Accept / Reject / Other buttons. You configure contacts and notification channels in the Vendors list inside MyVendorHub.

= What if a vendor doesn’t respond? =

Enable smart reminders in MyVendorHub Settings. You can schedule up to three follow-ups and optionally restrict them to working hours so vendors aren’t contacted at night.

= Does this work with Dokan, WC Vendors, or similar plugins? =

Vendor names are read from common product / line-item meta keys (`_vendor`, `vendor`, `_wcv_vendor`, `_dokan_vendor_id`). You can also set a custom vendor meta key in MyVendorHub Settings.

= Does the plugin store MyVendorHub passwords? =

No. Only the per-store `storeId` and `apiToken` from the connect handshake are stored in WordPress options.

= What personal data is sent to MyVendorHub? =

Order and fulfillment data needed for vendors to ship — including customer name, shipping address, and phone when present on the order. MyVendorHub does not sell personal data. See [https://www.myvendorhub.com/privacy](https://www.myvendorhub.com/privacy).

= Is it compatible with HPOS? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage.

== Screenshots ==

1. WooCommerce → Settings → MyVendorHub onboarding (permissions disclosure)
2. Connected state with Open MyVendorHub (SSO launch)
3. Advanced manual Store ID and API token entry
4. Order note applied after vendor accept in MyVendorHub

== Changelog ==

= 1.1.2 =

* Rename plugin to **MyVendorHub for WooCommerce** (distinctive branding for WordPress.org).
* Require valid connect `state` on redirect return — reject missing or invalid CSRF state before saving credentials.

= 1.1.1 =

* Align text domain with WordPress.org slug (`vendorhub-for-woocommerce`).
* Fix Plugin URI / Author URI headers for directory submission.

= 1.1.0 =

* SSO launch — Open MyVendorHub from wp-admin without re-login.
* Configurable vendor meta key for multi-vendor plugins.
* Onboarding UX and permissions disclosure improvements.
* WordPress 7.0 compatibility.
* PHPCS, Plugin Check CI, and E2E verification script.

= 1.0.0 =

* Initial release: redirect connect, manual token paste, order ingest, REST callback, settings UI, privacy disclosures, WordPress.org readiness (Phase 7F).

== Upgrade Notice ==

= 1.1.2 =

Security and branding update for WordPress.org review.

= 1.1.1 =

= 1.1.0 =

Adds SSO launch, vendor meta configuration, and WordPress 7.0 support.

= 1.0.0 =

Initial public release for WordPress.org submission.
