# VendorHub ↔ WooCommerce Plugin Integration

This document mirrors the VendorHub backend contract for the **VendorHub for WooCommerce** WordPress plugin (`vendorhub-woocommerce`).

## 1. Registration / connect

### Onboarding (plugin UX)

1. **Activation** — Store admin with `manage_woocommerce` is redirected once to **WooCommerce → Settings → VendorHub** (`vendorhub_wc_show_onboarding` option).
2. **Permissions screen** — Native wp-admin disclosure checklist; merchant must accept before any external redirect.
3. **Post-connect** — Success notice with **Open VendorHub dashboard** link.

Plugin implementation: `VendorHub_Onboarding`, `admin/settings-page.php`.

### Redirect flow (production — legacy, Phase 1)

```
GET {VENDORHUB_BASE}/connect/woocommerce?siteUrl={url}&pluginToken={token}&returnUrl={url}&state={csrf_state}
```

- Backend validates `returnUrl` (same hostname as `siteUrl`, path includes `admin.php`).
- **Platform must echo `state` unchanged** on the return redirect. The plugin validates it when present; omitting `state` skips CSRF validation (legacy compat only — not recommended).
- After merchant login, backend redirects to:

```
{returnUrl}?vendorhub_store_id={storeId}&vendorhub_api_token={apiToken}&state={state}
```

- Plugin implementation: `VendorHub_Connect::get_redirect_url()`, `maybe_handle_redirect_return()`, `validate_connect_state()`.

### OAuth flow (Phase 2 — when platform ships)

```
GET {VENDORHUB_BASE}/oauth/authorize
  ?client_id={public_client_id}
  &redirect_uri={encoded_wp_callback_url}
  &response_type=code
  &state={csrf_state}
  &site_url={store_public_url}
  &plugin_token={per_site_plugin_token}
  &scope=orders:write callbacks:receive
  &code_challenge={pkce_s256}
  &code_challenge_method=S256

GET {wp_callback}?code={code}&state={state}
  → plugin validates state, exchanges code server-side

POST {VENDORHUB_BASE}/oauth/token
Body: { grant_type, code, redirect_uri, client_id, code_verifier }
Response: { storeId, apiToken }  (or access_token mapped per platform spec)
```

- Public `client_id` only — **no client_secret in the plugin** (WordPress.org requirement). Set via `VENDORHUB_WC_OAUTH_CLIENT_ID` in wp-config or the `vendorhub_wc_oauth_client_id` filter (evaluated at connect time via `VendorHub_Connect::get_oauth_client_id()`).
- OAuth callback: `admin-post.php?action=vendorhub_oauth_callback` (filterable via `vendorhub_wc_oauth_callback_url`).
- Feature-detect: non-empty `VendorHub_Connect::get_oauth_client_id()` + filter `vendorhub_wc_use_oauth_connect`.
- Plugin implementation: `VendorHub_Connect::get_oauth_authorize_url()`, `exchange_oauth_code()`, `VendorHub_Settings::handle_oauth_callback()`.
- Legacy redirect remains fallback when OAuth client ID is not configured.

### Merchant dashboard URL

```
{VENDORHUB_BASE}/stores/{storeId}
```

- Filterable path: `vendorhub_wc_dashboard_path` (default `stores/{store_id}`).
- Fallback when no store ID: `vendorhub_wc_dashboard_fallback_path` (default `dashboard`).
- Plugin implementation: `VendorHub_Connect::get_dashboard_url()`.

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

- **Not connected:** Permissions disclosure + accept checkbox + Connect CTA; Advanced section for manual credentials, API base URL, dev HMAC connect.
- **Connected:** Status, Open dashboard, Test connection, Disconnect; Advanced for developer settings.
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
- `docs/AGENT_PROMPT_WOOCOMMERCE_PLUGIN.md` — implementation roadmap
