# Run Composer dev commands when `composer` is not on PATH.
# Uses winget PHP if found, otherwise `php` from PATH.
param(
	[Parameter(Position = 0)]
	[ValidateSet('update', 'test', 'phpcs')]
	[string]$Command = 'test'
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$phpCandidates = @(
	'php',
	"$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe",
	"$env:LOCALAPPDATA\studio_app\app-1.11.0\resources\php-bin\8.4.21\php.exe"
)

$php = $null
foreach ($candidate in $phpCandidates) {
	if ($candidate -eq 'php') {
		$cmd = Get-Command php -ErrorAction SilentlyContinue
		if ($cmd) {
			$php = $cmd.Source
			break
		}
		continue
	}
	if (Test-Path $candidate) {
		$php = $candidate
		break
	}
}

if (-not $php) {
	throw 'PHP not found. Install with: winget install PHP.PHP.8.3'
}

if (-not (Test-Path "$root\composer.phar")) {
	throw "composer.phar not found in $root"
}

& $php "$root\composer.phar" $Command
