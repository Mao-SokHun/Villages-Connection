$ErrorActionPreference = "Stop"
$certDir = Join-Path $PSScriptRoot "..\docker\certs"
New-Item -ItemType Directory -Force -Path $certDir | Out-Null

$key = Join-Path $certDir "dev.key"
$crt = Join-Path $certDir "dev.crt"

if ((Test-Path $key) -and (Test-Path $crt)) {
    Write-Host "SSL certs already exist in docker/certs/"
    exit 0
}

openssl req -x509 -nodes -days 825 -newkey rsa:2048 `
    -keyout $key `
    -out $crt `
    -subj "/CN=localhost"

Write-Host "Created docker/certs/dev.crt and dev.key"
Write-Host "Use https://localhost:8443 after: docker compose up -d --build"
