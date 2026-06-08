# PowerShell script to fix config paths in all public PHP files
# This updates relative paths to work in both local and production environments

$files = Get-ChildItem "public\*.php" | Where-Object { $_.Name -ne 'index.php' }

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw

    # Check if file needs updating
    if ($content -match "require_once '\.\./config/") {
        Write-Host "Fixing: $($file.Name)" -ForegroundColor Yellow

        # Add dynamic path resolution at the top
        $header = @"
// Dynamic path resolution for local vs production
`$basePath = (strpos(`$_SERVER['HTTP_HOST'], 'localhost') !== false || strpos(`$_SERVER['HTTP_HOST'], '127.0.0.1') !== false)
    ? '../'  // Local development
    : 'connecthub/'; // Production

"@

        # Replace require_once statements
        $content = $content -replace "require_once '\.\./config/constants\.php';", "require_once `$basePath . 'config/constants.php';"
        $content = $content -replace "require_once '\.\./config/bootstrap\.php';", "require_once `$basePath . 'config/bootstrap.php';"
        $content = $content -replace "require_once '\.\./config/database\.php';", "require_once `$basePath . 'config/database.php';"
        $content = $content -replace "require_once '\.\./config/ads\.php';", "require_once `$basePath . 'config/ads.php';"

        # For files that start with PHP opening tag, insert after it
        if ($content -match "^<\?php\s*$" -and $content -notmatch "// Dynamic path resolution") {
            $content = $content -replace "(<\?php\s*)$", "`$1`n$header"
        }

        # Save the file
        $content | Set-Content $file.FullName -Encoding UTF8
        Write-Host "✅ Fixed: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "`n🎉 All public PHP files have been updated!" -ForegroundColor Green
Write-Host "Local development and production should now work correctly." -ForegroundColor White