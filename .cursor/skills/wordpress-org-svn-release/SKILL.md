---
name: wordpress-org-svn-release
description: Syncs MyVendorHub for WooCommerce plugin files to the WordPress.org SVN working copy, commits with a message, and tags trunk for release. Use when publishing to WordPress.org, running an SVN release, committing to vendorhub-for-woocommerce-svn, or tagging a plugin version.
---

# WordPress.org SVN release

Publish **MyVendorHub for WooCommerce** to WordPress.org from the Git dev repo.

## Paths

| Item | Value |
|------|-------|
| Dev repo | `vendorhub-woocommerce` (this repo) |
| SVN working copy | `D:\Dev\Torpedo Solutions Inc\vendorhub-for-woocommerce-svn` |
| SVN URL | `https://plugins.svn.wordpress.org/myvendorhub-for-woocommerce` |
| SVN username | `myvendorhub` (lowercase, case-sensitive) |
| Public page | https://wordpress.org/plugins/myvendorhub-for-woocommerce/ |

## Pre-release checks

1. `vendorhub.php` `Version:` equals `readme.txt` `Stable tag:`.
2. Changelog in `readme.txt` includes the release version.
3. Run from repo root:

```powershell
.\scripts\sync-svn-trunk.ps1
```

Add `-SyncAssets` if `assets/*.png` changed.

## Release (commit + tag)

Run from repo root. TortoiseSVN dialogs open for user confirmation.

```powershell
.\scripts\release-wordpress-org.ps1 -Message "Release 1.1.2"
```

With listing image updates:

```powershell
.\scripts\release-wordpress-org.ps1 -Message "Release 1.1.2" -SyncAssets
```

The script will:

1. Sync ship files into `trunk/` (and optionally `assets/`).
2. Open **TortoiseSVN Commit** on the SVN root with the message prefilled.
3. Open **TortoiseSVN Branch/tag** from `trunk` to `tags/{version}` with log message `Tag version {version}`.

### User actions in TortoiseSVN dialogs

**Commit dialog:** Review files under `trunk/` and `assets/`, then click OK.

**Tag dialog:** Confirm:
- **To path:** `myvendorhub-for-woocommerce/tags/{version}`
- **Working copy** selected (not a specific old revision)
- **Switch working copy** unchecked
- Log message present (script prefills it)

If credentials fail, clear saved auth in TortoiseSVN Settings and re-enter username `myvendorhub`.

## Commit or tag only

```powershell
# Sync only
.\scripts\sync-svn-trunk.ps1

# Commit without tagging (trunk/assets changes, no new version)
.\scripts\release-wordpress-org.ps1 -Message "Update readme" -SkipTag

# Tag only (after trunk already committed)
.\scripts\release-wordpress-org.ps1 -Message "n/a" -SkipSync -SkipCommit
```

## New version workflow

1. Bump `Version:` in `vendorhub.php` and `Stable tag:` in `readme.txt`.
2. Add changelog section in `readme.txt`.
3. Make code changes in this Git repo.
4. Run `.\scripts\release-wordpress-org.ps1 -Message "Release X.Y.Z"`.
5. Verify the public plugin page and test install from WordPress.org.

## Troubleshooting

| Error | Fix |
|-------|-----|
| `user 'MyVendorHub' cannot modify` | Username must be `myvendorhub` (all lowercase) |
| `Please provide commit message` | Never leave log message empty |
| `URL doesn't exist` | Use slug `myvendorhub-for-woocommerce`, not `vendorhub-for-woocommerce` |
| Tag before commit | Commit `trunk` first, then tag from **Working copy** |
