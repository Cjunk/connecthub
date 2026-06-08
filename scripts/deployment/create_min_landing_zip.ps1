<#
Creates a minimal production ZIP for showing only the landing page (index.php)
Includes only the core config and layout files required to render the homepage.
Adds a .env with APP_ENV=production so production_config.php is loaded.
#>

param(
    [string]$ProjectPath = 'F:\connecthub',
    [string]$ZipPath = 'F:\connecthub_min_landing.zip'
)

$tempPath = 'F:\connecthub_temp_min'

Write-Host 'Creating MINIMAL landing-only package…' -ForegroundColor Green

if (Test-Path $tempPath) { Remove-Item $tempPath -Recurse -Force }
New-Item -ItemType Directory -Path $tempPath | Out-Null

# Helper to ensure directory exists before copying specific files
function Copy-FilePreservePath($root, $relPath, $destRoot) {
    $src = Join-Path $root $relPath
    if (Test-Path $src -PathType Leaf) {
        $dest = Join-Path $destRoot $relPath
        $destDir = Split-Path $dest -Parent
        if (!(Test-Path $destDir)) { New-Item -ItemType Directory -Path $destDir | Out-Null }
        Copy-Item $src -Destination $dest -Force
        Write-Host "Copied: $relPath" -ForegroundColor Yellow
    } else {
        Write-Host "Missing (skipped): $relPath" -ForegroundColor DarkYellow
    }
}

# Minimal required files
$files = @(
    'index.php',
    '.htaccess',
    '.user.ini',
    'favicon.ico',
    # Config core
    'config/constants.php',
    'config/bootstrap.php',
    'config/env_loader.php',
    # Production config
    'production_config.php',
    # Layouts and helpers
    'src/views/layouts/header.php',
    'src/views/layouts/footer.php',
    'src/helpers/functions.php',
    # Optional minimal assets
    'assets/css/clean-style.css',
    'assets/js/main.js',
    # Helpful readme
    'README_PRODUCTION.md',
    'GODADDY_SETUP.md',
    'LICENSE'
)

foreach ($f in $files) { Copy-FilePreservePath -root $ProjectPath -relPath $f -destRoot $tempPath }

# Ensure assets folders exist if css/js were copied
foreach ($folder in @('assets/css','assets/js','src/views/layouts','src/helpers','config')) {
    $full = Join-Path $tempPath $folder
    if (!(Test-Path $full)) { New-Item -ItemType Directory -Path $full | Out-Null }
}

# Create minimal uploads folder (not used by landing, but safe to have)
$uploads = Join-Path $tempPath 'uploads'
if (!(Test-Path $uploads)) { New-Item -ItemType Directory -Path $uploads | Out-Null }
New-Item -ItemType Directory -Path (Join-Path $uploads 'events') -ErrorAction SilentlyContinue | Out-Null
New-Item -ItemType Directory -Path (Join-Path $uploads 'groups') -ErrorAction SilentlyContinue | Out-Null
New-Item -ItemType Directory -Path (Join-Path $uploads 'profiles') -ErrorAction SilentlyContinue | Out-Null

# Write a .env to force production branch and correct URLs
$envPath = Join-Path $tempPath '.env'
$envContent = @(
    'APP_ENV=production',
    'BASE_URL=https://www.phat-fitness.com',
    'SITE_URL=https://www.phat-fitness.com'
) -join "`r`n"
Set-Content -Path $envPath -Value $envContent -Encoding UTF8
Write-Host 'Wrote .env with APP_ENV=production' -ForegroundColor Cyan

# Clean up any dev-only files that might have slipped in
Get-ChildItem -Path $tempPath -Filter 'local_config.php' -Recurse -Force -ErrorAction SilentlyContinue | Remove-Item -Force

# Create the ZIP
if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempPath, $ZipPath)
Write-Host "✅ Minimal landing ZIP created: $ZipPath" -ForegroundColor Green

# Cleanup
Remove-Item $tempPath -Recurse -Force
Write-Host 'Cleaned up temp files' -ForegroundColor Yellow