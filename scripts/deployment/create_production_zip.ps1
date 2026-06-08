# PowerShell script to create production-ready ZIP for GoDaddy upload
# This script creates a clean version without development/test files based on current project layout

$projectPath = "F:\connecthub"
$zipPath = "F:\connecthub_production.zip"
$tempPath = "F:\connecthub_temp_production"

Write-Host "Creating production-ready package for GoDaddy..." -ForegroundColor Green

# Create temporary directory
if (Test-Path $tempPath) {
    Remove-Item $tempPath -Recurse -Force
}
New-Item -ItemType Directory -Path $tempPath | Out-Null

# Folders to include (from repo root)
$includeFolders = @(
    'assets',
    'src',
    'config',
    'database',
    'api',
    'auth',
    'payment',
    'components',
    'static'
)

# Files in repo root to include
$includeFiles = @(
    '.htaccess',
    '.user.ini',
    '404.shtml',
    'favicon.ico',
    'index.php',
    'login.php',
    'register.php',
    'logout.php',
    'dashboard.php',
    'groups.php',
    'group-detail.php',
    'events.php',
    'event-detail.php',
    'create-group.php',
    'create-event.php',
    'manage-group.php',
    'membership.php',
    'security-dashboard.php',
    'healthcheck.php',
    'production_config.php',
    'GODADDY_SETUP.md',
    'README_PRODUCTION.md',
    'LICENSE'
)

# Copy folders
foreach ($folder in $includeFolders) {
    $sourcePath = Join-Path $projectPath $folder
    $destPath = Join-Path $tempPath $folder

    if (Test-Path $sourcePath -PathType Container) {
        Copy-Item $sourcePath -Destination $destPath -Recurse -Force
        Write-Host "Copied folder: $folder" -ForegroundColor Yellow
    } else {
        Write-Host "Skipping missing folder: $folder" -ForegroundColor DarkYellow
    }
}

# Copy files
foreach ($file in $includeFiles) {
    $sourcePath = Join-Path $projectPath $file
    if (Test-Path $sourcePath -PathType Leaf) {
        Copy-Item $sourcePath -Destination (Join-Path $tempPath $file) -Force
        Write-Host "Copied file: $file" -ForegroundColor Yellow
    } else {
        Write-Host "Skipping missing file: $file" -ForegroundColor DarkYellow
    }
}

# Create clean uploads directory structure under public/ to match constants.php (PUBLIC_PATH . '/uploads')
$publicPath = Join-Path $tempPath 'public'
if (!(Test-Path $publicPath)) { New-Item -ItemType Directory -Path $publicPath | Out-Null }
$uploadsSource = Join-Path $projectPath 'uploads'
$uploadsDest = Join-Path $publicPath 'uploads'
New-Item -ItemType Directory -Path $uploadsDest -Force | Out-Null

# Always include .htaccess and placeholders if present from existing uploads folder
foreach ($name in @('.htaccess', '.gitkeep', 'README.md')) {
    $src = Join-Path $uploadsSource $name
    if (Test-Path $src -PathType Leaf) {
        Copy-Item $src -Destination (Join-Path $uploadsDest $name) -Force
    }
}

# Standard subfolders to pre-create
$uploadDirs = @('profiles','groups','events','event-media','documents')
foreach ($dir in $uploadDirs) {
    $path = Join-Path $uploadsDest $dir
    if (!(Test-Path $path)) { New-Item -ItemType Directory -Path $path | Out-Null }
}
Write-Host "Prepared public/uploads directory structure" -ForegroundColor Green

# Write a .env file to force production environment and correct URLs
$envPath = Join-Path $tempPath '.env'
$envContent = @(
    'APP_ENV=production',
    'BASE_URL=https://www.phat-fitness.com',
    'SITE_URL=https://www.phat-fitness.com'
) -join "`r`n"
Set-Content -Path $envPath -Value $envContent -Encoding UTF8
Write-Host "Wrote .env with APP_ENV=production" -ForegroundColor Cyan

# Remove development and misc files from temp copy
$devGlobs = @(
    # Config dev overrides
    'config/local_config.php',
    'config/local_config.template.php',

    # Debug/test/setup scripts in root
    'debug-*.php',
    'test-*.php',
    'setup-*.php',
    'add-*.php',
    'fix-*.php',
    'sftp-test.php',
    'under-construction.php',
    'check-payments.php',
    'test-db.php',
    'test-database.php',
    'test-bootstrap.php',
    'test-exact-security.php',
    'test-security-dashboard.php',

    # Unrelated/legacy and local-only folders
    'docs',
    'deployment',
    'dist',
    'connecthub',
    'testingphp',
    'ordertracker.com.au',
    'theextremlysimpletodolist.com.au',
    'portfolios',
    'coles',
    'vaultedge',
    'php',
    '.vscode',
    '.git'
)

foreach ($glob in $devGlobs) {
    Get-ChildItem -Path $tempPath -Filter $glob -Recurse -Force -ErrorAction SilentlyContinue | ForEach-Object {
        try {
            if ($_.PSIsContainer) {
                Remove-Item $_.FullName -Recurse -Force
            } else {
                Remove-Item $_.FullName -Force
            }
            Write-Host "Removed dev artifact: $($_.FullName)" -ForegroundColor Cyan
        } catch {
            Write-Host "Could not remove: $($_.FullName) -> $($_.Exception.Message)" -ForegroundColor Red
        }
    }
}

# Create the ZIP file
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

try {
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($tempPath, $zipPath)
    Write-Host "✅ Production ZIP created successfully: $zipPath" -ForegroundColor Green
    $zipSize = (Get-Item $zipPath).Length / 1MB
    Write-Host "ZIP file size: $([math]::Round($zipSize, 2)) MB" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Error creating ZIP file: $($_.Exception.Message)" -ForegroundColor Red
}

# Clean up temporary directory
Remove-Item $tempPath -Recurse -Force
Write-Host "Cleaned up temporary files" -ForegroundColor Yellow

Write-Host "`n🚀 Ready for GoDaddy upload!" -ForegroundColor Green
Write-Host "Upload the contents of the ZIP file to your GoDaddy hosting (public_html)." -ForegroundColor White
Write-Host "Follow the instructions in GODADDY_SETUP.md (included in the ZIP)." -ForegroundColor White