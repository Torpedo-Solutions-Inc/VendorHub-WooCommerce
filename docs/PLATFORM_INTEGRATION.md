# VendorHub ↔ WooCommerce Plugin Integration

This document mirrors the VendorHub backend contract for the **VendorHub for WooCommerce** WordPress plugin (`vendorhub-woocommerce`).

## 1. Registration / connect

### Redirect flow (production)

```
GET {VENDORHUB_BASE}/connect/woocommerce?siteUrl={url}&pluginToken={token}&returnUrl={url}
```

- Backend validates `returnUrl` (same hostname as `siteUrl`, path includes `admin.php`).
- After merchant login, backend redirects to:

```
{returnUrl}?vendorhub_store_id={storeId}&vendorhub_api_token={apiToken}
```

- Plugin implementation: `VendorHub_Connect::get_redirect_url()`, `maybe_handle_redirect_return()`.

### Manual token paste

Merchant copies **Store ID** + **API token** from VendorHub → Settings → API access.

### Direct HMAC connect (dev only)

```
POST {VENDORHUB_BASE}/api/connect/woocommerce
```

Body JSON (signature field appended after signing):

```json
{
  "siteUrl": "https://store.example.com",
  "displayName": "My Store",
  "pluginToken": "...",
  "timestamp": "1710000000000",
  "signature": "..."
}
```

- Signature: `HMAC-SHA256` hex of `{timestamp}.{rawRequestBody}` where `rawRequestBody` is the JSON **without** the `signature` field.
- Secret: `VENDORHUB_WC_CONNECT_SECRET` in wp-config only — **never** ship in the WordPress.org build.
- Plugin implementation: `VendorHub_Connect::connect()`, `VendorHub_HMAC::sign()`.

## 2. Order forwarding

- Hooks: `woocommerce_new_order`, `woocommerce_checkout_order_processed` (fallback).
- Endpoint:

```
POST {VENDORHUB_BASE}/api/stores/{storeId}/orders
Authorization: Bearer {apiToken}
```

Required `NormalizedOrder` fields:

| Field | WooCommerce source |
| --- | --- |
| `externalId` | Order ID |
| `orderNumber` | `get_order_number()` |
| `platform` | `"woocommerce"` |
| `createdAt` | ISO 8601 |
| `lineItems[]` | Order line items |

Also sent when available: `customerEmail`, `shippingAddress`, `currency`, `totalAmount`, line-item `vendor`/`sku`/`price` (unit price per quantity).

Idempotency: order meta `_vendorhub_synced` (in-flight lock: `_vendorhub_syncing`).

Plugin implementation: `VendorHub_Order_Sync`.

## 3. Callback REST route (Mode B)

When the merchant has **not** saved WooCommerce REST keys in the VendorHub dashboard:

```
POST {siteUrl}/wp-json/vendorhub/v1/order/{orderId}
```

Headers:

- `Authorization: Bearer {apiToken}`
- `X-VendorHub-Timestamp` — Unix ms
- `X-VendorHub-Signature` — `HMAC-SHA256` hex of `{timestamp}.{rawBody}` keyed by `apiToken` (±5 min skew)

Body (optional fields): `status`, `note`, `tracking`.

Plugin implementation: `VendorHub_REST`.

## 4. Settings UI

**WooCommerce → Settings → VendorHub**

- Connection status, redirect connect, manual credentials, disconnect, test connection.
- API base URL (default `https://www.myvendorhub.com`).
- No embedded VendorHub admin secrets in distributed builds.

## Platform requirements

| Component | Minimum |
| --- | --- |
| WordPress | 5.8 |
| PHP | 7.4 |
| WooCommerce | 6.0 (tested up to 9.6) |

HPOS: plugin declares `custom_order_tables` compatibility.

## Store ID format

Backend assigns `wc-{hostname}` (e.g. `wc-example.com`). Plugin accepts whatever the backend returns.

## Related plugin docs

- `TESTING.md` — manual E2E against staging/ngrok
- `WORDPRESS_ORG_CHECKLIST.md` — submission readiness
