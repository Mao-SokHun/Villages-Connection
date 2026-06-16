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

Write-Host "Optional dev HTTPS certs created."
Write-Host "Local dev uses HTTP: http://localhost:8080"
Write-Host "Production HTTPS: docker-compose.prod.yml + scripts/oracle-enable-https.sh"
