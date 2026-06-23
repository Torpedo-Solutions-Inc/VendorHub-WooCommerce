# WordPress.org Submission Checklist — VendorHub for WooCommerce

Recommended plugin slug: **`vendorhub-woocommerce`** (alternate: `vendorhub-for-woocommerce`).

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

## Manual steps (Torpedo Solutions account)

1. **WordPress.org plugin account** — Register at https://wordpress.org/plugins/developers/add/ with Torpedo Solutions email.
2. **Plugin slug reservation** — Submit with slug `vendorhub-woocommerce`; wait for approval email.
3. **SVN checkout** (after approval):

```bash
svn co https://plugins.svn.wordpress.org/vendorhub-woocommerce
cd vendorhub-woocommerce
```

4. **Trunk** — Copy plugin source (not `.git`, `assets` for banners only in `/assets`):

```bash
# From plugin repo root
rsync -av --exclude .git --exclude assets --exclude '*.zip' ./ vendorhub-woocommerce/trunk/
```

5. **Assets** — Copy banner/icon PNGs to SVN `assets/`:

```bash
cp assets/* ../vendorhub-woocommerce/assets/
```

6. **Tag release** (match `readme.txt` Stable tag):

```bash
svn cp trunk tags/1.0.0
svn ci -m "Tag version 1.0.0"
```

7. **Plugin Check** — CI runs `wordpress/plugin-check-action` on PRs. Optionally re-run [Plugin Check](https://wordpress.org/plugins/plugin-check/) on a test site before SVN submit; document any warnings below.

8. **Screenshots** — Branded mockups committed in `assets/`. Replace with real admin captures before final org listing if desired.

9. **GlotPress** — After approval, contribute translations via https://translate.wordpress.org/

10. **VendorHub production** — Run `./scripts/e2e-verify.sh` (connect route probe). Confirm `https://www.myvendorhub.com/connect/woocommerce` is live before promoting org listing.

## PHPCS warnings (documented)

| Rule | Status | Notes |
| --- | --- | --- |
| `WordPress.Security.NonceVerification.Recommended` | Accepted | OAuth return query params validated server-side; merchant must be `manage_woocommerce`. |
| `WordPress.WP.Capabilities.Unknown` (`manage_woocommerce`) | Accepted | WooCommerce capability; not in core WP role map used by PHPCS. |
| Inline `<style>` in settings page | Accepted | Scoped admin-only styles; no frontend output. |

## Audit fixes (2025-06)

- [x] Redirect connect URL encoding — `add_query_arg()` only (no double `rawurlencode`)
- [x] Admin notice message encoding — same fix in `settings_url()`
- [x] Order sync race guard — `_vendorhub_syncing` meta lock
- [x] Direct connect HMAC — sign single payload object before appending `signature`
- [x] `customerEmail` in normalized order payload
- [x] `index.php` silencers in `includes/` and `admin/`
- [x] `docs/PLATFORM_INTEGRATION.md` in plugin repo

## Audit fixes (2026-06)

- [x] `load_plugin_textdomain()` + `languages/` directory
- [x] Plugin header `Tested up to: 6.7` aligned with readme
- [x] Direct connect accepts HTTP 200 or 201
- [x] Admin redirect notices use generic messages (details in WC logs only)
- [x] Line item `price` sent as unit price (line total ÷ quantity)
- [x] WordPress.org assets (icons, banners, screenshots)
- [x] Plugin Check GitHub Action + `scripts/e2e-verify.sh`
- [ ] Staging E2E: redirect connect → order ingest → callback verified on live/staging store

## Release workflow

```bash
git tag v1.0.0
git push origin v1.0.0
# GitHub Actions builds vendorhub-woocommerce-v1.0.0.zip
```

## Do not submit until

- [ ] Torpedo explicitly requests WordPress.org submission
- [ ] Staging E2E: redirect connect → order ingest → callback verified (`scripts/e2e-verify.sh` + TESTING.md)
- [ ] Plugin Check CI green on main (and optional local run with zero errors)

## Related docs

- Plugin: `docs/PLATFORM_INTEGRATION.md`, `TESTING.md`, `README.md`
