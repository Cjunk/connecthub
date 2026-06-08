param(
    [string]$HostName = 'localhost',
    [int]$Port = 8080,
    [string]$DocRoot = '.'
)

# Force PostgreSQL for local dev without changing committed config.
$env:DB_DRIVER = 'pgsql'

Write-Host "Starting ConnectHub local server at http://$HostName`:$Port"
Write-Host "DB_DRIVER=$($env:DB_DRIVER)"

php -S "$HostName`:$Port" -t $DocRoot
