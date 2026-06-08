# PowerShell script to deploy Uhura directly to GoDaddy root domain
# This uploads all necessary files to public_html (not a subdirectory)

Write-Host "🚀 Deploying Uhura to GoDaddy Root Domain..." -ForegroundColor Green
Write-Host "This will make www.phat-fitness.com your Uhura landing page" -ForegroundColor Yellow

# Files to upload to root public_html
$rootFiles = @(
    "production_config.php",
    "public/index.php",
    "public/.htaccess"
)

$folders = @(
    "public/assets",
    "config", 
    "src",
    "database"
)

Write-Host "`n📁 Uploading root files..." -ForegroundColor Cyan
foreach ($file in $rootFiles) {
    if (Test-Path $file) {
        Write-Host "  → $file" -ForegroundColor White
    }
}

Write-Host "`n📂 Uploading folders..." -ForegroundColor Cyan
foreach ($folder in $folders) {
    if (Test-Path $folder) {
        Write-Host "  → $folder/" -ForegroundColor White
    }
}

Write-Host "`n✅ Ready for upload!" -ForegroundColor Green
Write-Host "Use VS Code SFTP to upload these files/folders to your public_html root" -ForegroundColor White
Write-Host "`n📋 Manual upload steps:" -ForegroundColor Yellow
Write-Host "1. Right-click each file/folder above → 'Upload'"
Write-Host "2. Or use: SFTP: Upload Project (select specific files)"
Write-Host "3. Test: https://www.phat-fitness.com" -ForegroundColor White