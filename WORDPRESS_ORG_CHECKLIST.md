# WordPress.org Submission Checklist — VendorHub for WooCommerce

Recommended plugin slug: **`vendorhub-woocommerce`** (alternate: `vendorhub-for-woocommerce`).

## Pre-submission (automated / repo)

- [x] `phpcs.xml` with WordPress-Core + WordPress-Extra
- [x] GitHub Actions: PHPCS on PR, release ZIP on tag
- [x] `readme.txt` complete (stable tag, WC tested up to, PHP, FAQ, screenshots list, changelog)
- [x] `assets/` folder with icon and banner placeholders
- [x] Privacy policy via `includes/class-vendorhub-privacy.php` + readme external services section
- [x] No embedded VendorHub connect secret (redirect + manual token only; `VENDORHUB_WC_CONNECT_SECRET` wp-config for dev)
- [x] Plugin works without connection (graceful "Not connected" state)
- [x] GPL v2+ license in plugin header and readme
- [x] VendorHub `docs/PLATFORM_INTEGRATION.md` synced

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

7. **Plugin Check** — Install [Plugin Check](https://wordpress.org/plugins/plugin-check/) on a test site; run against trunk ZIP. Document any warnings in this file.

8. **Screenshots** — Replace readme placeholders with real PNGs in `assets/` (screenshot-1.png … screenshot-4.png) after UI is final.

9. **GlotPress** — After approval, contribute translations via https://translate.wordpress.org/

10. **VendorHub production** — Ensure `https://api.vendorhub.app/connect/woocommerce` redirect flow is live before promoting org listing.

## PHPCS warnings (documented)

| Rule | Status | Notes |
| --- | --- | --- |
| `WordPress.Security.NonceVerification.Recommended` | Accepted | OAuth return query params validated server-side; merchant must be `manage_woocommerce`. |
| Inline `<style>` in settings page | Accepted | Scoped admin-only styles; no frontend output. |

## Release workflow

```bash
git tag v1.0.0
git push origin v1.0.0
# GitHub Actions builds vendorhub-woocommerce-v1.0.0.zip
```

## Do not submit until

- [ ] Torpedo explicitly requests WordPress.org submission
- [ ] Staging E2E: redirect connect → order ingest → callback verified
- [ ] Final screenshot assets captured
- [ ] Plugin Check run with zero errors on test site

## Related docs

- VendorHub: `docs/PLATFORM_INTEGRATION.md`
- Plugin: `TESTING.md`, `README.md`
