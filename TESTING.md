# Manual testing — VendorHub WooCommerce Plugin



Test the plugin against a local VendorHub instance exposed via ngrok.



## Prerequisites



- WordPress + WooCommerce (local or staging)

- VendorHub repo running locally (`npm run dev`)

- [ngrok](https://ngrok.com/) for **both** WordPress and VendorHub if they are on different networks



## Connect flow options



| Flow | VendorHub env | Plugin setup |

| --- | --- | --- |

| **Redirect** (recommended) | No connect secret required | Click **Connect to VendorHub** |

| **Manual token** | Store exists in VendorHub | Paste Store ID + API token from VH Settings |

| **Direct HMAC** (dev) | `VENDORHUB_WC_CONNECT_SECRET` in `.env` | `define('VENDORHUB_WC_CONNECT_SECRET', '…')` in wp-config |



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



## 3. Redirect connect (production-like)



1. **WooCommerce → Settings → VendorHub**

2. **API base URL:** your VendorHub URL (ngrok or staging)

3. Click **Connect to VendorHub**

4. Sign in on VendorHub `/connect/woocommerce`

5. Confirm connect — browser returns to WP settings with credentials saved

6. Click **Test connection**



## 4. Manual token paste



1. In VendorHub dashboard, open **Settings → API access**

2. Copy **Store ID** and **API token**

3. In WordPress, paste under **Manual connection** → **Save credentials**



## 5. Direct connect (self-hosted dev)



1. Set `VENDORHUB_WC_CONNECT_SECRET` in VendorHub `.env`

2. Add matching constant in `wp-config.php`

3. Click **Direct connect (dev)** when the button appears



## 6. Forward a test order



1. Create a WC product with vendor meta `_vendor` = `Test Vendor` (optional)

2. Place a test order

3. Check **WooCommerce → Status → Logs → vendorhub**

4. Confirm order in VendorHub dashboard



## 7. Callback — vendor accept → WC note

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



## Acceptance checklist



- [ ] Redirect connect saves `storeId` + `apiToken`

- [ ] Manual token paste works

- [ ] New WC order ingested in VendorHub

- [ ] Vendor response adds note on WC order

- [ ] Invalid callback signature rejected

- [ ] Plugin shows graceful "Not connected" without credentials

