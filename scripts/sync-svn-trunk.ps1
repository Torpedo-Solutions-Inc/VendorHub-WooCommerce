# Copy plugin ship files from the Git repo into the WordPress.org SVN trunk (and optional assets).
param(
	[switch]$SyncAssets
)

$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'wordpress-org-config.ps1')

$config = $script:WordPressOrgConfig
$sourceRoot = $config.SourceRoot
$svnRoot = $config.SvnRoot
$trunkRoot = Join-Path $svnRoot 'trunk'
$assetsRoot = Join-Path $svnRoot 'assets'

if (-not (Test-Path (Join-Path $svnRoot '.svn'))) {
	throw "SVN working copy not found at $svnRoot. Run SVN checkout first."
}

if (-not (Test-Path $trunkRoot)) {
	New-Item -ItemType Directory -Path $trunkRoot | Out-Null
}

foreach ($relativePath in $config.TrunkPaths) {
	$sourcePath = Join-Path $sourceRoot $relativePath
	$destPath = Join-Path $trunkRoot $relativePath

	if (-not (Test-Path $sourcePath)) {
		throw "Required source path missing: $sourcePath"
	}

	if (Test-Path $sourcePath -PathType Leaf) {
		$destDir = Split-Path -Parent $destPath
		if ($destDir -and -not (Test-Path $destDir)) {
			New-Item -ItemType Directory -Path $destDir -Force | Out-Null
		}
		Copy-Item -Path $sourcePath -Destination $destPath -Force
		Write-Host "Copied file: $relativePath"
		continue
	}

	if (-not (Test-Path $destPath)) {
		New-Item -ItemType Directory -Path $destPath -Force | Out-Null
	}

	& robocopy $sourcePath $destPath /MIR /NFL /NDL /NJH /NJS /NC /NS | Out-Null
	if ($LASTEXITCODE -ge 8) {
		throw "robocopy failed for $relativePath with exit code $LASTEXITCODE"
	}
	Write-Host "Mirrored directory: $relativePath"
}

if ($SyncAssets) {
	$sourceAssets = Join-Path $sourceRoot 'assets'
	if (-not (Test-Path $sourceAssets)) {
		throw "Source assets folder not found: $sourceAssets"
	}
	if (-not (Test-Path $assetsRoot)) {
		New-Item -ItemType Directory -Path $assetsRoot -Force | Out-Null
	}

	foreach ($pattern in $config.AssetPatterns) {
		Get-ChildItem -Path $sourceAssets -Filter $pattern -File | ForEach-Object {
			Copy-Item -Path $_.FullName -Destination (Join-Path $assetsRoot $_.Name) -Force
			Write-Host "Copied asset: $($_.Name)"
		}
	}
}

Write-Host "SVN sync complete."
Write-Host "  Source: $sourceRoot"
Write-Host "  Trunk:  $trunkRoot"
if ($SyncAssets) {
	Write-Host "  Assets: $assetsRoot"
}
