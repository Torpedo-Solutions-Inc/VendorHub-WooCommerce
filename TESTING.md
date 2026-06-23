# Manual testing — VendorHub WooCommerce Plugin

Test the plugin against a local VendorHub instance exposed via ngrok.

## Prerequisites

- WordPress + WooCommerce (local or staging)
- VendorHub repo running locally (`npm run dev`)
- [ngrok](https://ngrok.com/) for **both** WordPress and VendorHub if they are on different networks

## Connect flow options

| Flow | VendorHub env | Plugin setup |
| --- | --- | --- |
| **Redirect** (recommended) | No connect secret required | Accept permissions → **Connect to VendorHub** |
| **Manual token** | Store exists in VendorHub | Advanced → paste Store ID + API token |
| **Direct HMAC** (dev) | `VENDORHUB_WC_CONNECT_SECRET` in `.env` | Advanced → **Direct connect (dev)** |
| **OAuth** (Phase 2) | `/oauth/authorize` + `/oauth/token` live | `vendorhub_wc_oauth_client_id` filter or `VENDORHUB_WC_OAUTH_CLIENT_ID` in wp-config |

## 1. Start VendorHub

```bash
cd VendorHub
npm run dev
```

Expose via ngrok if needed:

```bash
ngrok http 3000
```

## 2. Install the plugin

Build ZIP and upload via **WordPress → Plugins → Add New → Upload**.

## 3. Onboarding (fresh install)

1. Activate **VendorHub for WooCommerce**
2. Confirm one-time redirect to **WooCommerce → Settings → VendorHub**
3. Review permissions checklist (data sent, not sent, callbacks, privacy link)
4. Check **I have reviewed the permissions…** — Connect button should require this
5. Click **Connect to VendorHub** without checking the box — expect permissions error notice

## 4. Redirect connect (production-like)

1. **WooCommerce → Settings → VendorHub**
2. **Advanced → API base URL:** your VendorHub URL (ngrok or staging)
3. Accept permissions → click **Connect to VendorHub**
4. Sign in on VendorHub `/connect/woocommerce`
5. Confirm connect — browser returns to WP settings with credentials saved
6. Confirm success notice includes **Open VendorHub dashboard**
7. Click **Test connection**

## 5. Manual token paste

1. In VendorHub dashboard, open **Settings → API access**
2. Copy **Store ID** and **API token**
3. In WordPress, open **Advanced → Manual connection** → **Save credentials**

## 6. Direct connect (self-hosted dev)

1. Set `VENDORHUB_WC_CONNECT_SECRET` in VendorHub `.env`
2. Add matching constant in `wp-config.php`
3. **Advanced → Direct connect (dev)** when the button appears

## 7. Disconnect and reconnect

1. Click **Disconnect** — status shows Not connected, onboarding permissions screen returns
2. Reconnect via redirect or manual token

## 8. Capability check

Log in as a user **without** `manage_woocommerce` — confirm connect admin-post URLs return Unauthorized.

## 9. Forward a test order

1. Create a WC product with vendor meta `_vendor` = `Test Vendor` (optional)
2. Place a test order
3. Check **WooCommerce → Status → Logs → vendorhub**
4. Confirm order in VendorHub dashboard

## 10. Callback — vendor accept → WC note

VendorHub calls `POST {siteUrl}/wp-json/vendorhub/v1/order/{orderId}` with Bearer + HMAC headers.

Example (replace values from your connected store):

```bash
ORDER_ID=123
API_TOKEN="your-api-token"
BODY='{"note":"Vendor accepted the order"}'
TIMESTAMP=$(date +%s000)
SIGNATURE=$(printf '%s.%s' "$TIMESTAMP" "$BODY" | openssl dgst -sha256 -hmac "$API_TOKEN" | awk '{print $2}')

curl -X POST "https://your-store.example/wp-json/vendorhub/v1/order/${ORDER_ID}" \
  -H "Authorization: Bearer ${API_TOKEN}" \
  -H "Content-Type: application/json" \
  -H "X-VendorHub-Timestamp: ${TIMESTAMP}" \
  -H "X-VendorHub-Signature: ${SIGNATURE}" \
  -d "$BODY"
```

Expect HTTP 200 and a new order note in WooCommerce. Replay with a bad signature — expect HTTP 401.

Or run the helper script (after connect + test order):

```bash
SITE_URL=https://your-store.example API_TOKEN=your-token ORDER_ID=123 ./scripts/e2e-verify.sh
```

The script also probes `GET {API_BASE}/connect/woocommerce` reachability.

## 11. OAuth connect (Phase 2 — when platform ready)

1. Platform staging must expose `/oauth/authorize` and `/oauth/token`
2. Register public OAuth client ID with VendorHub
3. In WordPress, enable via filter:

```php
add_filter( 'vendorhub_wc_oauth_client_id', fn() => 'your-public-client-id' );
```

4. Accept permissions → Connect — should redirect to `/oauth/authorize` (not legacy `/connect/woocommerce`)
5. After consent, callback hits `admin-post.php?action=vendorhub_oauth_callback`
6. Confirm credentials saved without tokens in browser query string

## Acceptance checklist

- [ ] Fresh install → onboarding redirect → permissions before external redirect
- [ ] Redirect connect saves `storeId` + `apiToken`
- [ ] Connected state shows dashboard link
- [ ] Manual token paste works (Advanced)
- [ ] Direct HMAC connect works (Advanced, dev only)
- [ ] New WC order ingested in VendorHub
- [ ] Vendor response adds note on WC order
- [ ] Invalid callback signature rejected
- [ ] Plugin shows graceful onboarding when not connected
- [ ] User without `manage_woocommerce` cannot connect
- [ ] OAuth connect (Phase 2) when platform ships
