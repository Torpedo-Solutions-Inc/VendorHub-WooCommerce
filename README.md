# VendorHub for WooCommerce

WordPress plugin that connects WooCommerce stores to [VendorHub](https://vendorhub.app).

**Recommended WordPress.org slug:** `vendorhub-woocommerce`

## Requirements

| Component | Minimum |
| --- | --- |
| WordPress | 5.8 |
| PHP | 7.4 |
| WooCommerce | 6.0 |

## Connect approaches

| Flow | When | Secret in plugin? |
| --- | --- | --- |
| **Redirect** | WordPress.org / SaaS | No — opens `{apiBase}/connect/woocommerce?siteUrl=…` |
| **Manual token** | Offline / org review v1 | No — paste Store ID + API token from VendorHub Settings |
| **Direct** | Self-hosted / ngrok dev | `VENDORHUB_WC_CONNECT_SECRET` in wp-config only |

See `TESTING.md` for ngrok end-to-end steps, `docs/PLATFORM_INTEGRATION.md` for the backend contract, and `WORDPRESS_ORG_CHECKLIST.md` for submission readiness.

## Build ZIP

**PowerShell (Windows):**

```powershell
Compress-Archive -Path vendorhub.php, readme.txt, uninstall.php, includes, admin -DestinationPath vendorhub-woocommerce.zip -Force
```

**Bash (macOS/Linux):**

```bash
zip -r vendorhub-woocommerce.zip vendorhub.php readme.txt uninstall.php includes admin -x "*.DS_Store"
```

Or tag a release — GitHub Actions builds `vendorhub-woocommerce-v{version}.zip`.

## PHPCS

```bash
composer install
composer run phpcs
```

## License

GPL v2 or later.
