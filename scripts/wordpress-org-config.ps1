# Shared paths for WordPress.org SVN sync and release.
# Override any value with environment variables before calling scripts.

function Get-TortoiseProcPath {
	$keys = @(
		'HKLM:\SOFTWARE\TortoiseSVN',
		'HKLM:\SOFTWARE\WOW6432Node\TortoiseSVN'
	)

	foreach ($key in $keys) {
		try {
			$procPath = (Get-ItemProperty -Path $key -ErrorAction Stop).ProcPath
			if ($procPath -and (Test-Path $procPath)) {
				return $procPath
			}
		}
		catch {
			continue
		}
	}

	$candidates = @(
		'C:\Program Files\TortoiseSVN\bin\TortoiseProc.exe',
		'C:\Program Files (x86)\TortoiseSVN\bin\TortoiseProc.exe',
		'D:\2 - Prog\TortoiseSVN\bin\TortoiseProc.exe'
	)

	foreach ($candidate in $candidates) {
		if (Test-Path $candidate) {
			return $candidate
		}
	}

	return $null
}

function Get-PluginVersion {
	param(
		[string]$SourceRoot = $script:WordPressOrgConfig.SourceRoot
	)

	$readmePath = Join-Path $SourceRoot 'readme.txt'
	if (-not (Test-Path $readmePath)) {
		throw "readme.txt not found at $readmePath"
	}

	$stableTag = Select-String -Path $readmePath -Pattern '^Stable tag:\s*(\S+)' | ForEach-Object { $_.Matches[0].Groups[1].Value }
	if (-not $stableTag) {
		throw 'Could not read Stable tag from readme.txt'
	}

	$pluginPath = Join-Path $SourceRoot 'vendorhub.php'
	$headerVersion = Select-String -Path $pluginPath -Pattern '^\s*\*\s*Version:\s*(\S+)' | ForEach-Object { $_.Matches[0].Groups[1].Value }
	if (-not $headerVersion) {
		throw 'Could not read Version from vendorhub.php'
	}

	if ($stableTag -ne $headerVersion) {
		throw "Version mismatch: readme Stable tag ($stableTag) != vendorhub.php Version ($headerVersion)"
	}

	return $stableTag
}

$script:WordPressOrgConfig = @{
	SourceRoot    = if ($env:VENDORHUB_WC_SOURCE_ROOT) { $env:VENDORHUB_WC_SOURCE_ROOT } else { (Split-Path -Parent $PSScriptRoot) }
	SvnRoot       = if ($env:VENDORHUB_WC_SVN_ROOT) { $env:VENDORHUB_WC_SVN_ROOT } else { Join-Path (Split-Path -Parent (Split-Path -Parent $PSScriptRoot)) 'vendorhub-for-woocommerce-svn' }
	SvnUrl        = if ($env:VENDORHUB_WC_SVN_URL) { $env:VENDORHUB_WC_SVN_URL } else { 'https://plugins.svn.wordpress.org/myvendorhub-for-woocommerce' }
	SvnUsername   = if ($env:VENDORHUB_WC_SVN_USERNAME) { $env:VENDORHUB_WC_SVN_USERNAME } else { 'myvendorhub' }
	TortoiseProc  = if ($env:TORTOISEPROC_PATH) { $env:TORTOISEPROC_PATH } else { Get-TortoiseProcPath }
	TrunkPaths    = @(
		'vendorhub.php',
		'readme.txt',
		'uninstall.php',
		'includes',
		'admin',
		'languages'
	)
	AssetPatterns = @('*.png')
}
