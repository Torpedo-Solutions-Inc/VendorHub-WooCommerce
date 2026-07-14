# Sync plugin files, then open TortoiseSVN commit and tag dialogs for a WordPress.org release.
param(
	[Parameter(Mandatory = $true)]
	[string]$Message,

	[switch]$SyncAssets,
	[switch]$SkipSync,
	[switch]$SkipCommit,
	[switch]$SkipTag
)

$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'wordpress-org-config.ps1')

$config = $script:WordPressOrgConfig
$svnRoot = $config.SvnRoot
$trunkRoot = Join-Path $svnRoot 'trunk'
$version = Get-PluginVersion -SourceRoot $config.SourceRoot

if (-not $config.TortoiseProc -or -not (Test-Path $config.TortoiseProc)) {
	throw 'TortoiseProc.exe not found. Install TortoiseSVN or set TORTOISEPROC_PATH.'
}

if (-not (Test-Path (Join-Path $svnRoot '.svn'))) {
	throw "SVN working copy not found at $svnRoot"
}

if (-not $SkipSync) {
	& (Join-Path $PSScriptRoot 'sync-svn-trunk.ps1') @(
		if ($SyncAssets) { '-SyncAssets' }
	)
}

$tagMessage = "Tag version $version"
$tagUrl = "^/tags/$version"

Write-Host "Preparing WordPress.org release $version"
Write-Host "  SVN root: $svnRoot"
Write-Host "  Commit message: $Message"
Write-Host "  Tag URL: $($config.SvnUrl)/tags/$version"

if (-not $SkipCommit) {
	Write-Host ''
	Write-Host 'Opening TortoiseSVN commit dialog...'
	Write-Host 'Review changed files, then click OK to commit.'
	& $config.TortoiseProc /command:commit /path:"$svnRoot" /logmsg:"$Message" /closeonend:1
}

if (-not $SkipTag) {
	Write-Host ''
	Write-Host 'Opening TortoiseSVN tag dialog...'
	Write-Host "Confirm tag destination: tags/$version (Working copy, do not switch)."
	& $config.TortoiseProc /command:copy /path:"$trunkRoot" /url:"$tagUrl" /logmsg:"$tagMessage" /closeonend:1
}

Write-Host ''
Write-Host "Release workflow finished for version $version."
Write-Host "Verify: https://wordpress.org/plugins/myvendorhub-for-woocommerce/"
