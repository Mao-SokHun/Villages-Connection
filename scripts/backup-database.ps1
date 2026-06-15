$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$backupDir = Join-Path $root "storage\backups"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

$envFile = Join-Path $root ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match '^\s*#' -or $_ -notmatch '=') { return }
        $pair = $_ -split '=', 2
        $name = $pair[0].Trim()
        $value = $pair[1].Trim()
        if ($name -ne '') {
            Set-Item -Path "env:$name" -Value $value
        }
    }
}

$dbName = if ($env:DB_DATABASE) { $env:DB_DATABASE } else { "project_cms" }
$dbUser = if ($env:DB_USERNAME) { $env:DB_USERNAME } else { "postgres" }
$dbHost = if ($env:DB_HOST) { $env:DB_HOST } else { "127.0.0.1" }
$dbPort = if ($env:DB_PORT) { $env:DB_PORT } else { "5432" }

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$outFile = Join-Path $backupDir ("db-" + $dbName + "-" + $timestamp + ".sql.gz")

$useDocker = $false
if ($dbHost -eq "db") {
    $useDocker = $true
}

if ($useDocker) {
    $container = "project_cms_db"
    docker exec $container sh -c "pg_dump -U $dbUser -d $dbName --no-owner --no-acl | gzip" | Set-Content -Path $outFile -Encoding Byte
} else {
    $pgDump = Get-Command pg_dump -ErrorAction SilentlyContinue
    if (-not $pgDump) {
        Write-Error "pg_dump not found. Install PostgreSQL client tools or use Docker (DB_HOST=db)."
    }
    $env:PGPASSWORD = $env:DB_PASSWORD
    & pg_dump -h $dbHost -p $dbPort -U $dbUser -d $dbName --no-owner --no-acl | gzip > $outFile
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}

if (-not (Test-Path $outFile)) {
    Write-Error "Backup failed."
}

$sizeKb = [math]::Round((Get-Item $outFile).Length / 1KB, 1)
Write-Host "Backup saved: $outFile ($sizeKb KB)"

# Keep last 14 backups
Get-ChildItem $backupDir -Filter "db-*.sql.gz" | Sort-Object LastWriteTime -Descending | Select-Object -Skip 14 | Remove-Item -Force
