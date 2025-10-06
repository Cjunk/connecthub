# PowerShell script to create production-ready ZIP for GoDaddy upload
# This script creates a clean version without development files

$projectPath = "F:\connecthub"
$zipPath = "F:\connecthub_production.zip"
$tempPath = "F:\connecthub_temp_production"

Write-Host "Creating production-ready package for GoDaddy..." -ForegroundColor Green

# Create temporary directory
if (Test-Path $tempPath) {
    Remove-Item $tempPath -Recurse -Force
}
New-Item -ItemType Directory -Path $tempPath | Out-Null

# Copy essential files and folders
$filesToCopy = @(
    "public",
    "config", 
    "src",
    "database",
    "assets",
    "production_config.php",
    "GODADDY_SETUP.md",
    "README_PRODUCTION.md"
)

foreach ($item in $filesToCopy) {
    $sourcePath = Join-Path $projectPath $item
    $destPath = Join-Path $tempPath $item
    
    if (Test-Path $sourcePath) {
        if (Test-Path $sourcePath -PathType Container) {
            # It's a directory
            Copy-Item $sourcePath -Destination $destPath -Recurse -Force
            Write-Host "Copied directory: $item" -ForegroundColor Yellow
        } else {
            # It's a file
            Copy-Item $sourcePath -Destination $destPath -Force
            Write-Host "Copied file: $item" -ForegroundColor Yellow
        }
    } else {
        Write-Host "Warning: $item not found" -ForegroundColor Red
    }
}

# Remove development-specific files from the temp copy
$devFilesToRemove = @(
    "$tempPath\config\local_config.template.php",
    "$tempPath\public\uploads\README.md"
)

foreach ($file in $devFilesToRemove) {
    if (Test-Path $file) {
        Remove-Item $file -Force
        Write-Host "Removed dev file: $file" -ForegroundColor Cyan
    }
}

# Create uploads directory structure
$uploadsPath = "$tempPath\public\uploads"
if (!(Test-Path $uploadsPath)) {
    New-Item -ItemType Directory -Path $uploadsPath | Out-Null
}

# Create subdirectories for uploads
$uploadDirs = @("profiles", "groups", "events", "documents")
foreach ($dir in $uploadDirs) {
    $dirPath = Join-Path $uploadsPath $dir
    if (!(Test-Path $dirPath)) {
        New-Item -ItemType Directory -Path $dirPath | Out-Null
        Write-Host "Created upload directory: $dir" -ForegroundColor Green
    }
}

# Create the ZIP file
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

try {
    # Use .NET compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($tempPath, $zipPath)
    Write-Host "✅ Production ZIP created successfully: $zipPath" -ForegroundColor Green
    
    # Show file size
    $zipSize = (Get-Item $zipPath).Length / 1MB
    Write-Host "ZIP file size: $([math]::Round($zipSize, 2)) MB" -ForegroundColor Cyan
    
} catch {
    Write-Host "❌ Error creating ZIP file: $($_.Exception.Message)" -ForegroundColor Red
}

# Clean up temporary directory
Remove-Item $tempPath -Recurse -Force
Write-Host "Cleaned up temporary files" -ForegroundColor Yellow

Write-Host "`n🚀 Ready for GoDaddy upload!" -ForegroundColor Green
Write-Host "Upload the contents of the ZIP file to your GoDaddy hosting." -ForegroundColor White
Write-Host "Follow the instructions in GODADDY_SETUP.md (included in the ZIP)" -ForegroundColor White