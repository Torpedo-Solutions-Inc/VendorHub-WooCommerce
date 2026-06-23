# Build a WordPress-compatible plugin ZIP (forward-slash paths only).
# PowerShell Compress-Archive uses backslashes and breaks WP plugin upload.
param(
	[string]$Output = "vendorhub-woocommerce.zip"
)

$ErrorActionPreference = "Stop"

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$paths = @(
	"vendorhub.php",
	"readme.txt",
	"uninstall.php",
	"includes",
	"admin"
)

foreach ($path in $paths) {
	if (-not (Test-Path $path)) {
		throw "Required path missing: $path"
	}
}

if (Test-Path $Output) {
	Remove-Item $Output -Force
}

$zip = [System.IO.Compression.ZipFile]::Open(
	(Join-Path $root $Output),
	[System.IO.Compression.ZipArchiveMode]::Create
)

try {
	foreach ($path in $paths) {
		if (Test-Path $path -PathType Leaf) {
			$entryName = $path -replace '\\', '/'
			[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
				$zip,
				(Join-Path $root $path),
				$entryName
			)
			continue
		}

		Get-ChildItem -Path $path -Recurse -File | ForEach-Object {
			$relative = $_.FullName.Substring($root.Length + 1)
			$entryName = $relative -replace '\\', '/'
			[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
				$zip,
				$_.FullName,
				$entryName
			)
		}
	}
}
finally {
	$zip.Dispose()
}

Write-Host "Created $Output ($(Join-Path $root $Output))"
