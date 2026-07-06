# WordPress.org Submission Checklist — VendorHub for WooCommerce

Recommended plugin slug: **`vendorhub-for-woocommerce`** (assigned by WordPress.org — `woocommerce` cannot appear as a standalone slug segment).

## Pre-submission (automated / repo)

- [x] `phpcs.xml` with WordPress-Core + WordPress-Extra
- [x] GitHub Actions: PHPCS on PR, Plugin Check on PR (`plugin-check.yml`), release ZIP on tag
- [x] `readme.txt` complete (stable tag, WC tested up to, PHP, FAQ, screenshots list, changelog)
- [x] `assets/` — icon + banner PNGs committed; `screenshot-1.png` … `screenshot-4.png` (branded mockups — replace with real admin captures before final org listing if desired)
- [x] Privacy policy via `includes/class-vendorhub-privacy.php` + readme external services section
- [x] No embedded VendorHub connect secret (redirect + manual token only; `VENDORHUB_WC_CONNECT_SECRET` wp-config for dev)
- [x] Plugin works without connection (graceful onboarding + "Not connected" state)
- [x] Post-activation onboarding redirect with permissions disclosure (Phase 1 UX)
- [x] Dashboard URL documented in `docs/PLATFORM_INTEGRATION.md` (`vendorhub_wc_dashboard_path` filter)
- [x] OAuth client scaffolding (Phase 2 — `VendorHub_Connect::get_oauth_client_id()`, PKCE, callback handler)
- [x] GPL v2+ license in plugin header and readme
- [x] `docs/PLATFORM_INTEGRATION.md` documents backend contract in this repo
- [x] Plugin header `Tested up to: 7.0` aligned with readme
- [x] Plugin Check CI green on main
- [x] Local E2E verified (WP Studio — connect, order ingest, callback)

## WordPress.org account

- [x] WordPress.org account — [myvendorhub](https://profiles.wordpress.org/myvendorhub/) (torpedosolutionsinc@gmail.com)
- [x] Plugin submitted — slug **`vendorhub-for-woocommerce`** (July 6, 2026; awaiting review)
- [ ] SVN deploy after approval (steps below)

> **Note:** WordPress.com (`myvendorhub.wordpress.com`) is separate from WordPress.org plugin hosting. Plugin directory submission uses the **.org** account only.

## Submit now (initial review)

1. Build release ZIP: `.\scripts\build-zip.ps1` or tag `v1.1.0` (GitHub Actions builds the ZIP).
2. Go to https://wordpress.org/plugins/developers/add/
3. Upload `vendorhub-woocommerce.zip` (or the GitHub release artifact).
4. Plugin name: **VendorHub for WooCommerce**
5. Short description: connect WooCommerce to VendorHub for multi-vendor order routing and fulfillment updates.
6. Wait for approval email (typically 1–14 days).

## SVN deploy (after approval)

```bash
svn co https://plugins.svn.wordpress.org/vendorhub-for-woocommerce
cd vendorhub-for-woocommerce
```

**Trunk** — copy plugin source (exclude `.git`, repo `assets/`, `*.zip`):

```bash
# From plugin repo root
rsync -av --exclude .git --exclude assets --exclude '*.zip' ./ vendorhub-for-woocommerce/trunk/
```

**Assets** — org listing images only (not in the plugin ZIP):

```bash
cp assets/* ../vendorhub-for-woocommerce/assets/
```

**Tag release** (must match `readme.txt` Stable tag):

```bash
svn cp trunk tags/1.1.1
svn ci -m "Tag version 1.1.1"
```

## PHPCS warnings (documented)

| Rule | Status | Notes |
| --- | --- | --- |
| `WordPress.Security.NonceVerification.Recommended` | Accepted | OAuth return query params validated server-side; merchant must be `manage_woocommerce`. |
| `WordPress.WP.Capabilities.Unknown` (`manage_woocommerce`) | Accepted | WooCommerce capability; not in core WP role map used by PHPCS. |
| Inline `<style>` in settings page | Accepted | Scoped admin-only styles; no frontend output. |

## Post-approval (optional)

- [ ] Replace screenshot mockups with real wp-admin captures in SVN `assets/`
- [ ] GlotPress translations — https://translate.wordpress.org/
- [ ] OAuth connect (Phase 2) when VendorHub platform ships `/oauth/authorize` + `/oauth/token`

## Release workflow

```bash
git tag v1.1.0
git push origin v1.1.0
# GitHub Actions builds vendorhub-woocommerce-v1.1.0.zip
```

Or trigger manually: **Actions → Release → Run workflow**.

## Related docs

- Plugin: `docs/PLATFORM_INTEGRATION.md`, `TESTING.md`, `README.md`
